<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\bid;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Validation\ValidationException;
use App\Models\Project;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use App\Events\LiveChat;
use App\Models\BuyerReview;
use App\Models\Charge;
use App\Models\Review;
use Illuminate\Support\Facades\Validator;

class ProjectManagerController extends Controller
{
    public function index()
    {
        $pageTitle = 'All Projects';
        $projects  = $this->projectData();
        return view('admin.project.index', compact('pageTitle', 'projects'));
    }
    public function reported()
    {
        $pageTitle = "Reported Projects";
        $projects = $this->projectData('reported');
        return view('admin.project.index', compact('pageTitle', 'projects'));
    }
    public function running()
    {
        $pageTitle = "Running Projects";
        $projects = $this->projectData('running');
        return view('admin.project.index', compact('pageTitle', 'projects'));
    }
    public function reviewing()
    {
        $pageTitle = "Reviewing Projects";
        $projects = $this->projectData('reviewing');
        return view('admin.project.index', compact('pageTitle', 'projects'));
    }
    public function rejected()
    {
        $pageTitle = "Rejected Projects";
        $projects = $this->projectData('rejected');
        return view('admin.project.index', compact('pageTitle', 'projects'));
    }

    public function completed()
    {
        $pageTitle = "Completed Projects";
        $projects = $this->projectData('completed');
        return view('admin.project.index', compact('pageTitle', 'projects'));
    }

    protected function projectData($scope = null)
    {
        if ($scope) {
            $projects = Project::$scope();
        } else {
            $projects = Project::query();
        }

        $projects = $projects->searchable(['job:title', 'user:username', 'buyer:username'])->with('job', 'user', 'buyer')->orderBy('id', 'DESC');
        if (request()->date) {
            try {
                $date      = explode('-', request()->date);
                $startDate = Carbon::parse(trim($date[0]))->format('Y-m-d');
                $endDate = @$date[1] ? Carbon::parse(trim(@$date[1]))->format('Y-m-d') : $startDate;
            } catch (\Exception $e) {
                throw ValidationException::withMessages(['error' => 'Unauthorized action']);
            }
            request()->merge(['start_date' => $startDate, 'end_date' => $endDate]);
            $projects =  $projects->whereHas('bid', function ($query) use ($startDate, $endDate) {
                $query->whereDate('deadline', '>=', $startDate)->whereDate('deadline', '<=', $endDate);
            });
        }
        return $projects->paginate(getPaginate());
    }

    public function details($id)
    {
        $pageTitle = "Project Details";
        $project   = Project::with('job.bids', 'bid', 'buyer', 'user')->findOrFail($id);
        $convId    =  Conversation::where('user_id', $project->user_id)->where('buyer_id', $project->buyer_id)->first();
        return view('admin.project.details', compact('pageTitle', 'project', 'convId'));
    }
    public function downloadFile($id, $file)
    {
        $project = Project::where('id', $id)->with('job')->first();
        if (!$project) {
            $notify[] = ['error', 'Project not found!'];
            return back()->withNotify($notify);
        }
        $path = getFilePath('projectFile');
        $file = decrypt($file);

        $full_path = $path . '/' . $file;
        $title = slug(substr($project->job->title, 0, 20));
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype = mime_content_type($full_path);
        header('Content-Disposition: attachment; filename="' . $title . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }


    public function rejectProject($id)
    {
        $project = Project::reported()->where('id', $id)->first();
        if (!$project) {
            $notify[] = ['error', 'Project not found'];
            return back()->withNotify($notify);
        }
        $buyer  = $project->buyer;
        $freelancer   = $project->user;

        $project->status = Status::PROJECT_REJECTED;
        $project->save();

        $job =  $project->job;
        $job->is_approved = Status::JOB_REJECTED;
        $job->status = Status::JOB_FINISHED;
        $job->save();

        if ($project->escrow_amount) {
            $buyer->balance += $project->escrow_amount;
            $buyer->save();

            $targetedTrx  = Transaction::where('buyer_id', $buyer->id)->where('project_id', $project->id)->first()->trx;
            $transaction               = new Transaction();
            $transaction->buyer_id    = $buyer->id;
            $transaction->project_id   = $project->id;
            $transaction->amount       = $project->escrow_amount;
            $transaction->post_balance = $buyer->balance;
            $transaction->trx_type     = '+';
            $transaction->details      = 'Reported project rejected, job ' . $job->title;
            $transaction->trx          = @$targetedTrx;
            $transaction->remark       = 'hold_amount_released';
            $transaction->save();
        }

        $notificationData = [
            'freelancer' => $freelancer->fullname,
            'buyer' => $buyer->fullname,
            'job' => $project->job->title,
            'escrow_amount' => $project->escrow_amount ?  showAmount($project->escrow_amount) : 'N/A',
        ];
        notify($buyer, 'REPORTED_PROJECT_REJECTED', $notificationData);
        notify($freelancer, 'REPORTED_PROJECT_REJECTED', $notificationData);


        $notify[] = ['success', 'Project rejected successfully'];
        return back()->withNotify($notify);
    }

    public function completeProject($id)
    {

        $project = Project::reported()->with('user', 'buyer')->where('id', $id)->first();
        if (!$project) {
            $notify[] = ['error', 'Project not found'];
            return back()->withNotify($notify);
        }
        $freelancer   = $project->user;
        $buyer  = $project->buyer;

        $bid   = Bid::accepted()->where('project_id', $project->id)->with('job')->first();
        if (!$bid) {
            $notify[] = ['error', 'Bid not found of this project'];
            return back()->withNotify($notify);
        }
        $bidAmount =  $bid->bid_amount;

        //if author already used escrow! 
        if (!$project->escrow_amount && $buyer->balance <  $bidAmount) {
            $notify[] = ['error', 'Insufficient balance for this completed project!'];
            return back()->withNotify($notify);
        }

        //if author not used escrow! 
        if (!$project->escrow_amount) {
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

        if ($percentCharge > 0) {
            $calculatedChargeAmount = ($bidAmount * $percentCharge) / 100;
            $chargeAmount =   $calculatedChargeAmount + $fixedCharge;
        } else {
            $chargeAmount = $fixedCharge;
        }

        $freelancer->balance += $bidAmount;
        $freelancer->save();


        $trx = GetTrx();
        $transaction               = new Transaction();
        $transaction->user_id      = $freelancer->id;
        $transaction->amount       = $bidAmount;
        $transaction->post_balance = $freelancer->balance;
        $transaction->trx_type     = '+';
        $transaction->details      = 'Reported project completed, job ' . $job->title;
        $transaction->trx          = $trx;
        $transaction->remark       = 'completed_project';
        $transaction->save();

        $finalIncome  =  $bidAmount - $chargeAmount;
        $freelancer->earning += $bidAmount;
        $freelancer->balance -= $chargeAmount;
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
        $message->message = 'COMPLETED:: Reported project completed';
        $message->conversation_id = $conversation->id;
        $message->admin_id = auth()->guard('admin')->id();
        $message->save();

        notify($freelancer, 'REPORTED_PROJECT_COMPLETED', [
            'job'      => $job->title,
            'income'   => showAmount($finalIncome),
            'charge'   => showAmount($chargeAmount),
            'buyer'   => $buyer->fullname
        ]);


        $notify[] = ['success', 'Project completed successfully'];
        return back()->withNotify($notify);
    }

    public function conversation($id, $projectId)
    {
        $pageTitle = 'Conversation';
        $project = Project::with('bid', 'job', 'user', 'buyer')->findOrFail($projectId);
        $conversation = Conversation::findOrFail($id);
        $messages = Message::where('conversation_id', $conversation->id)->with(['user', 'buyer'])->orderBy('created_at', 'ASC')->get();

        $id = $id;
        return view('admin.project.conversation', compact('pageTitle', 'conversation', 'messages', 'project', 'id'));
    }

    public function conversationStore(Request $request, $id)
    {
        $validation  = Validator::make($request->all(), [
            'message' => 'nullable',
            'message_files'          => ['nullable', 'array', 'max:10'],
            'message_files.*'        => ['nullable', 'max:2048', new FileTypeValidate(['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'pdf', 'PDF', 'docx', 'DOCX', 'doc', 'DOC'])],
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->all(),
            ]);
        }

        if (!($request->message_files) && !($request->message)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Message field is required',
            ]);
        }
        $conversation = Conversation::find($id);

        if (!$conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid conversation!',
            ]);
        }

        $data = initializePusher();

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pusher connection is required'
            ]);
        }

        if ($request->message_files) {
            foreach ($request->message_files as $message_file) {
                try {
                    $message_files[] = fileUploader($message_file, getFilePath('message'));
                } catch (\Exception $exp) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Couldn\'t upload your files'
                    ]);
                }
            }
        }

        $message = new Message();
        $message->message = $request->message;
        $message->files = $message_files ?? [];
        $message->conversation_id = $id;
        $message->admin_id = auth()->guard('admin')->id();
        $message->save();

        event(new LiveChat($message));

        return response()->json([
            'status' => 'success',
            'message' => 'Message send successfully'
        ]);
    }

    public function removeBuyerReview($id)
    {

        $review = BuyerReview::with('buyer')->findOrFail($id);
        $project =  $review->project;
        $buyer = $review->buyer;
        $review->delete();
        $buyer->avg_rating = $buyer->buyerReviews->count() > 0 ? $buyer->buyerReviews->avg('rating') : 0;
        $buyer->save();
        $project->blocked_review =  Status::YES;
        $project->save();

        $notify[] = ['success', 'Review deleted successfully'];
        return back()->withNotify($notify);
    }
    public function removeFreelancerReview($id)
    {
        $review = Review::with('user')->findOrFail($id);
        $project =  $review->project;
        $freelancer = $review->user;
        $review->delete();
        $freelancer->avg_rating = $freelancer->reviews->count() > 0 ? $freelancer->reviews->avg('rating') : 0;
        $freelancer->save();
        $project->blocked_review =  Status::YES;
        $project->save();

        $notify[] = ['success', 'Review deleted successfully'];
        return back()->withNotify($notify);
    }
}
