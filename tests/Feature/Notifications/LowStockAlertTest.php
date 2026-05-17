<?php

namespace Tests\Feature\Notifications;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_notification_sends_mail(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $product = Product::factory()->create(['stock' => 2]);

        $admin->notify(new LowStockAlert($product));

        Notification::assertSentTo($admin, LowStockAlert::class, function ($notification, $channels) {
            return in_array('mail', $channels);
        });
    }
}
