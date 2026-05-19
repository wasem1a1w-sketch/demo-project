<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 20, 500);
        $tax = round($subtotal * 0.08, 2);
        $shipping = $subtotal > 100 ? 0 : $this->faker->randomFloat(2, 5, 15);
        $hasDiscount = $this->faker->boolean(20);
        $discount = $hasDiscount ? round($subtotal * $this->faker->randomFloat(2, 0.05, 0.20), 2) : 0;
        $total = round($subtotal + $tax + $shipping - $discount, 2);

        $status = $this->faker->randomElement([
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ]);

        $paymentStatus = match ($status) {
            Order::STATUS_PENDING => $this->faker->randomElement([Order::PAYMENT_PENDING, Order::PAYMENT_FAILED]),
            Order::STATUS_CANCELLED => $this->faker->randomElement([Order::PAYMENT_FAILED, Order::PAYMENT_REFUNDED]),
            default => Order::PAYMENT_PAID,
        };

        return [
            'user_id' => User::factory(),
            'order_number' => Order::generateOrderNumber(),
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'payment_status' => $paymentStatus,
            'shipping_name' => $this->faker->name(),
            'shipping_address' => $this->faker->streetAddress(),
            'shipping_city' => $this->faker->city(),
            'shipping_state' => $this->faker->state(),
            'shipping_postal_code' => $this->faker->postcode(),
            'shipping_country' => 'USA',
            'shipping_phone' => $this->faker->phoneNumber(),
            'created_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
            'updated_at' => fn (array $attrs) => $attrs['created_at'],
        ];
    }
}
