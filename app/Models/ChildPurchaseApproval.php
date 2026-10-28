<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'player_profile_id', 'requested_by', 'parent_id', 'purchasable_type', 'purchasable_id', 'description',
    'amount', 'payment_type', 'status', 'auto_approved', 'requested_at', 'expires_at', 'responded_at',
    'parent_notes', 'info_requested_at', 'info_request_message',
])]
class ChildPurchaseApproval extends Model
{
    public const EXPIRES_AFTER_HOURS = 48;

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'status' => ApprovalStatus::class,
            'auto_approved' => 'boolean',
            'amount' => 'integer',
            'requested_at' => 'datetime',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'info_requested_at' => 'datetime',
        ];
    }

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }
}
