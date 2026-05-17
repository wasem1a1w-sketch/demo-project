<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewUserRegisteredTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_registered_notification_sends_mail(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $newUser = User::factory()->create();

        $admin->notify(new NewUserRegistered($newUser));

        Notification::assertSentTo($admin, NewUserRegistered::class, function ($notification, $channels) {
            return in_array('mail', $channels);
        });
    }
}
