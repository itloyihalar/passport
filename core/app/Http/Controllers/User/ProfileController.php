<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\User;
use App\Models\Skill;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{

    public function changePassword()
    {
        $pageTitle = 'Change Password';
        return view('Template::user.password', compact('pageTitle'));
    }

    public function submitPassword(Request $request)
    {

        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', $passwordValidation]
        ]);

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = ['success', 'Password changed successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'The password doesn\'t match!'];
            return back()->withNotify($notify);
        }
    }

    public function skill()
    {
        $pageTitle = 'Complete Your - About & Skills';
        $user = auth()->user();
        $skills = Skill::active()->get();
        return view('Template::user.profile.skill', compact('pageTitle', 'skills', 'user'));
    }

    public function submitSkills(Request $request)
    {
        $request->validate([
            'tagline' => 'required|max:255',
            'skill_ids' => 'required|array',
            'skill_ids.*' => 'exists:skills,id',
            'about' => 'required|string',
        ]);

        $user = auth()->user();
        $user->skills()->sync($request->skill_ids);
        $user->tagline = $request->tagline;
        $user->about = $request->about;

        if ($user->step < 1) {
            $user->step = 1;
        }
        $user->save();
        $notify[] = ['success', 'Skills updated successfully. Proceed to the next step.'];
        return to_route('user.profile.setting')->withNotify($notify);
    }


    public function profile()
    {
        $pageTitle = "Complete Your - Profile Setting";
        $user = auth()->user();
        if ($user->step > 0) {
            return view('Template::user.profile.setting', compact('pageTitle', 'user'));
        } else {
            return to_route('user.profile.skill');
        }
    }


    public function submitProfile(Request $request)
    {
        $imageRule = 'nullable';
        $request->validate([
            'firstname'  => 'required|string',
            'lastname'   => 'required|string',
            'language'   => 'required|array|min:1|max:10',
            'language.*' => 'nullable|string',
            'image'       => ["$imageRule", new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        $user = auth()->user();

        if ($request->hasFile('image')) {
            try {
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), @$user->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload user image'];
                return back()->withNotify($notify);
            }
        }

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->language = $request->language;

        if ($user->step < 2) {
            $user->step = 2;
        }

        $user->save();
        $notify[] = ['success', 'Basic setting updated successfully.  Proceed to the next step.'];
        return to_route('user.profile.education')->withNotify($notify);
    }

    public function education()
    {
        $pageTitle = "Complete Your - Education";
        $user = auth()->user();
        $educations = $user->educations;
        return view('Template::user.profile.education', compact('pageTitle', 'educations'));
    }


    public function submitEducations(Request $request)
    {
        $request->validate([
            'education.*.school'       => 'required|string|max:255',
            'education.*.year_from'    => 'nullable|string',
            'education.*.year_to'      => 'nullable|string',
            'education.*.degree'       => 'nullable|string|max:255',
            'education.*.area_of_study' => 'nullable|string|max:255',
            'education.*.description'  => 'nullable|string',
        ], [
            'education.*.school.required' => 'The school name is required.',
        ]);
        
        $user = auth()->user();
        $updatedIds = [];

        if (!$request->education || empty($request->education)) {
            return back()->with('error', 'Please provide your education qualifications.');
        }

        foreach ($request->education as $educationData) {
            if (!empty($educationData['id'])) {
                $education = $user->educations()->find($educationData['id']);
                if ($education) {
                    $education->school =  $educationData['school'];
                    $education->year_from =  $educationData['year_from'];
                    $education->year_to =  $educationData['year_to'];
                    $education->degree =  $educationData['degree'];
                    $education->area_of_study =  $educationData['area_of_study'];
                    $education->description =  $educationData['description'];
                    $education->save();
                    $updatedIds[] = $education->id;
                }
            } else {
                $education = new Education();
                $education->user_id =   $user->id;
                $education->school =  $educationData['school'];
                $education->year_from =  $educationData['year_from'];
                $education->year_to =  $educationData['year_to'];
                $education->degree =  $educationData['degree'];
                $education->area_of_study =  $educationData['area_of_study'];
                $education->description =  $educationData['description'];
                $education->save();
                $updatedIds[] = $education->id;
            }
        }

        $user->educations()->whereNotIn('id', $updatedIds)->delete();

        if ($user->step < 3) {
            $user->step = 3;
            $user->save();
        }

        return redirect()->route('user.profile.portfolio')->with('success', 'Education updated successfully.');
    }






    public function portfolio()
    {
        $pageTitle = "Complete Your - Portfolios";
        $user = auth()->user();
        $portfolios = $user->portfolios()->paginate(getPaginate());
        $skills = Skill::active()->get();
        return view('Template::user.profile.portfolio', compact('pageTitle', 'user', 'portfolios', 'skills'));
    }

    public function submitPortfolios(Request $request, $id = 0)
    {
        $user = auth()->user();
        $imageRule = $id ? 'nullable' : 'required';

        $request->validate([
            'title'        => 'required|string',
            'role'         => 'nullable|string',
            'description'  => 'required|string',
            'skill_ids'   => 'required|array',
            'skill_ids.*' => 'exists:skills,id',
            'image'       => ["$imageRule", new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($id) {
            $portfolio = Portfolio::where('user_id', $user->id)->findOrFail($id);
            $notification = 'Portfolio updated successfully';
        } else {
            $portfolio = new Portfolio();
            $notification = 'Portfolio added successfully';
        }

        if ($request->hasFile('image')) {
            try {
                $portfolio->image = fileUploader($request->image, getFilePath('portfolio'), getFileSize('portfolio'), @$portfolio->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload portfolio image'];
                return back()->withNotify($notify);
            }
        }

        $portfolio->user_id     = $user->id;
        $portfolio->title       = $request->title;
        $portfolio->role        = $request->role;
        $portfolio->description = $request->description;
        $portfolio->skill_ids   = $request->skill_ids;
        $portfolio->save();


        if ($user->step < 4) {
            $user->step = 4;
            $user->save();
        }

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function statusPortfolio($id)
    {
        return Portfolio::changeStatus($id);
    }

    public function workProfileComplete()
    {
        $user = auth()->user();
        $id  = $user->id;
        return User::changeStatus($id, 'work_profile_complete');
    }
}
