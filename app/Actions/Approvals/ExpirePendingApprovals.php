<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\ApprovalStatus;
use App\Models\ChildPurchaseApproval;
use App\Notifications\ChildApprovalExpired;

final class ExpirePendingApprovals
{
    public function handle(): int
    {
        $expired = 0;

        ChildPurchaseApproval::query()
            ->with(['parent', 'requester', 'playerProfile'])
            ->where('status', ApprovalStatus::Pending)
            ->where('expires_at', '<=', now())
            ->each(function (ChildPurchaseApproval $approval) use (&$expired) {
                $this->expireOne($approval);
                $expired++;
            });

        return $expired;
    }

    public function expireOne(ChildPurchaseApproval $approval): void
    {
        $approval->update(['status' => ApprovalStatus::Expired, 'responded_at' => now()]);
        $approval->loadMissing(['parent', 'requester']);

        $approval->parent->notify(new ChildApprovalExpired($approval));
        $approval->requester->notify(new ChildApprovalExpired($approval));
    }
}
