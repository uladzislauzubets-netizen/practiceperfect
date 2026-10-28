<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trainer_id', 'player_profile_id', 'share_link_id', 'status', 'connected_at', 'disconnected_at'])]
class TrainerPlayer extends Model
{
    protected $table = 'trainer_player';

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(ShareLink::class);
    }
}
