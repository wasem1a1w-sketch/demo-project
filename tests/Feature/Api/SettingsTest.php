<?php

namespace Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_default_settings(): void
    {
        Setting::set('shipping_rate', 15);
        Setting::set('free_shipping_threshold', 100);
        Setting::set('tax_rate', 10);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([
                'shipping_rate' => '15',
                'free_shipping_threshold' => '100',
                'tax_rate' => '10',
            ]);
    }

    public function test_reflects_updated_settings(): void
    {
        Setting::set('shipping_rate', 15);
        Setting::set('free_shipping_threshold', 100);
        Setting::set('tax_rate', 10);

        Setting::set('shipping_rate', 25);
        Setting::set('tax_rate', 8);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([
                'shipping_rate' => '25',
                'free_shipping_threshold' => '100',
                'tax_rate' => '8',
            ]);
    }

    public function test_returns_empty_array_when_no_settings_exist(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([]);
    }
}
