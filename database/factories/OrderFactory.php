<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'subtotal' => 100.00,
            'tax' => 10.00,
            'shipping' => 15.00,
            'discount' => 0.00,
            'total' => 125.00,
            'payment_status' => 'unpaid',
            'shipping_name' => $this->faker->name,
            'shipping_address' => $this->faker->streetAddress,
            'shipping_city' => $this->faker->city,
            'shipping_state' => $this->faker->state,
            'shipping_postal_code' => $this->faker->postcode,
            'shipping_country' => 'USA',
            'shipping_phone' => $this->faker->phoneNumber,
        ];
    }
}
