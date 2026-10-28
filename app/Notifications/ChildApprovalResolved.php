<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChildPurchaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChildApprovalResolved extends Notification implements ShouldQueue
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
        $message = (new MailMessage)->subject($this->title())->line($this->title());

        if ($this->approval->parent_notes !== null) {
            $message->line('Note from your parent: '.$this->approval->parent_notes);
        }

        if ($this->approval->info_request_message !== null && $this->approval->isPending()) {
            $message->line('Question from your parent: '.$this->approval->info_request_message);
        }

        return $message->action('View your requests', route('purchase-requests.index'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'url' => route('purchase-requests.index'),
            'approval_id' => $this->approval->id,
        ];
    }

    private function title(): string
    {
        $outcome = $this->approval->isPending()
            ? 'needs more information'
            : strtolower($this->approval->status->label());

        return "Your request \"{$this->approval->description}\" {$outcome}";
    }
}
