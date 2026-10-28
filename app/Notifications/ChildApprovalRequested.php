<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChildPurchaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChildApprovalRequested extends Notification implements ShouldQueue
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
            ->line('Amount: '.AmountFormatter::format($this->approval))
            ->line('The request expires in '.ChildPurchaseApproval::EXPIRES_AFTER_HOURS.' hours.')
            ->action('Review request', route('family.approvals.index'));
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
        return "{$this->approval->playerProfile->first_name} needs your approval: {$this->approval->description}";
    }
}
