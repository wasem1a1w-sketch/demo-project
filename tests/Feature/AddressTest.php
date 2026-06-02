<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_addresses(): void
    {
        $user = User::factory()->create();
        Address::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/addresses');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'addresses');
    }

    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/addresses', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'type' => 'shipping',
            'company' => '',
            'address1' => '456 Oak St',
            'address2' => '',
            'city' => 'Chicago',
            'state' => 'IL',
            'postal_code' => '60601',
            'country' => 'US',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'address1' => '456 Oak St',
            'city' => 'Chicago',
        ]);
    }

    public function test_user_can_update_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'address1' => 'Old Address',
        ]);

        $response = $this->actingAs($user)->put("/addresses/{$address->id}", [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'type' => 'shipping',
            'company' => '',
            'address1' => 'New Address',
            'address2' => '',
            'city' => 'Chicago',
            'state' => 'IL',
            'postal_code' => '60601',
            'country' => 'US',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'address1' => 'New Address',
        ]);
    }

    public function test_user_can_delete_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/addresses/{$address->id}");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

}
