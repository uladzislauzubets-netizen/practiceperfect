<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChildWantsToJoinTrainer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $childName,
        private readonly string $trainerName,
        private readonly string $url,
    ) {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->childName} wants to join {$this->trainerName}'s program")
            ->line("{$this->childName} opened a link to join {$this->trainerName}'s program.")
            ->line($this->url)
            ->action('Review Registration', $this->url);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->childName} wants to join {$this->trainerName}'s program",
            'url' => $this->url,
        ];
    }
}
