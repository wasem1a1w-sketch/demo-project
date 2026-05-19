<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([Address::TYPE_SHIPPING, Address::TYPE_BILLING]),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address1' => $this->faker->streetAddress(),
            'address2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'USA',
            'phone' => $this->faker->phoneNumber(),
            'is_default' => false,
        ];
    }
}
