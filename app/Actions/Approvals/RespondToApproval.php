<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\ApprovalStatus;
use App\Models\ChildPurchaseApproval;
use App\Notifications\ChildApprovalResolved;
use Illuminate\Validation\ValidationException;

final class RespondToApproval
{
    public const APPROVE = 'approve';

    public const DENY = 'deny';

    public const REQUEST_INFO = 'request_info';

    public function __construct(private readonly ExpirePendingApprovals $expire) {}

    public function handle(ChildPurchaseApproval $approval, string $decision, ?string $notes): ChildPurchaseApproval
    {
        if (! $approval->isPending()) {
            throw ValidationException::withMessages(['approval' => 'This request has already been handled.']);
        }

        if ($approval->expires_at->isPast()) {
            $this->expire->expireOne($approval);

            throw ValidationException::withMessages(['approval' => 'This request expired and was denied automatically.']);
        }

        match ($decision) {
            self::APPROVE => $approval->update(['status' => ApprovalStatus::Approved, 'responded_at' => now(), 'parent_notes' => $notes]),
            self::DENY => $approval->update(['status' => ApprovalStatus::Denied, 'responded_at' => now(), 'parent_notes' => $notes]),
            self::REQUEST_INFO => $approval->update(['info_requested_at' => now(), 'info_request_message' => $notes]),
        };

        $approval->load('requester');
        $approval->requester->notify(new ChildApprovalResolved($approval));

        return $approval;
    }
}
