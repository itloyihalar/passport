<?php

namespace App\Http\Controllers\Buyer;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Bid;
use App\Models\Charge;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\Review;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $pageTitle = 'My Projects';
        $buyer = auth()->guard('buyer')->user();
        $projects = Project::searchable(['job:title', 'user:username'])->filter(['status'])->dateFilter()->orderBy('id', 'desc')->where('buyer_id',  $buyer->id)->with(['bid.job', 'user', 'buyer', 'review', 'buyerReview'])->paginate(getPaginate());
        return view('Template::buyer.project.index', compact('pageTitle', 'projects'));
    }

    public function detail($id)
    {
        $pageTitle = 'Project Details';
        $project = Project::where('status', '!=', Status::PROJECT_REJECTED)->with(['job', 'bid', 'user', 'buyer', 'review', 'buyerReview'])->where('id', $id)->firstOrFail();
        return view('Template::buyer.project.detail', compact('pageTitle', 'project'));
    }

    public function downloadFile($id, $file)
    {
        $buyer = auth()->guard('buyer')->user();
        $project = Project::where('id', $id)->where('buyer_id', $buyer->id)->with('job')->first();

        if (!$project) {
            $notify[] = ['error', 'Project not found!'];
            return back()->withNotify($notify);
        }
        $path = getFilePath('projectFile');
        $file = decrypt($file);
        $fullPath = $path . '/' . $file;
        if (!file_exists($fullPath)) {
            abort(404, 'File not found');
        }
        $title = slug(substr($project->job->title, 0, 20));
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($fullPath);
        header('Content-Disposition: attachment; filename="' . $title . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($fullPath);
    }
    public function complete(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
        ]);
        $buyer = auth()->guard('buyer')->user();
        $project = Project::reviewing()->where('buyer_id',  $buyer->id)->find($id);
        if (!$project) {
            $notify[] = ['error', 'Project not found'];
            return back()->withNotify($notify);
        }

        $bid   = Bid::accepted()->where('project_id', $project->id)->with('job')->first();
        if (!$bid) {
            $notify[] = ['error', 'Bid not found of this project'];
            return back()->withNotify($notify);
        }

        $freelancer = $project->user;
        $bidAmount =  $bid->bid_amount;


        //if author already used escrow! 
        if (!$bid->project->escrow_amount && $buyer->balance <  $bidAmount) {
            $notify[] = ['error', 'Insufficient balance for this completed project!'];
            return back()->withNotify($notify);
        }

        //if author not used escrow! 
        if (!$bid->project->escrow_amount) {
            $buyer->balance -= $bidAmount;
            $buyer->save();
        }

        $project->status = Status::PROJECT_COMPLETED;
        $project->save();

        $bid->status = Status::BID_COMPLETED;
        $bid->save();

        $job = $bid->job;
        $job->status = Status::JOB_COMPLETED;
        $job->save();

        // Manage Charge Percent;
        $charges = Charge::orderBy('amount', 'asc')->get();
        $freelancerEarning = $freelancer->earning;
        $percentCharge = 0;
        $fixedCharge = gs('fixed_service_charge');
        if (gs('percent_service_charge')) {
            $applied = false;
            foreach ($charges as $charge) {
                if (!is_null($charge->amount) && !is_null($charge->percent)) {
                    if ($freelancerEarning <= $charge->amount) {
                        $percentCharge = $charge->percent;
                        $applied = true;
                        break;
                    }
                }
            }
            if (!$applied) {
                $percentCharge = 0;
            }
        }

        if ($percentCharge) {
            $calculatedChargeAmount = ($bidAmount * $percentCharge) / 100;
            $chargeAmount =   $calculatedChargeAmount + $fixedCharge;
        } else {
            $chargeAmount = $fixedCharge;
        }

        $review = new Review();
        $review->user_id    = $freelancer->id;
        $review->buyer_id  = $buyer->id;
        $review->project_id = $project->id;
        $review->rating     = $request->rating;
        $review->review     = $request->review;
        $review->save();

        $reviews = Review::where('user_id', $freelancer->id)->get(['rating']);

        $finalIncome  =  $bidAmount - $chargeAmount;
        $freelancer->balance += $bidAmount;
        $freelancer->save();

        $trx = GetTrx();
        $transaction               = new Transaction();
        $transaction->user_id      = $freelancer->id;
        $transaction->amount       = $bidAmount;
        $transaction->post_balance = $freelancer->balance;
        $transaction->trx_type     = '+';
        $transaction->details      = 'Project completed for job ' . $job->title;
        $transaction->trx          = $trx;
        $transaction->remark       = 'completed_project';
        $transaction->save();


        $freelancer->balance -= $chargeAmount;
        $freelancer->earning += $finalIncome;
        $freelancer->avg_rating = $reviews->count() > 0 ? $reviews->sum('rating') / $reviews->count() : 0;
        $freelancer->save();

        $freelancer->updateBadge();

        $transaction               = new Transaction();
        $transaction->user_id      = $freelancer->id;
        $transaction->amount       = $chargeAmount;
        $transaction->post_balance = $freelancer->balance;
        $transaction->trx_type     = '-';
        $transaction->details      = 'Project completed commission for ' . $job->title;
        $transaction->trx          = $trx;
        $transaction->remark       = 'commission';
        $transaction->save();

        $trxData = Transaction::where('project_id', $project->id)->first();
        $transaction = $trxData ? $trxData : new Transaction();
        $transaction->buyer_id = $buyer->id;
        $transaction->amount = $bidAmount;
        $transaction->post_balance = $buyer->balance;
        $transaction->trx_type = '-';
        $transaction->remark = 'completed_project';
        $transaction->details = 'Project completed for job ' . $job->title;
        $transaction->trx = $trxData ? $trxData->trx : null;
        $transaction->save();


        notify($freelancer, 'PROJECT_COMPLETED', [
            'job'      => $job->title,
            'income'   => showAmount($finalIncome),
            'charge'   => showAmount($chargeAmount),
            'buyer'   => $buyer->fullname,
            'rating'   => $request->rating,
            'review'   => $request->review,
        ]);

        $notify[] = ['success', 'Project completed successfully'];
        return back()->withNotify($notify);
    }

    public function report(Request $request, $id)
    {
        $request->validate([
            'report_reason' => 'required|string',
        ]);

        $buyer = auth()->guard('buyer')->user();
        $project = Project::reviewing()->with('buyer')->where('buyer_id',  $buyer->id)->find($id);
        if (!$project) {
            $notify[] = ['error', 'Project not found'];
            return back()->withNotify($notify);
        }
        $bid   = Bid::accepted()->where('project_id', $project->id)->with('job')->first();
        if (!$bid) {
            $notify[] = ['error', 'Bid not found of this project'];
            return back()->withNotify($notify);
        }

        $project->status = Status::PROJECT_REPORTED;
        $project->report_reason =  $request->report_reason;
        $project->save();

        $freelancer = $project->user;

        $conversation = Conversation::where('buyer_id', $buyer->id)
            ->where('user_id', $freelancer->id)
            ->first();

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->job_id = $project->job_id;
            $conversation->user_id = $project->user_id;
            $conversation->buyer_id = $project->buyer_id;
            $conversation->user_id = $freelancer->id;
            $conversation->save();
        }

        $message          = new Message();
        $message->message = 'REPORTED:: ' . $request->report_reason;
        $message->conversation_id = $conversation->id;
        $message->buyer_id = $conversation->buyer_id;
        $message->buyer_read_at = now();
        $message->save();

        notify($freelancer, 'PROJECT_REPORTED', [
            'job'  => $project->job->title,
            'buyer'  => $buyer->fullname,
            'reason' => $project->report_reason,
        ]);

        $adminNotification            = new AdminNotification();
        $adminNotification->buyer_id   = $project->buyer_id;
        $adminNotification->title     = 'A new report has been submitted by ' . $project->buyer->fullname;
        $adminNotification->click_url = urlPath('admin.project.details', $project->id);
        $adminNotification->save();

        $notify[] = ['success', 'Project reported successfully'];
        return back()->withNotify($notify);
    }


    public function updateReviewRating(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
        ]);

        $buyer =  auth()->guard('buyer')->user();
        $project = Project::completed()->where('buyer_id', $buyer->id)->findOrFail($id);

        $freelancer =  $project->user;
        $conversation = Conversation::where('user_id', $freelancer->id)->where('buyer_id', $buyer->id)->first();

        $mainQuery = Review::query();
        $review = (clone $mainQuery)->where('project_id', $id)->first();

        if (!$review) {
            $notify[] = ['error', 'Review not existing!'];
            return back()->withNotify($notify);
        }
        $review->rating     = $request->rating;
        $review->review     = $request->review;
        $review->save();

        $reviews = (clone $mainQuery)->where('user_id', $freelancer->id)->get(['rating']);
        $freelancer->avg_rating = $reviews->count() > 0 ? $reviews->avg('rating') : 0;
        $freelancer->save();

        if ($conversation) {
            $message = new Message();
            $message->conversation_id = $conversation->id;
            $message->buyer_id    = $buyer->id;
            $message->message    = 'UPDATED: rating- ' . $request->rating . '/(' . $project->review->rating . ') & review - (' . $request->review . '/' . $project->review->review . ')';
            $message->save();
        }

        $notify[] = ['success', 'Review & Rating updated successfully!'];
        return back()->withNotify($notify);
    }
}
