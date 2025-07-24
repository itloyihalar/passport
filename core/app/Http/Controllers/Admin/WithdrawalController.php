<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function pending($userId = null)
    {
        $pageTitle = 'Pending Withdrawals';
        $withdrawals = $this->withdrawalData('pending', userId: $userId);
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function approved($userId = null)
    {
        $pageTitle = 'Approved Withdrawals';
        $withdrawals = $this->withdrawalData('approved', userId: $userId);
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function rejected($userId = null)
    {
        $pageTitle = 'Rejected Withdrawals';
        $withdrawals = $this->withdrawalData('rejected', userId: $userId);
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }


    public function all($userId = null)
    {

        $pageTitle = 'All Withdrawals';
        $buyerId  = null;
        if (request()->buyer_id) {
            $buyerId = request()->buyer_id;
        }

        $withdrawalData = $this->withdrawalData(null, true, $userId, $buyerId);
        $withdrawals = $withdrawalData['data'];
        $summary = $withdrawalData['summary'];

        $successful = $summary['successful'];
        $pending = $summary['pending'];
        $rejected = $summary['rejected'];

        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals', 'successful', 'pending', 'rejected'));
    }


    protected function withdrawalData($scope = null, $summary = false, $userId = null, $buyerId = null)
    {
        $withdrawals = $scope ? Withdrawal::$scope() : Withdrawal::where('status', '!=', Status::PAYMENT_INITIATE);


        if ($userId) {
            $withdrawals->where('user_id', $userId);
        }

        if ($buyerId) {
            $withdrawals->where('buyer_id', $buyerId);
        }

        $withdrawals = $withdrawals->searchable(['trx', 'user:username', 'buyer:username'])->dateFilter();

        if (request()->method) {
            $withdrawals->where('method_id', request()->method);
        }


        if (!$summary) {
            return $withdrawals->with(['user', 'buyer', 'method'])->orderBy('id', 'desc')->paginate(getPaginate());
        }

        $successful = clone $withdrawals;
        $pending = clone $withdrawals;
        $rejected = clone $withdrawals;

        $successfulSummary = $successful->where('status', Status::PAYMENT_SUCCESS)->sum('amount');
        $pendingSummary = $pending->where('status', Status::PAYMENT_PENDING)->sum('amount');
        $rejectedSummary = $rejected->where('status', Status::PAYMENT_REJECT)->sum('amount');

        return [
            'data' => $withdrawals->with(['user', 'buyer', 'method'])->orderBy('id', 'desc')->paginate(getPaginate()),
            'summary' => [
                'successful' => $successfulSummary,
                'pending' => $pendingSummary,
                'rejected' => $rejectedSummary,
            ],
        ];
    }
    public function details($id)
    {
        $withdrawal = Withdrawal::where('id', $id)->where('status', '!=', Status::PAYMENT_INITIATE)->with(['user', 'buyer', 'method'])->firstOrFail();
        $pageTitle = 'Withdrawal Details';
        $details = $withdrawal->withdraw_information ? json_encode($withdrawal->withdraw_information) : null;
        return view('admin.withdraw.detail', compact('pageTitle', 'withdrawal', 'details'));
    }

    public function approve(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id', $request->id)->where('status', Status::PAYMENT_PENDING)->with('user', 'buyer')->firstOrFail();
        $withdraw->status = Status::PAYMENT_SUCCESS;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        $user = $withdraw->user ?? $withdraw->buyer;

        notify($user, 'WITHDRAW_APPROVE', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount, currencyFormat: false),
            'amount' => showAmount($withdraw->amount, currencyFormat: false),
            'charge' => showAmount($withdraw->charge, currencyFormat: false),
            'rate' => showAmount($withdraw->rate, currencyFormat: false),
            'trx' => $withdraw->trx,
            'admin_details' => $request->details
        ]);

        $notify[] = ['success', 'Withdrawal approved successfully'];
        return to_route('admin.withdraw.data.pending')->withNotify($notify);
    }


    public function reject(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id', $request->id)->where('status', Status::PAYMENT_PENDING)->with('user', 'buyer')->firstOrFail();

        $withdraw->status = Status::PAYMENT_REJECT;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        $user = $withdraw->user ?? $withdraw->buyer;
        $user->balance += $withdraw->amount;
        $user->save();


        $transaction = new Transaction();
        if ($withdraw->user) {
            $transaction->user_id = $withdraw->user_id;
        } else {
            $transaction->buyer_id = $withdraw->buyer_id;
        }
        $transaction->amount = $withdraw->amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'withdraw_reject';
        $transaction->details = 'Refunded for withdrawal rejection';
        $transaction->trx = $withdraw->trx;
        $transaction->save();

        notify($user, 'WITHDRAW_REJECT', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount, currencyFormat: false),
            'amount' => showAmount($withdraw->amount, currencyFormat: false),
            'charge' => showAmount($withdraw->charge, currencyFormat: false),
            'rate' => showAmount($withdraw->rate, currencyFormat: false),
            'trx' => $withdraw->trx,
            'post_balance' => showAmount($user->balance, currencyFormat: false),
            'admin_details' => $request->details
        ]);

        $notify[] = ['success', 'Withdrawal rejected successfully'];
        return to_route('admin.withdraw.data.pending')->withNotify($notify);
    }
}
