<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\UserActivityLog;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent', 'children', 'products')
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/Categories/Index', ['categories' => $categories]);
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        UserActivityLog::record(auth()->id(), 'category_created', "Category created: {$category->name}");

        return back();
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        UserActivityLog::record(auth()->id(), 'category_updated', "Category updated: {$category->name}");

        return back();
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        UserActivityLog::record(auth()->id(), 'category_deleted', "Category deleted: {$category->name}");
        $category->delete();

        return back();
    }
}
