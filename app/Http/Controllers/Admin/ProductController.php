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
        $sortField = $request->sort ?? 'id';
        $sortOrder = $request->order === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'name', 'price', 'stock', 'created_at', 'updated_at'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'id';
        }

        $products = Product::with(['category', 'images'])
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->orderBy($sortField, $sortOrder)
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
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            'gallery_images' => 'nullable|array|max:4',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        $product = Product::create(collect($validated)->except(['main_image', 'gallery_images'])->toArray());

        if ($request->hasFile('main_image')) {
            $this->uploadImage($product, $request->file('main_image'), isPrimary: true);
        }

        foreach ($request->file('gallery_images', []) as $galleryImage) {
            $this->uploadImage($product, $galleryImage, isPrimary: false);
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
        $product = Product::with('images')->findOrFail($id);

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
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:3072',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        $product->update(collect($validated)->except(['main_image', 'gallery_images'])->toArray());

        if ($request->hasFile('main_image')) {
            $oldPrimary = $product->images->firstWhere('is_primary', true);
            if ($oldPrimary) {
                Storage::disk('public')->delete($oldPrimary->image_path);
                $oldPrimary->delete();
            }
            $this->uploadImage($product, $request->file('main_image'), isPrimary: true);
        }

        $galleryFiles = $request->file('gallery_images', []);
        if (!empty($galleryFiles)) {
            $currentGalleryCount = $product->images()->where('is_primary', false)->count();
            $total = $currentGalleryCount + count($galleryFiles);
            if ($total > 4) {
                return back()->withErrors(['gallery_images' => 'Gallery images cannot exceed 4. Please delete existing gallery images first.']);
            }
            foreach ($galleryFiles as $galleryImage) {
                $this->uploadImage($product, $galleryImage, isPrimary: false);
            }
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

    protected function uploadImage($product, $file, bool $isPrimary = false): void
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return;
        }

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $uploadPath = public_path('uploads');

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $filename);
        $path = 'uploads/' . $filename;

        $order = $isPrimary ? 0 : $product->images()->where('is_primary', false)->count() + 1;

        $productImage = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $path,
            'is_primary' => $isPrimary,
            'order' => $order,
        ]);

        if ($isPrimary) {
            $product->update(['primary_image_id' => $productImage->id]);
        }
    }
}
