<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
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

        Category::create($validated);

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

        return back();
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();

        return back();
    }
}
