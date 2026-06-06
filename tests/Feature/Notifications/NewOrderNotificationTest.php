<?php

namespace Tests\Feature\Notifications;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_notification_sends_mail(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = \App\Models\Order::create([
            'order_number' => 'ORD-12345',
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'subtotal' => 100.00,
            'tax' => 0.00,
            'shipping' => 0.00,
            'discount' => 0.00,
            'total' => 100.00,
            'payment_method' => 'card',
            'payment_status' => PaymentStatus::Pending,
            'shipping_name' => 'John Doe',
        ]);

        $user->notify(new NewOrderNotification($order));

        Notification::assertSentTo($user, NewOrderNotification::class, function ($notification, $channels) {
            return in_array('mail', $channels);
        });
    }
}
