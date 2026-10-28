<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trainer_id', 'status', 'bio', 'credentials', 'certifications', 'is_public', 'joined_trainer_at'])]
class CoachProfile extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'is_public' => 'boolean',
            'joined_trainer_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
