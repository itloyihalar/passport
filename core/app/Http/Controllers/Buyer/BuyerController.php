<?php

namespace App\Http\Controllers\Buyer;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Models\Bid;
use App\Models\DeviceToken;
use App\Models\Form;
use App\Models\Job;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BuyerController extends Controller
{
    public function home()
    {
        $pageTitle = 'Dashboard';
        $buyer = auth()->guard('buyer')->user();
        $jobs = Job::where('buyer_id', $buyer->id)->with(['category'])->withCount('bids')->orderByDesc('id')->take(10)->get();

        $projectQuery = Project::where('buyer_id', $buyer->id);
        $widget = [
            'total_project'           => $projectQuery->count(),
            'total_running_project'   => $projectQuery->clone()->where('status', Status::PROJECT_RUNNING)->count(),
            'total_reviewing_project'   => $projectQuery->clone()->where('status', Status::PROJECT_BUYER_REVIEW)->count(),
            'total_bid'               => Bid::where('buyer_id', $buyer->id)->count(),
            'total_job_completed'     => Job::where('buyer_id', $buyer->id)->where('status', Status::JOB_COMPLETED)->count(),
        ];
        $projects = $projectQuery->clone()->where('status', Status::PROJECT_COMPLETED)->with(['job', 'bid', 'user'])->orderBy('uploaded_at', 'DESC')->take(3)->get();
        $monthlyData = $this->getMonthlyBidData($buyer);

        $holdBalance = $projectQuery->clone() 
            ->whereIn('status', [Status::PROJECT_RUNNING, Status::PROJECT_BUYER_REVIEW]) 
            ->whereNotNull('escrow_amount')
            ->sum('escrow_amount');

        return view('Template::buyer.dashboard', compact('pageTitle', 'buyer', 'widget', 'jobs', 'projects', 'monthlyData', 'holdBalance'));
    }

    public function getMonthlyBidData($buyer)
    {
        $monthlyBids = Bid::with(['project'])
            ->join('projects', 'projects.id', '=', 'bids.project_id')
            ->where('projects.buyer_id', $buyer->id)
            ->where('projects.status', Status::PROJECT_COMPLETED)
            ->whereBetween('projects.uploaded_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('YEAR(projects.uploaded_at) as year, MONTH(projects.uploaded_at) as month, SUM(bids.bid_amount) as total_bid')
            ->groupBy(DB::raw('YEAR(projects.uploaded_at), MONTH(projects.uploaded_at)'))
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . $item->month;
            });

        $monthlyData = collect();
        $startDate   = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate     = Carbon::now()->endOfMonth();

        while ($startDate <= $endDate) {
            $year  = $startDate->year;
            $month = $startDate->month;
            $key   = $year . '-' . $month;

            $monthBids = $monthlyBids->get($key, (object) ['total_bid' => 0]);
            $monthlyData->push([
                'month'     => $startDate->format('M Y'),
                'total_bid' => $monthBids->total_bid,
            ]);

            $startDate->addMonth();
        }

        $totalAmount = $monthlyData->sum('total_bid');
        $monthlyData = $monthlyData->map(function ($data) use ($totalAmount) {
            $data['percentage'] = $totalAmount > 0 ? ($data['total_bid'] / $totalAmount) * 100 : 0;
            return $data;
        });

        return $monthlyData;
    }

    public function talentInviteByBuyer($fId)
    {
        $freelancer = User::active()->findOrFail($fId);
        $buyer = auth()->guard('buyer')->user();
        $totalActiveJobs = $buyer->jobs()->where('status', Status::JOB_PUBLISH)->count();
        if (!$totalActiveJobs) {
            $notify[] = ['error', 'You do not have any active job posts for freelancing.'];
            return back()->withNotify($notify);
        }

        notify($freelancer, 'FREELANCER_INVITATION', [
            'buyer' => $buyer->fullname,
            'active_post' => $totalActiveJobs,
            'post_page' => route('freelance.jobs', ['buyer' => $buyer->username])

        ]);
        $notify[] = ['success' => 'Invitation sent to the targeted freelancer successfully!'];
        return back()->withNotify($notify);
    }

    public function depositHistory(Request $request)
    {
        $pageTitle = 'Deposit History';
        $deposits = auth()->guard('buyer')->user()->deposits()->searchable(['trx'])->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::buyer.deposit_history', compact('pageTitle', 'deposits'));
    }

    public function show2faForm()
    {
        $ga = new GoogleAuthenticator();
        $user = auth()->guard('buyer')->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $pageTitle = '2FA Security';
        return view('Template::buyer.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->guard('buyer')->user();
        $request->validate([
            'key' => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);


        if ($response) {
            $user->tsc = $request->key;
            $user->ts = Status::ENABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator activated successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }

    public function disable2fa(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $user = auth()->guard('buyer')->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts = Status::DISABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator deactivated successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }

    public function transactions()
    {
        $pageTitle = 'Transactions';
        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');
        $transactions = Transaction::where('buyer_id', auth()->guard('buyer')->id())->searchable(['trx'])->filter(['trx_type', 'remark'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::buyer.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function kycForm()
    {
        
        if (auth()->guard('buyer')->user()->kv == Status::KYC_PENDING) {
            $notify[] = ['error', 'Your KYC is under review'];
            return to_route('buyer.home')->withNotify($notify);
        }
        if (auth()->guard('buyer')->user()->kv == Status::KYC_VERIFIED) {
            $notify[] = ['error', 'You are already KYC verified'];
            return to_route('buyer.home')->withNotify($notify);
        }
        $pageTitle = 'KYC Form';
        $form = Form::where('act', 'kyc_buyer')->first();
       
        
        return view('Template::buyer.kyc.form', compact('pageTitle', 'form'));
    }

    public function kycData()
    {
        $user = auth()->guard('buyer')->user();
        $pageTitle = 'KYC Data';
        abort_if($user->kv == Status::VERIFIED, 403);
        return view('Template::buyer.kyc.info', compact('pageTitle', 'user'));
    }

    public function kycSubmit(Request $request)
    {
        $form = Form::where('act', 'kyc_buyer')->firstOrFail();
        $formData = $form->form_data;
        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $user = auth()->guard('buyer')->user();
        foreach (@$user->kyc_data ?? [] as $kycData) {
            if ($kycData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
            }
        }
        $userData = $formProcessor->processFormData($request, $formData);
        $user->kyc_data = $userData;
        $user->kyc_rejection_reason = null;
        $user->kv = Status::KYC_PENDING;
        $user->save();

        $notify[] = ['success', 'KYC data submitted successfully'];
        return to_route('buyer.home')->withNotify($notify);
    }

    public function buyerData()
    {
        $user = auth()->guard('buyer')->user();
        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }
        $pageTitle  = 'User Data';
        $info       = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view('Template::buyer.user_data', compact('pageTitle', 'user', 'countries', 'mobileCode'));
    }

    public function buyerDataSubmit(Request $request)
    {
        $user = auth()->guard('buyer')->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('buyer.home');
        }

        $countryData  = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));

        $request->validate([
            'country_code' => 'required|in:' . $countryCodes,
            'country'      => 'required|in:' . $countries,
            'mobile_code'  => 'required|in:' . $mobileCodes,
            'username'     => 'required|unique:users|min:6',
            'mobile'       => ['required', 'regex:/^([0-9]*)$/', Rule::unique('users')->where('dial_code', $request->mobile_code)],
        ]);


        if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            $notify[] = ['info', 'Username can contain only small letters, numbers and underscore.'];
            $notify[] = ['error', 'No special character, space or capital letters in username.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        $user->country_code = $request->country_code;
        $user->mobile       = $request->mobile;
        $user->username     = $request->username;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country_name = @$request->country;
        $user->dial_code = $request->mobile_code;

        $user->profile_complete = Status::YES;
        $user->save();

        return to_route('buyer.home');
    }

    public function addDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            return ['success' => true, 'message' => 'Already exists'];
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->buyer_id = auth()->guard('buyer')->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = Status::NO;
        $deviceToken->save();

        return ['success' => true, 'message' => 'Token saved successfully'];
    }

    public function downloadAttachment($fileHash)
    {
        $filePath = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $title = slug(gs('site_name')) . '- attachments.' . $extension;
        try {
            $mimetype = mime_content_type($filePath);
        } catch (\Exception $e) {
            $notify[] = ['error', 'File does not exists'];
            return back()->withNotify($notify);
        }
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }
}
