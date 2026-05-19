<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(100)->create();

        foreach ($users as $user) {
            $user->assignRole('Client');

            $addressCount = fake()->numberBetween(1, 2);
            for ($i = 0; $i < $addressCount; $i++) {
                $user->addresses()->create([
                    'type' => $i === 0 ? 'shipping' : fake()->randomElement(['shipping', 'billing']),
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'address1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'postal_code' => fake()->postcode(),
                    'country' => 'USA',
                    'phone' => fake()->phoneNumber(),
                    'is_default' => $i === 0,
                ]);
            }
        }

        $this->command->info('100 clients seeded successfully!');
    }
}
