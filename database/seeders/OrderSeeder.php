<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $clientIds = User::role('client')->pluck('id')->toArray();
        $products = Product::all();

        if (empty($clientIds)) {
            $this->command->error('No clients found. Run ClientSeeder first.');

            return;
        }

        if ($products->isEmpty()) {
            $this->command->error('No products found. Run StoreSeeder first.');

            return;
        }

        $statuses = [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
        $statusWeights = [20, 25, 25, 20, 10];
        $statusPool = [];
        foreach ($statuses as $i => $status) {
            $statusPool = array_merge($statusPool, array_fill(0, $statusWeights[$i], $status));
        }

        $bar = $this->command->getOutput()->createProgressBar(500);
        $bar->start();

        for ($i = 0; $i < 500; $i++) {
            $userId = $clientIds[array_rand($clientIds)];
            $itemCount = rand(1, 5);

            $productsForOrder = $products->random($itemCount);
            $itemSubtotal = 0;
            $items = [];

            foreach ($productsForOrder as $product) {
                $quantity = rand(1, 5);
                $price = $product->price;
                $subtotal = round($price * $quantity, 2);
                $itemSubtotal += $subtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            $tax = round($itemSubtotal * 0.08, 2);
            $shipping = $itemSubtotal > 100 ? 0 : round(rand(500, 1500) / 100, 2);
            $discount = 0;
            $total = round($itemSubtotal + $tax + $shipping - $discount, 2);

            $status = $statusPool[array_rand($statusPool)];

            $paymentStatus = match ($status) {
                Order::STATUS_PENDING => rand(0, 2) ? Order::PAYMENT_PENDING : Order::PAYMENT_FAILED,
                Order::STATUS_CANCELLED => rand(0, 1) ? Order::PAYMENT_FAILED : Order::PAYMENT_REFUNDED,
                default => Order::PAYMENT_PAID,
            };

            $createdAt = fake()->dateTimeBetween('-60 days', 'now');

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => Order::generateOrderNumber(),
                'status' => $status,
                'subtotal' => $itemSubtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'payment_status' => $paymentStatus,
                'shipping_name' => fake()->name(),
                'shipping_address' => fake()->streetAddress(),
                'shipping_city' => fake()->city(),
                'shipping_state' => fake()->state(),
                'shipping_postal_code' => fake()->postcode(),
                'shipping_country' => 'USA',
                'shipping_phone' => fake()->phoneNumber(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($items as $itemData) {
                $order->items()->create($itemData);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info('500 orders seeded successfully!');
    }
}
