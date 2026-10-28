<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChildPurchaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChildTokenSpendNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ChildPurchaseApproval $approval)
    {
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
            ->subject($this->title())
            ->line($this->title())
            ->line('This is for your information only. No action is needed.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'url' => route('family.approvals.index'),
            'approval_id' => $this->approval->id,
        ];
    }

    private function title(): string
    {
        return "{$this->approval->playerProfile->first_name} spent ".AmountFormatter::format($this->approval)." on {$this->approval->description}";
    }
}
