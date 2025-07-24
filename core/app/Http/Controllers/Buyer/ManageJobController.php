<?php

namespace App\Http\Controllers\Buyer;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Category;
use App\Models\Bid;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManageJobController extends Controller
{
    public function index()
    {
        $pageTitle = "Job Listing";
        $buyer = auth()->guard('buyer')->user()->loadCount('buyerReviews');
        $jobs = Job::searchable(['title'])->filter(['status'])->where('buyer_id', $buyer->id)->with(['category'])->withCount('bids')->orderByDesc('id')->paginate(getPaginate());

        return view('Template::buyer.job.index', compact('pageTitle', 'buyer', 'jobs'));
    }

    public function postForm()
    {
        $buyer = auth()->guard('buyer')->user()->loadCount('buyerReviews');
        $pageTitle = "Post Job";
        $categories = Category::active()->with(['subcategories' => function ($query) {
            $query->active();
        }])->whereHas('subcategories', function ($q) {
            $q->active()->orderBy('name');
        })->orderBy('id')->get();
        $skills = Skill::active()->get();


        $totalJobs = Job::where('buyer_id', $buyer->id)->count();
        // Buyer project assignments
        $buyerProjectAssignment = Project::where('buyer_id', $buyer->id);
        $buyerJobs = $buyerProjectAssignment->count();
        $buyerSuccessJobs = (clone $buyerProjectAssignment)->where('status', Status::PROJECT_COMPLETED)->count();
        $buyerSuccessJobPercent = ($buyerJobs > 0)  ? ($buyerSuccessJobs / $buyerJobs) * 100 : 0;

        return view('Template::buyer.job.form', compact('pageTitle', 'categories', 'buyer', 'skills', 'totalJobs', 'buyerJobs', 'buyerSuccessJobs', 'buyerSuccessJobPercent'));
    }

    public function postEdit($id)
    {
        $buyer = auth()->guard('buyer')->user()->loadCount('buyerReviews');
        $mainQuery = Job::where('buyer_id', $buyer->id)->find($id);
        $job = $mainQuery->with(['category', 'skills']);
        $totalJobs = (clone $mainQuery)->count();
        $buyerProjectAssignment = Project::where('buyer_id', $buyer->id);
        $buyerJobs = $buyerProjectAssignment->count();
        $buyerSuccessJobs = (clone $buyerProjectAssignment)->where('status', Status::PROJECT_COMPLETED)->count();
        $buyerSuccessJobPercent = ($buyerJobs > 0)  ? ($buyerSuccessJobs / $buyerJobs) * 100 : 0;

        $job = $job->findOrFail($id);

        if ($job->is_approved != 0) {
            $notify[] = ['error', 'You are not allowed to edit this job post.'];
            return back()->withNotify($notify);
        }

        $categories = Category::active()->with(['subcategories' => function ($query) {
            $query->active();
        }])->whereHas('subcategories', function ($q) {
            $q->active()->orderBy('name');
        })->orderBy('id')->get();

        $jobSkillIds = $job->skills->pluck('id')->toArray();
        $similarJobs = Job::published()
            ->approved()
            ->where('id', '!=', $job->id)
            ->where('buyer_id', '!=', $buyer->id)
            ->where('category_id', $job->category_id)
            ->where('subcategory_id', $job->subcategory_id)
            ->when(count($jobSkillIds), function ($query) use ($jobSkillIds) {
                $query->whereHas('skills', function ($skillQuery) use ($jobSkillIds) {
                    $skillQuery->whereIn('skills.id', $jobSkillIds);
                });
            })
            ->with('skills')
            ->take(5)
            ->get();

        $skills = Skill::active()->get();

        $pageTitle = "Edit Post Job";
        return view('Template::buyer.job.form', compact('pageTitle', 'buyer', 'skills', 'categories', 'job', 'totalJobs', 'buyerJobs', 'buyerSuccessJobs', 'buyerSuccessJobPercent', 'similarJobs'));
    }

    public function postStore(Request $request, $id = 0)
    {
        $request->validate([
            'title' => 'required|string',
            'slug'    => ['required',  'string', Rule::unique('jobs', 'slug')->ignore($id),],
            'category_id' => ['required', 'integer', 'gt:0', Rule::exists('categories', 'id')->where(function ($query) {
                $query->where('status', Status::YES);
            }),],
            'subcategory_id' => ['required', 'integer', 'gt:0', Rule::exists('subcategories', 'id')->where(function ($query) {
                $query->where('status', Status::YES);
            }),],
            'description' => 'required|string',
            'budget' => 'required|numeric|gt:0',
            'custom_budget' => 'required|in:0,1',
            'skill_ids' => 'required|array',
            'skill_ids.*' => 'exists:skills,id',
            'project_scope' => 'required|in:1,2,3',
            'job_longevity' => 'required|in:1,2,3,4',
            'skill_level' => 'required|in:1,2,3,4',
            'deadline' => 'required|date',
            'questions' => 'nullable|array|max:5',
            'questions.*' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $buyer = auth()->guard('buyer')->user();
       
        if ($id) {
            $job = Job::where('buyer_id', $buyer->id)->find($id);
            $notification = 'Job post updated successfully';
        } else {
            $job = new Job();
            $notification = 'Job post created successfully';
        }

        $job->buyer_id = $buyer->id;
        $job->title = $request->title;
        $job->slug = $request->slug;
        $job->category_id = $request->category_id;
        $job->subcategory_id = $request->subcategory_id;
        $job->description = $request->description;
        $job->budget = $request->budget;
        $job->custom_budget = $request->custom_budget;
        $job->project_scope = $request->project_scope;
        $job->job_longevity = $request->job_longevity;
        $job->skill_level = $request->skill_level;
        $job->deadline = $request->deadline;
        $job->questions = $request->questions;
        $job->status = $request->status;
        if ($request->status == Status::JOB_PUBLISH && gs('job_auto_approved')) {
            $job->is_approved = Status::JOB_APPROVED;
        }
        $job->save();
        $job->skills()->sync($request->skill_ids);
        $notify[] = ['success', $notification];
        return to_route('buyer.job.post.view', @$job->id)->withNotify($notify);
    }

    public function view($id)
    {
        $pageTitle = "View Job Post";
        $customSubPageTitle = 'Job Listing';
        $toRoute = route('buyer.job.post.index', );
        $buyer = auth()->guard('buyer')->user()->loadCount('buyerReviews');
        $mainQuery = Job::where('buyer_id', $buyer->id)->with('skills');
        $job = (clone $mainQuery)->with(['category'])->findOrFail($id);
     

        $totalJobs = (clone $mainQuery)->count();
        $buyerProjectAssignment = Project::where('buyer_id', $buyer->id);
        $buyerJobs = $buyerProjectAssignment->count();
        $buyerSuccessJobs = (clone $buyerProjectAssignment)->where('status', Status::PROJECT_COMPLETED)->count();
        $buyerSuccessJobPercent = ($buyerJobs > 0)  ? ($buyerSuccessJobs / $buyerJobs) * 100 : 0;

        $jobSkillIds = $job->skills->pluck('id')->toArray();
        $similarJobs = Job::published()
            ->approved()
            ->where('id', '!=', $job->id)
            ->where('buyer_id', '!=', $buyer->id)
            ->where('category_id', $job->category_id)
            ->where('subcategory_id', $job->subcategory_id)
            ->when(count($jobSkillIds), function ($query) use ($jobSkillIds) {
                $query->whereHas('skills', function ($skillQuery) use ($jobSkillIds) {
                    $skillQuery->whereIn('skills.id', $jobSkillIds);
                });
            })
            ->with('skills')
            ->take(10)
            ->get();

        return view('Template::buyer.job.view', compact('pageTitle', 'customSubPageTitle', 'toRoute','buyer', 'job', 'similarJobs', 'totalJobs', 'buyerJobs', 'buyerSuccessJobs', 'buyerSuccessJobPercent',));
    }

    public function checkSlug($id = 0)
    {

        $job = Job::where('slug', request()->slug);
        if ($id) {
            $job = $job->where('id', '<>', $id);
        }
        $exist = $job->exists();
        return response()->json([
            'exists' => $exist
        ]);
    }

    public function toggleShortlist($bidId)
    {
        $bid = Bid::findOrFail($bidId);

        if ($bid->buyer_id != auth()->guard('buyer')->id()) {
            $notify[] = 'Unauthorized';
            return responseError('validation_error', $notify);
        }


        $bid->is_shortlist = $bid->is_shortlist ? Status::NO : Status::YES;
        $bid->save();

        $notify[] = $bid->is_shortlist ? 'Bid shortlisted successfully!' : 'Bid removed from shortlist.';
        return responseSuccess('shortlisted', $notify, [
            'success' => true,
            'shortlisted' => $bid->is_shortlist,
        ]);
    }

    public function jobBids(Request $request, $id = 0)
    {
        $pageTitle = "All Bids";
        $buyer = auth()->guard('buyer')->user();
        $sortColumn = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
    
        $validSortColumns = ['id', 'updated_at', 'is_shortlist'];
        if (!in_array($sortColumn, $validSortColumns)) {
            $sortColumn = 'id';
        }
    
        $bids = Bid::with(['job', 'user', 'buyer'])
            ->when($id, fn($q) => $q->where('job_id', $id))->where('buyer_id', $buyer->id)
            ->searchable(['job:title', 'job.category:name', 'job.subcategory:name', 'user:username'])
            ->filter(['status'])->dateFilter()->orderBy($sortColumn, $sortOrder)->paginate(getPaginate());
    
        return view('Template::buyer.job.bid', compact('pageTitle', 'bids', 'sortColumn', 'sortOrder'));
    }
    
   


    public function hireTalent($bidId)
    {
        $buyer = auth()->guard('buyer')->user();
        $bid  = Bid::with(['job', 'user'])->where('id', $bidId)->where('buyer_id', $buyer->id)->where('status', Status::BID_PENDING)->firstOrFail();
        $jobTitle = $bid->job->title;
        $buyer = $bid->job->buyer;
        $freelancer = $bid->user;
        $bidAmount = $bid->bid_amount;

        $existProject = Project::where('job_id', $bid->job_id)->where('status', '!=', Status::PROJECT_REJECTED)->first();
        
        if ($existProject) {
            $notify[] = ['error', 'Invalid action! Already hired talent.'];
            return back()->withNotify($notify);
        }

        if ($buyer->balance < $bidAmount) {
            $notify[] = ['error', 'Insufficient hired balance. Please deposit the required funds to accept the bid for the hire talent.'];
            return to_route('buyer.deposit.index')->withNotify($notify);
        }

        $job = $bid->job;
        $job->status = Status::JOB_PROCESSING;
        $job->save();


        //project-assign
        $assign = new Project();
        $assign->bid_id = $bid->id;
        $assign->job_id = $bid->job_id;
        $assign->user_id = $freelancer->id;
        $assign->buyer_id = $buyer->id;
        if (gs('escrow_payment')) {
            $assign->escrow_amount = $bidAmount;
            $buyer->balance -= $bidAmount;
            $buyer->save();
        }

        $assign->status = Status::PROJECT_RUNNING;
        $assign->save();

        //Accept bid
        $bid->status = Status::BID_ACCEPTED;
        $bid->project_id = $assign->id;
        $bid->save();

        notify($freelancer, 'BID_ACCEPTED', [
            'title' =>  $jobTitle,
            'buyer' => $buyer->fullname,
            'budget_type' => $bid->job->custom_budget ? 'Customized' : 'Fixed',
            'bid_amount' => showAmount($bidAmount),
            'estimated_time' => $bid->estimated_time,
            'assigned_at' => $bid->created_at,
        ]);

        $rejectsBids = Bid::where('job_id', $bid->job_id)->where('status', Status::BID_PENDING)->get();
        //bid rejected
        foreach ($rejectsBids as $rejBid) {
            $freelancer = $rejBid->user; //freelancer
            $rejBid->status = Status::BID_REJECTED;
            $rejBid->save();

            notify($freelancer, 'BID_REJECTED', [
                'title' => $jobTitle,
                'budget_type' => $rejBid->job->custom_budget ? 'Customized' : 'Fixed',
                'bid_amount' => showAmount($rejBid->bid_amount),
            ]);
        }

        if (gs('escrow_payment') ) {
            $transaction               = new Transaction();
            $transaction->buyer_id    =  $buyer->id;
            $transaction->project_id   =  $bid->project_id;
            $transaction->amount       =  $bidAmount;
            $transaction->post_balance =  $buyer->balance;
            $transaction->trx_type     = '-';
            $transaction->details      = 'Project hold amount, job: ' . $job->title;
            $transaction->trx          = getTrx();
            $transaction->remark       = 'project_hold_amount';
            $transaction->save();
        }

        $notify[] = ['success', 'Your project has been successfully assigned!'];
        return back()->withNotify($notify);
    }
}
