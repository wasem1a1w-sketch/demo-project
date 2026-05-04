<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'images'])
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->orderByDesc('id')
            ->paginate(20);

        $categories = Category::orderBy('name')->get();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Admin/Products/Create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'sku' => 'nullable|string|max:100',
            'weight' => 'nullable|integer',
            'weight_unit' => 'nullable|string|max:20',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'primary_image_index' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        $product = Product::create(collect($validated)->except(['images', 'primary_image_index'])->toArray());

        $images = $request->file('images') ?? [];
        if (!empty($images)) {
            $this->handleImageUploads($product, $images, $request->input('primary_image_index', 0));
        }

        return to_route('admin.products');
    }

    public function edit($id)
    {
        $product = Product::with('images')->findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Admin/Products/Edit', ['product' => $product, 'categories' => $categories]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $product = Product::findOrFail($id);

        $request->merge([
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,'.$id,
            'description' => 'nullable',
            'short_description' => 'nullable',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'sku' => 'nullable|string|max:100',
            'weight' => 'nullable|integer',
            'weight_unit' => 'nullable|string|max:20',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'new_images' => 'nullable|array|max:10',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'primary_image_index' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        $product->update(collect($validated)->except(['images', 'new_images', 'primary_image_index'])->toArray());

        $images = $request->file('images') ?? [];
        if (!empty($images)) {
            $this->handleImageUploads($product, $images, $request->input('primary_image_index', 0));
        }

        $newImages = $request->file('new_images') ?? [];
        if (!empty($newImages)) {
            $this->handleImageUploads($product, $newImages, $request->input('primary_image_index', 0));
        }

        return to_route('admin.products');
    }

    public function destroy($id)
    {
        $product = Product::with('images')->findOrFail($id);

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return back();
    }

    protected function handleImageUploads($product, $images, $primaryIndex = 0): void
    {
        if (!is_array($images)) {
            $images = [$images];
        }

        $existingCount = $product->images()->count();

        foreach ($images as $index => $image) {
            if (!$image instanceof \Illuminate\Http\UploadedFile || !$image->isValid()) {
                continue;
            }

            $filename = uniqid() . '.' . $image->getClientOriginalExtension();
            $uploadPath = public_path('uploads');

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $image->move($uploadPath, $filename);
            $path = 'uploads/' . $filename;

            $productImage = ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => (int) $index === (int) $primaryIndex,
                'order' => $existingCount + $index,
            ]);

            if ((int) $index === (int) $primaryIndex) {
                $product->update(['primary_image_id' => $productImage->id]);
            }

            $existingCount++;
        }
    }
}
