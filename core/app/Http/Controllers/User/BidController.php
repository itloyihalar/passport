<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Job;
use App\Models\Project;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class BidController extends Controller
{
    public function index()
    {
        $pageTitle  = 'Bid List';
        $freelancer = auth()->user();
        $bids       = Bid::searchable(['job:title'])->where('user_id', $freelancer->id)->with(['job', 'buyer', 'project'])->orderBy('id', 'DESC')->paginate(getPaginate());
        return view('Template::user.bid.index', compact('pageTitle', 'bids'));
    }

    public function storeBid(Request $request, $id)
    {
        $job = Job::published()->approved()->whereDate('deadline', '>', now())->with(['buyer' => function ($q) {
            $q->active();
        }])->findOrFail($id);

        $freelancer = auth()->user();
        if (!$freelancer->work_profile_complete) {
            $notify[] = ['error', 'Please complete your profile first!'];
            return to_route('user.profile.setting')->withNotify($notify);
        }
        $isJobBidExisting = $freelancer->bids()->where('job_id', $job->id)->count();
        if ($isJobBidExisting) {
            $notify[] = ['error', 'You have already placed a bid on this job. Duplicate bids are not allowed!'];
            return back()->withNotify($notify);
        }

        $budgetRule = $job->custom_budget ? 'required' : 'nullable';
        $request->validate([
            'bid_amount'     => [$budgetRule, 'numeric', 'gt:0'],
            'estimated_time' => 'required|string|max:40',
            'bid_quote'      => 'required|string',
        ], [
            'estimated_time.max' => 'Estimated time text can\'t be more than 40 characters',
        ]);

        $buyer              = $job->buyer;
        $bid                 = new Bid();
        $bid->job_id         = $job->id;
        $bid->user_id        = $freelancer->id;
        $bid->buyer_id      = $buyer->id;
        $bid->bid_amount     = $request->bid_amount ?? $job->budget;
        $bid->estimated_time = $request->estimated_time;
        $bid->bid_quote      = $request->bid_quote;
        $bid->save();

        $bidAmount = $job->custom_budget ? $request->bid_amount : $job->budget;

        notify($buyer, 'BID_PLACED', [
            'title'       => $job->title,
            'freelancer'  => $freelancer->fullname,
            'budget_type' => $job->custom_budget ? 'Customized' : 'Fixed',
            'amount'      => showAmount($bidAmount),
            'estimate'    => $bid->estimated_time,
            'bid_text'    => $bid->bid_quote,
        ]);

        $notify[] = ['success', 'Your bid has been successfully placed for the job!'];
        return to_route('user.bid.index')->withNotify($notify);
    }

    public function withdrawBid($id)
    {
        $bid = Bid::where('id', $id)->where('status', Status::BID_PENDING)->where('user_id', auth()->id())->with(['job', 'buyer', 'user'])->firstOrFail();
        if ($bid) {
            $bid->status = Status::BID_WITHDRAW;
            $bid->save();

            notify($bid->buyer, 'BID_WITHDRAW', [
                'freelancer' => $bid->user->fullname,
                'job'        => $bid->job->title,
            ]);
            $notify[] = ['success', 'Your bid has been successfully withdrawn.'];
        } else {
            $notify[] = ['error' => 'Invalid bid!'];
        }
        return back()->withNotify($notify);
    }

    public function assignProject($id)
    {
        $freelancer = auth()->user();
        $assignment = Project::where('user_id', $freelancer->id)->where('status', Status::PROJECT_RUNNING)->with('bid')->findOrFail($id);
        $pageTitle  = 'Upload Assign Project';
        return view('Template::user.project.upload', compact('pageTitle', 'assignment'));
    }

    public function projectUpload(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        if (auth()->user()->id !== $project->user_id) {
            $notify[] = ['error', 'You are not authorized to upload for this project!'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'project_file' => ['file', new FileTypeValidate(['jpg', 'jpeg', 'png', 'zip', 'rar', 'pdf', '3gp', 'mpeg3', 'x-mpeg-3', 'mp4', 'mpeg', 'mpkg', 'doc', 'docx', 'gif', 'txt', 'svg', 'wav', 'xls', 'xlsx', '7z'])],
            'comments'     => 'nullable|string',
        ]);

        if ($request->file('project_file')) {
            try {
                $project_file          = $request->file('project_file');
                $directory             = date("Y") . "/" . date("m") . "/" . date("d");
                $uploadPath            = getFilePath('projectFile') . '/' . $directory;
                $file                  = $directory . '/' . fileUploader($project_file, $uploadPath);
                $project->project_file = $file;
            } catch (\Exception $exp) {
                $notify[] = ['error', 'File could not upload'];
                return $notify;
            }
        }
        $project->comments = @$request->comments;
        $project->status   = Status::PROJECT_BUYER_REVIEW;
        $project->save();

        $notify[] = ['success', 'Project file uploaded successfully for buyer review.'];
        return to_route('user.bid.index')->withNotify($notify);
    }
}
