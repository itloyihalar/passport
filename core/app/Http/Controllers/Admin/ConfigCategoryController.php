<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Skill;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class ConfigCategoryController extends Controller
{
    public function index()
    {
        $pageTitle  = 'All Categories';
        $categories = Category::searchable(['name'])->withCount('subcategories')->withCount('jobs')->orderBy('id', 'DESC')->paginate(getPaginate());
        return view('admin.config_category.category', compact('pageTitle', 'categories'));
    }

    public function store(Request $request, $id = 0)
    {
        $imageValidation = $id ? 'nullable' : 'required';
        $request->validate(
            [
                'name'  => 'required',
                'image' => ["$imageValidation", new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            ]
        );
        if ($id) {
            $category     = Category::findOrFail($id);
            $notification = 'Category updated successfully';
        } else {
            $category     = new Category();
            $notification = 'Category added successfully';
        }

        if ($request->hasFile('image')) {
            try {
                $category->image = fileUploader($request->image, getFilePath('category'), getFileSize('category'), @$category->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload category image'];
                return back()->withNotify($notify);
            }
        }

        $category->name = $request->name;
        $category->save();
        $notify[] = ['success',  $notification];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Category::changeStatus($id);
    }

    public function feature($id)
    {
        return Category::changeStatus($id, 'is_featured');
    }


    public function subcategories($catId = 0)
    {
        $pageTitle     = 'All Subcategories';
        $categories    = Category::active()->orderBy('name')->get();
        $subcategoryCollection = Subcategory::searchable(['name'])->with('category')->withCount(['jobs' => function ($query) {
            $query->published()->approved();
        }]);
        $subcategoryCollection = $catId ? $subcategoryCollection->where('category_id', $catId) : $subcategoryCollection;
        $subcategories = $subcategoryCollection->orderBy('id', 'DESC')->paginate(getPaginate());
        return view('admin.config_category.subcategory', compact('pageTitle', 'categories', 'subcategories'));
    }

    public function subcategoryStore(Request $request, $id = 0)
    {
        $request->validate(
            [
                'category_id' => 'required|exists:categories,id',
                'name'        => 'required',
            ]
        );

        if ($id) {
            $subcategory     = Subcategory::findOrFail($id);
            $notification = 'Subcategory updated successfully';
        } else {
            $subcategory     = new Subcategory();
            $notification = 'Subcategory added successfully';
        }

        $subcategory->category_id = $request->category_id;
        $subcategory->name = $request->name;
        $subcategory->save();
        $notify[] = ['success',  $notification];
        return back()->withNotify($notify);
    }

    public function subcategoryStatus($id)
    {
        return Subcategory::changeStatus($id);
    }

    public function skills()
    {
        $pageTitle     = 'All Skills';
        $skills = Skill::searchable(['name'])->orderBy('id', 'DESC')->paginate(getPaginate());
        return view('admin.config_category.skill', compact('pageTitle', 'skills'));
    }

    public function skillStore(Request $request, $id = 0)
    {
        $request->validate(
            [
                'name'        => 'required',
            ]
        );

        if ($id) {
            $skill     = Skill::findOrFail($id);
            $notification = 'Skill updated successfully';
        } else {
            $skill     = new Skill();
            $notification = 'Skill added successfully';
        }

        $skill->name = $request->name;
        $skill->save();
        $notify[] = ['success',  $notification];
        return back()->withNotify($notify);
    }

    public function skillStatus($id)
    {
        return Skill::changeStatus($id);
    }
}
