<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
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
            'gallery_images' => 'nullable|array|max:4',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:3072',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($this->input('is_featured'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
