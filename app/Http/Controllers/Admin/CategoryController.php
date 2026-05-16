<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories',
            'description' => 'nullable',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $category = Category::create($validated);

        UserActivityLog::record(auth()->id(), 'category_created', "Category created: {$category->name}");

        return back();
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,'.$id,
            'description' => 'nullable',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $category->update($validated);

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
