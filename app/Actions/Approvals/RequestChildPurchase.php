<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentType;
use App\Models\ChildPurchaseApproval;
use App\Models\User;
use App\Notifications\ChildApprovalRequested;
use App\Notifications\ChildTokenSpendNotice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class RequestChildPurchase
{
    /** @param  array{description: string, amount: int, payment_type: string}  $data */
    public function handle(User $child, array $data, ?Model $purchasable = null): ChildPurchaseApproval
    {
        $profile = $child->playerProfile;
        $parent = $child->parent;

        if (! $child->isChildAccount() || $profile === null || $parent === null) {
            throw ValidationException::withMessages(['child' => 'Only child accounts need parent approval.']);
        }

        $paymentType = PaymentType::from($data['payment_type']);
        $autoApproved = $paymentType === PaymentType::Tokens && $profile->allow_token_spending_without_approval;

        $approval = ChildPurchaseApproval::query()->create([
            'player_profile_id' => $profile->id,
            'requested_by' => $child->id,
            'parent_id' => $parent->id,
            'purchasable_type' => $purchasable?->getMorphClass(),
            'purchasable_id' => $purchasable?->getKey(),
            'description' => $data['description'],
            'amount' => $data['amount'],
            'payment_type' => $paymentType,
            'status' => $autoApproved ? ApprovalStatus::Approved : ApprovalStatus::Pending,
            'auto_approved' => $autoApproved,
            'requested_at' => now(),
            'expires_at' => now()->addHours(ChildPurchaseApproval::EXPIRES_AFTER_HOURS),
            'responded_at' => $autoApproved ? now() : null,
        ]);

        $approval->load('playerProfile');
        $parent->notify($autoApproved ? new ChildTokenSpendNotice($approval) : new ChildApprovalRequested($approval));

        return $approval;
    }
}
