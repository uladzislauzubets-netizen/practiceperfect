<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ShareLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoachInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $trainerName,
        private readonly string $url,
        private readonly ?string $inviteeName,
        private readonly ?string $personalMessage,
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
        $message = (new MailMessage)
            ->subject("{$this->trainerName} invited you to coach")
            ->greeting($this->inviteeName !== null ? "Hi {$this->inviteeName}," : 'Hello,')
            ->line("{$this->trainerName} invited you to join their organization as a coach.");

        if ($this->personalMessage !== null && $this->personalMessage !== '') {
            $message->line('Message from the trainer:')->line($this->personalMessage);
        }

        return $message
            ->action('Accept invitation', $this->url)
            ->line('This invitation can be used once and expires in '.ShareLink::COACH_LINK_VALID_DAYS.' days.');
    }
}
