<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'user_id', 'parent_id', 'first_name', 'last_name', 'birth_date', 'gender', 'skill_level', 'school',
    'jersey_number', 'is_child', 'photo_path', 'photo_thumb_path', 'allow_token_spending_without_approval',
])]
class PlayerProfile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'is_child' => 'boolean',
            'allow_token_spending_without_approval' => 'boolean',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->birth_date?->age);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function associations(): HasMany
    {
        return $this->hasMany(TrainerPlayer::class);
    }

    public function activeAssociations(): HasMany
    {
        return $this->associations()->where('status', ActiveStatus::Active);
    }

    public function availabilitySlots(): MorphMany
    {
        return $this->morphMany(AvailabilitySlot::class, 'subject');
    }

    public function ownerAccount(): ?User
    {
        return $this->is_child ? $this->parent : $this->user;
    }

    public function scopeForTrainer(Builder $query, int $trainerId): Builder
    {
        return $query->whereHas('associations', fn (Builder $associations) => $associations
            ->where('trainer_id', $trainerId)
            ->where('status', ActiveStatus::Active));
    }
}
