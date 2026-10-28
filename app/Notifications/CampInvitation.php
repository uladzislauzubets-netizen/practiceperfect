<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $trainerName,
        private readonly string $url,
    ) {
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
            ->subject("Create your account with {$this->trainerName}")
            ->line("Thanks for signing up with {$this->trainerName}. Create your account to see sessions and content.")
            ->line('Your details are already filled in, so it only takes a minute.')
            ->action('Create your account', $this->url);
    }
}
