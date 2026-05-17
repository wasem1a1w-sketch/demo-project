<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe(): void
    {
        $response = $this->post('/api/newsletter/subscribe', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_guest_cannot_subscribe_with_invalid_email(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_guest_cannot_subscribe_duplicate_email(): void
    {
        Subscriber::create(['email' => 'dup@example.com']);

        $response = $this->post('/api/newsletter/subscribe', [
            'email' => 'dup@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('subscribers', 1);
    }

    public function test_guest_can_unsubscribe(): void
    {
        $subscriber = Subscriber::create(['email' => 'unsub@example.com']);

        $response = $this->post('/api/newsletter/unsubscribe', [
            'email' => 'unsub@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscribers', [
            'email' => 'unsub@example.com',
        ]);
    }
}
