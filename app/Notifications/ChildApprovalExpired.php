<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChildPurchaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChildApprovalExpired extends Notification implements ShouldQueue
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
            ->line('Nobody responded within '.ChildPurchaseApproval::EXPIRES_AFTER_HOURS.' hours, so it was denied automatically.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'url' => $notifiable->id === $this->approval->parent_id ? route('family.approvals.index') : route('purchase-requests.index'),
            'approval_id' => $this->approval->id,
        ];
    }

    private function title(): string
    {
        return "The request \"{$this->approval->description}\" expired";
    }
}
