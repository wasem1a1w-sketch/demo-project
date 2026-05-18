<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(
        public User $registeredUser
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Welcome to ' . config('app.name'))
                    ->greeting('Hello ' . $this->registeredUser->name . '!')
                    ->line('Thank you for registering on our platform.')
                    ->action('Visit Our Store', url('/'))
                    ->line('We are excited to have you with us!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_user_registered',
            'user_id' => $this->registeredUser->id,
            'user_name' => $this->registeredUser->name,
            'user_email' => $this->registeredUser->email,
            'message' => "New user registered: {$this->registeredUser->name}",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'new_user_registered';
    }
}
