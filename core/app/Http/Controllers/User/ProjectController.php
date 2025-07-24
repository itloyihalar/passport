<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BuyerReview;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $pageTitle  = 'My Projects';
        $freelancer = auth()->user();
        $projects   = Project::searchable(['job:title', 'buyer:username'])->filter(['status'])->dateFilter()->orderBy('id', 'desc')->where('user_id', $freelancer->id)->with(['bid.job', 'user', 'buyer'])->paginate(getPaginate());
        return view('Template::user.project.index', compact('pageTitle', 'projects'));
    }

    public function detail($id)
    {
        $pageTitle = 'Project Details';
        $project   = Project::with(['job', 'bid', 'user', 'buyer', 'review', 'buyerReview'])->where('id', $id)->firstOrFail();
        return view('Template::user.project.detail', compact('pageTitle', 'project'));
    }

    public function projectUploadForm($id)
    {
        $pageTitle  = 'Upload Assigned Project';
        $freelancer = auth()->user();
        $mainQuery  = Project::query();
        $project    = (clone $mainQuery)->where('user_id', $freelancer->id)->with('bid')->findOrFail($id);

        //Buyer project assignments
        $buyer                  = $project->buyer;
        $buyerProjectAssignment = (clone $mainQuery)->where('buyer_id', $project->buyer_id);
        $buyerJobs              = $buyerProjectAssignment->count();
        $buyerSuccessJobs       = (clone $buyerProjectAssignment)->where('status', Status::PROJECT_COMPLETED)->count();
        $buyerSuccessJobPercent = ($buyerJobs > 0) ? ($buyerSuccessJobs / $buyerJobs) * 100 : 0;

        return view('Template::user.project.upload', compact('pageTitle', 'project', 'buyer', 'buyerJobs', 'buyerSuccessJobs', 'buyerSuccessJobPercent'));
    }

    public function projectUpload(Request $request, $id)
    {
        $project = Project::where('status','!=', Status::PROJECT_COMPLETED)->findOrFail($id);
        if (auth()->user()->id !== $project->user_id) {
            $notify[] = ['error', 'You are not authorized to upload for this project!'];
            return back()->withNotify($notify);
        }
        $allowedExtension = ['zip', 'rar', 'pdf', 'doc', 'docx', 'xls', 'xlsx', '7zip'];
        $request->validate([
            'comments'     => 'nullable|string',
            'project_file' => [
                'required',
                'file',
                function ($attribute, $value, $fail) use ($request, $allowedExtension) {
                    $projectFile = $request->file('project_file');
                    if ($projectFile) {
                        $ext = strtolower($projectFile->getClientOriginalExtension());
                        if (!in_array($ext, $allowedExtension)) {
                            $fail("Only " . implode(', ', $allowedExtension) . " files are allowed.");
                        }
                    } else {
                        $fail("The file is invalid or missing.");
                    }
                },
            ],
        ]);

        if ($request->file('project_file')) {
            try {
                $old                   = basename($project->project_file) ?? '';
                $formProjectFile       = $request->file('project_file');
                $directory             = date("Y") . "/" . date("m") . "/" . date("d");
                $uploadPath            = getFilePath('projectFile') . '/' . $directory;
                $file                  = $directory . '/' . fileUploader($formProjectFile, $uploadPath, null, $old);
                $project->project_file = $file;
            } catch (\Exception $exp) {
                $notify[] = ['error', 'File could not upload'];
                return $notify;
            }
        }

        $project->comments    = @$request->comments;
        $project->status      = Status::PROJECT_BUYER_REVIEW;
        $project->uploaded_at = now();
        $project->uploaded_at = now();
        $project->upload_count += 1;
        $project->save();

        notify($project->buyer, 'PROJECT_BUYER_REVIEW', [
            'freelancer' => $project->user->fullname,
            'job'        => $project->job->title,
            'comments'   => $project->comments,
            'link'       => route('buyer.project.detail', $project->id),
        ]);

        $notify[] = ['success', 'Project file uploaded successfully for buyer review.'];
        return to_route('user.project.index')->withNotify($notify);
    }

    public function downloadFile($id, $file)
    {
        $freelancer = auth()->user();
        $project    = Project::where('id', $id)->where('user_id', $freelancer->id)->with('job')->first();

        if (!$project) {
            $notify[] = ['error', 'Project not found!'];
            return back()->withNotify($notify);
        }
        $path = getFilePath('projectFile');
        $file = decrypt($file);

        $full_path = $path . '/' . $file;
        $title     = slug(substr($project->job->title, 0, 20));
        $ext       = pathinfo($file, PATHINFO_EXTENSION);
        $mimetype  = mime_content_type($full_path);
        header('Content-Disposition: attachment; filename="' . $title . '.' . $ext . '";');
        header("Content-Type: " . $mimetype);
        return readfile($full_path);
    }

    public function storeReviewRating(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
        ]);

        $freelancer = auth()->user();
        $project    = Project::completed()->where('user_id', $freelancer->id)->findOrFail($id);
        $buyer      = $project->buyer;

        $review = BuyerReview::where('project_id', $id)->where('user_id', $freelancer->id)->first();

        if ($review) {
            $notify[] = ['success', 'Review & rating updated successfully'];
        } else {
            $review             = new BuyerReview();
            $review->buyer_id   = $project->buyer_id;
            $review->user_id    = $freelancer->id;
            $review->project_id = $project->id;
            $notify[]           = ['success', 'Review & rating added successfully'];
        }

        $review->rating = $request->rating;
        $review->review = $request->review;
        $review->save();

        $buyer->avg_rating = BuyerReview::where('buyer_id', $buyer->id)->avg('rating') ?? 0;
        $buyer->save();

        return back()->withNotify($notify);
    }
}
