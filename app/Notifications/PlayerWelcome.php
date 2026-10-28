<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlayerWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $trainerName)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You joined {$this->trainerName}")
            ->greeting('Welcome, '.$notifiable->first_name.'!')
            ->line("Your registration with {$this->trainerName} is confirmed.")
            ->line('Please verify your email address to start using your account.')
            ->action('Go to your dashboard', route('dashboard'));
    }
}
