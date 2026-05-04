<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Latest gadgets and electronics',
            'order' => 1,
        ]);

        $clothing = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
            'description' => 'Fashionable clothing and accessories',
            'order' => 2,
        ]);

        $home = Category::create([
            'name' => 'Home & Garden',
            'slug' => 'home-garden',
            'description' => 'Items for your home and garden',
            'order' => 3,
        ]);

        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'slug' => 'wireless-bluetooth-headphones',
                'description' => 'Premium wireless headphones with active noise cancellation and 30-hour battery life. Features premium sound quality and comfortable over-ear design.',
                'short_description' => 'Premium noise-cancelling headphones',
                'price' => 149.99,
                'compare_price' => 199.99,
                'stock' => 50,
                'category_id' => $electronics->id,
                'is_featured' => true,
                'sku' => 'WBH-001',
                'weight' => 250,
            ],
            [
                'name' => 'Smart Watch Pro',
                'slug' => 'smart-watch-pro',
                'description' => 'Advanced smartwatch with health tracking, GPS, and water resistance. Track your fitness goals with precision.',
                'short_description' => 'Fitness & health tracking',
                'price' => 299.99,
                'compare_price' => 349.99,
                'stock' => 30,
                'category_id' => $electronics->id,
                'is_featured' => true,
                'sku' => 'SWP-002',
                'weight' => 50,
            ],
            [
                'name' => 'Wireless Earbuds',
                'slug' => 'wireless-earbuds',
                'description' => 'True wireless earbuds with crystal clear sound and secure fit. Perfect for workouts and daily use.',
                'short_description' => 'True wireless sound',
                'price' => 79.99,
                'stock' => 100,
                'category_id' => $electronics->id,
                'sku' => 'WE-003',
                'weight' => 30,
            ],
            [
                'name' => 'Cotton T-Shirt',
                'slug' => 'cotton-t-shirt',
                'description' => 'Premium organic cotton t-shirt. Comfortable, breathable, and eco-friendly.',
                'short_description' => '100% organic cotton',
                'price' => 24.99,
                'stock' => 200,
                'category_id' => $clothing->id,
                'is_featured' => true,
                'sku' => 'CTS-001',
                'weight' => 150,
            ],
            [
                'name' => 'Denim Jeans',
                'slug' => 'denim-jeans',
                'description' => 'Classic fit denim jeans. Premium quality denim with modern style.',
                'short_description' => 'Classic fit denim',
                'price' => 59.99,
                'compare_price' => 79.99,
                'stock' => 75,
                'category_id' => $clothing->id,
                'sku' => 'DJ-002',
                'weight' => 400,
            ],
            [
                'name' => 'Wool Sweater',
                'slug' => 'wool-sweater',
                'description' => 'Warm wool sweater. Perfect for cold weather.',
                'short_description' => 'Warm & stylish',
                'price' => 89.99,
                'stock' => 40,
                'category_id' => $clothing->id,
                'sku' => 'WS-003',
                'weight' => 300,
            ],
            [
                'name' => 'LED Desk Lamp',
                'slug' => 'led-desk-lamp',
                'description' => 'Adjustable LED desk lamp with multiple brightness levels. Perfect for reading and work.',
                'short_description' => 'Adjustable brightness',
                'price' => 39.99,
                'stock' => 60,
                'category_id' => $home->id,
                'is_featured' => true,
                'sku' => 'LDL-001',
                'weight' => 500,
            ],
            [
                'name' => 'Plant Pot Set',
                'slug' => 'plant-pot-set',
                'description' => 'Set of 3 ceramic plant pots. Modern design for your indoor plants.',
                'short_description' => 'Modern ceramic design',
                'price' => 34.99,
                'stock' => 45,
                'category_id' => $home->id,
                'sku' => 'PPS-002',
                'weight' => 800,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 50,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'SAVE20',
            'type' => 'fixed',
            'value' => 20,
            'min_order_amount' => 100,
            'max_discount_amount' => 50,
            'is_active' => true,
        ]);

        $this->command->info('Store seeded successfully!');
    }
}
