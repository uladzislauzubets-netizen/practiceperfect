<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShareLinkState;
use App\Enums\ShareLinkType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'code', 'type', 'trainer_id', 'created_by', 'target_email', 'invitee_name', 'message',
    'expires_at', 'max_uses', 'uses_count', 'is_active',
])]
class ShareLink extends Model
{
    public const COACH_LINK_VALID_DAYS = 7;

    protected function casts(): array
    {
        return [
            'type' => ShareLinkType::class,
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => route('join.show', $this->code));
    }

    public static function newCode(): string
    {
        do {
            $code = Str::lower(Str::random(10));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public static function findByCode(string $code): ?self
    {
        return self::query()->with('trainer.trainerProfile')->where('code', Str::lower($code))->first();
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function uses(): HasMany
    {
        return $this->hasMany(ShareLinkUse::class);
    }

    public function state(): ShareLinkState
    {
        return match (true) {
            ! $this->is_active => ShareLinkState::Inactive,
            ! $this->trainer->isActive() => ShareLinkState::TrainerUnavailable,
            $this->expires_at !== null && $this->expires_at->isPast() => ShareLinkState::Expired,
            $this->max_uses !== null && $this->uses_count >= $this->max_uses => ShareLinkState::Exhausted,
            default => ShareLinkState::Valid,
        };
    }

    public function isUsable(): bool
    {
        return $this->state() === ShareLinkState::Valid;
    }

    public function invitationStatus(): string
    {
        return match (true) {
            $this->uses_count > 0 => 'accepted',
            $this->state() === ShareLinkState::Valid => 'pending',
            default => 'expired',
        };
    }
}
