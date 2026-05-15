<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 200),
            'compare_price' => fake()->optional(0.3)->randomFloat(2, 200, 300),
            'stock' => fake()->numberBetween(0, 100),
            'category_id' => Category::factory(),
            'is_active' => true,
            'is_featured' => false,
            'sku' => strtoupper(Str::random(8)),
        ];
    }
}
