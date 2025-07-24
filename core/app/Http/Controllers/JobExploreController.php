<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\Category;
use App\Models\Buyer;
use App\Models\Job;
use App\Models\Page;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\Request;

class JobExploreController extends Controller
{
    protected function jobQuery()
    {
        return Job::published()->approved()->whereDate('deadline', '>', now())->whereHas('buyer', fn($query) => $query->active())->whereHas('category', fn($query) => $query->active());
    }

    public function freelanceJobs()
    {
        $pageTitle = 'Freelance Job';
        $sections = Page::where('tempname', activeTemplate())->where('slug', 'freelance-jobs')->firstOrFail();
        $seoContents = $sections->seo_content;
        $seoImage = !empty($seoContents->image) ? getImage(getFilePath('seo') . '/' . $seoContents->image, getFileSize('seo')) : null;
        $jobsQuery = $this->jobQuery()->withCount('bids', 'skills');
        $buyerUsername = request()->buyer;
        if ($buyerUsername) {
            $buyer = Buyer::where('username', $buyerUsername)->first();
            if ($buyer) {
                $jobsQuery->where('buyer_id', $buyer->id);
            }
        }
        $jobs = $jobsQuery->searchable(['title'])->filter(['category_id'])->paginate(getPaginate());
        $categories = Category::active()->withCount(['jobs' => fn($query) => $query->published()->approved()])->whereHas('jobs', fn($query) => $query->published()->approved())->get();
        $subcategories = Subcategory::active()->withCount(['jobs' => fn($query) => $query->published()->approved()])->whereHas('category', fn($q) => $q->active())->whereHas('jobs', fn($query) => $query->published()->approved())->get();
        $counting = $this->getJobCounts();
        return view('Template::job_explore.jobs', compact('pageTitle', 'sections', 'seoContents', 'seoImage', 'jobs', 'categories', 'subcategories', 'counting'));
    }

    private function getJobCounts()
    {
        $baseQuery = $this->jobQuery();
        return [
            'large'       => (clone $baseQuery)->where('project_scope', Status::SCOPE_LARGE)->count(),
            'medium'      => (clone $baseQuery)->where('project_scope', Status::SCOPE_MEDIUM)->count(),
            'small'       => (clone $baseQuery)->where('project_scope', Status::SCOPE_SMALL)->count(),

            'pro'         => (clone $baseQuery)->where('skill_level', Status::SKILL_PRO)->count(),
            'expert'      => (clone $baseQuery)->where('skill_level', Status::SKILL_EXPERT)->count(),
            'intermediate' => (clone $baseQuery)->where('skill_level', Status::SKILL_INTERMEDIATE)->count(),
            'entry'       => (clone $baseQuery)->where('skill_level', Status::SKILL_ENTRY)->count(),
        ];
    }

    public function filterJobs(Request $request)
    {
        $jobs = $this->jobQuery()->searchable(['title', 'budget'])
            ->when(
                $request->category_id,
                fn($query) =>
                $query->where('category_id', $request->category_id)
            )
            ->when(
                $request->subcategory_id,
                fn($query) =>
                $query->whereIn('subcategory_id', $request->subcategory_id)
            )
            ->when(
                $request->project_scope,
                fn($query) =>
                $query->whereIn('project_scope', $request->project_scope)
            )
            ->when(
                $request->skill_level,
                fn($query) =>
                $query->whereIn('skill_level', $request->skill_level)
            );

        if ($request->min_budget || $request->max_budget) {
            $minBudget = (float) $request->min_budget;
            $maxBudget = (float) $request->max_budget;
            $jobs->where(function ($query) use ($minBudget, $maxBudget) {
                $query->whereBetween('budget', [$minBudget, $maxBudget]);
            });
        }
        $jobs = $jobs->withCount('bids', 'skills')->paginate(getPaginate());
        $notify[] = 'Get freelance jobs successfully';
        $view = view('Template::job_explore.job', compact('jobs'))->render();

        return responseSuccess('freelance_jobs', $notify, [
            'html' => $view
        ]);
    }

    public function exploreJob($slug)
    {
        $pageTitle = 'Explore';
        $customSubPageTitle = 'Freelance Job';
        $toRoute = route('freelance.jobs');

        $job =  $this->jobQuery()->withCount('bids')->where('slug', $slug)->firstOrFail();

        // Calculate buyer job success rate
        $buyerJobs = Project::where('buyer_id', $job->buyer_id);
        $buyerSuccessJobs = $buyerJobs->where('status', Status::PROJECT_COMPLETED)->count();
        $buyerSuccessJobPercent = $buyerJobs->count() > 0 ? ($buyerJobs->where('status', Status::PROJECT_COMPLETED)->count() / $buyerJobs->count()) * 100 : 0;

        // Get similar jobs
        $jobSkillIds = $job->skills->pluck('id');
        $similarJobsQuery = Job::published()->approved()->where('slug', '!=', $job->slug)->where('category_id', $job->category_id)->where('subcategory_id', $job->subcategory_id)
            ->whereHas('buyer', fn($query) => $query->active())->whereHas('category', fn($query) => $query->active())->when(
                $jobSkillIds->isNotEmpty(),
                fn($query) =>
                $query->whereHas(
                    'skills',
                    fn($skillQuery) => $skillQuery->active()->whereIn('skills.id', $jobSkillIds)
                )
            )->with('skills');

        $similarJobs = $similarJobsQuery->take(5)->get();
        $totalSimilarJobs = $similarJobsQuery->count();
        $biddenFreelancers = $job->bids()->pending()
            ->with(['user.projects', 'user.badge'])
            ->with(['user' => function ($query) {
                $query->withCount('reviews as reviews_count');
            }])
            ->orderByDesc('id')
            ->take(5)
            ->get()
            ->pluck('user');

        $totalBiddenFreelancers = $job->bids()->count();

        // Get top skills
        $topSkills = $this->jobQuery()->withWhereHas('skills', fn($q) => $q->active())->get()
            ->pluck('skills')->flatten()->countBy('id')->sortDesc()->take(5)
            ->mapWithKeys(function ($count, $skillId) {
                $skill = Skill::active()->find($skillId);
                return [
                    $skillId => [
                        'id' => $skillId,
                        'name' => $skill->name ?? 'Unknown',
                        'count' => $count,
                    ]
                ];
            });

        return view('Template::job_explore.details', compact('pageTitle', 'customSubPageTitle', 'toRoute',  'job', 'totalSimilarJobs', 'similarJobs', 'totalBiddenFreelancers', 'biddenFreelancers', 'topSkills', 'buyerSuccessJobs', 'buyerSuccessJobPercent'));
    }


    public function getSimilarFreelancers(Request $request)
    {
        $offset = $request->offset ?? 5;
        $limit = $request->limit ?? 5;
        $job = Job::findOrFail($request->job_id);

        $biddenFreelancersQuery = $job->bids()->pending()
            ->with(['user.projects', 'user.badge'])
            ->with(['user' => function ($query) {
                $query->withCount('reviews as reviews_count');
            }])
            ->orderByDesc('id');


        $totalBiddenFreelancers = $biddenFreelancersQuery->count();
        $biddenFreelancers = $biddenFreelancersQuery->skip($offset)->take($limit)->get()->pluck('user');
        $nextOffset = ($offset + $limit) < $totalBiddenFreelancers ? $offset + $limit : null;

        $view = view('Template::job_explore.freelancer', ['similarFreelancers' => $biddenFreelancers])->render();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully fetched freelancers',
            'data' => [
                'html' => $view,
                'next_offset' => $nextOffset,
                'total' => $totalBiddenFreelancers,
            ]
        ]);
    }

    public function getSimilarJobs(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 5;
        $jobSkillIds = is_array($request->job_skill_ids) ? $request->job_skill_ids : [];

        $jobQuery = $this->jobQuery()->when(!empty($jobSkillIds), function ($query) use ($jobSkillIds) {
            $query->where(function ($subQuery) use ($jobSkillIds) {
                foreach ($jobSkillIds as $skillId) {
                    $subQuery->orWhereJsonContains('skill_ids', $skillId);
                }
            });
        });

        $totalSimilarJobs = (clone $jobQuery)->count();
        $similarJobs = $jobQuery->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();

        $nextOffset = ($offset + $limit) < $totalSimilarJobs ? $offset + $limit : null;

        $view = view('Template::job_explore.similar_job', compact('similarJobs'))->render();

        return responseSuccess('similar_jobs', 'Successfully fetched jobs', [
            'html' => $view,
            'next_offset' => $nextOffset,
            'total' => $totalSimilarJobs,
        ]);
    }



    public function exploreFreelancer($slug)
    {

        $pageTitle = 'Frilanser profili';
        $customSubPageTitle = 'Iste’dodli frilanserlar';
        $toRoute = route('all.freelancers', );

        $freelancer = User::active()
            ->where('username', $slug)
            ->with(['skills', 'badge', 'projects' => function ($q) {
                $q->select('id', 'user_id', 'status');
            }])
            ->firstOrFail();

        $freelancerSkills = $freelancer->skills;
        $skillIds = $freelancerSkills->pluck('id')->toArray();
        $similarFreelancers = User::active()
            ->where('username', '!=', $slug)
            ->whereHas('skills', function ($query) use ($skillIds) {
                $query->whereIn('skills.id', $skillIds);
            })
            ->with('badge')
            ->orderByDesc('users.avg_rating')
            ->inRandomOrder()
            ->take(9)
            ->get();

        $topSkills = Skill::active()
            ->whereHas('jobs', function ($q) {
                $q->whereHas('skills', fn($sq) => $sq->active());
            })
            ->get()
            ->countBy('id')
            ->sortDesc()
            ->take(5)
            ->mapWithKeys(function ($count, $skillId) {
                $skill = Skill::find($skillId);
                return [
                    $skillId => [
                        'id' => $skillId,
                        'name' => $skill->name ?? 'Unknown',
                        'count' => $count,
                    ]
                ];
            });

        $totalJobs = $freelancer->projects->count();
        $successfulJobs = $freelancer->projects->where('status', Status::PROJECT_COMPLETED)->count();
        $freelancerSuccessJobPercent = $totalJobs > 0 ? ($successfulJobs / $totalJobs) * 100 : 0;
        $freelancersReviews = $freelancer->reviews()->paginate(getPaginate());
        return view('Template::freelancer_explore', compact('pageTitle', 'customSubPageTitle', 'toRoute', 'freelancer', 'freelancerSkills', 'similarFreelancers', 'successfulJobs', 'freelancerSuccessJobPercent','topSkills','freelancersReviews'
        ));
    }
}
