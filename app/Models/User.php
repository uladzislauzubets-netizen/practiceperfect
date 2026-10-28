<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'first_name', 'last_name', 'email', 'phone', 'school', 'photo_path', 'photo_thumb_path', 'photo_url',
    'role', 'status', 'email_verified_at', 'password', 'must_change_password', 'parent_id', 'active_context',
    'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
    'last_login_at', 'deactivated_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'active_context' => 'array',
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    public function coachProfile(): HasOne
    {
        return $this->hasOne(CoachProfile::class);
    }

    public function playerProfile(): HasOne
    {
        return $this->hasOne(PlayerProfile::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childAccounts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(PlayerProfile::class, 'parent_id');
    }

    public function availabilitySlots(): MorphMany
    {
        return $this->morphMany(AvailabilitySlot::class, 'subject');
    }

    public function isRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isTrainer(): bool
    {
        return $this->role === UserRole::Trainer;
    }

    public function isCoach(): bool
    {
        return $this->role === UserRole::Coach;
    }

    public function isPlayer(): bool
    {
        return $this->role === UserRole::Player;
    }

    public function isChildAccount(): bool
    {
        return $this->isPlayer() && $this->parent_id !== null;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /** @return array<int, int> */
    public function trainerIds(): array
    {
        return TrainerPlayer::query()
            ->whereIn('player_profile_id', $this->familyProfileIds())
            ->where('status', ActiveStatus::Active)
            ->pluck('trainer_id')
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    public function familyProfileIds(): array
    {
        return PlayerProfile::query()
            ->where(fn (Builder $query) => $query->where('user_id', $this->id)->orWhere('parent_id', $this->id))
            ->pluck('id')
            ->all();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escape = fn (string $value): string => '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
        $words = preg_split('/\s+/', $term) ?: [$term];

        return $query->where(function (Builder $query) use ($escape, $words, $term) {
            $query->where('email', 'like', $escape($term))
                ->orWhere(function (Builder $query) use ($escape, $words) {
                    foreach ($words as $word) {
                        $query->where(fn (Builder $inner) => $inner
                            ->where('first_name', 'like', $escape($word))
                            ->orWhere('last_name', 'like', $escape($word)));
                    }
                });
        });
    }
}
