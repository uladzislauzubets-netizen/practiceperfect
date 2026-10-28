<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['share_link_id', 'user_id', 'player_profile_id', 'used_at'])]
class ShareLinkUse extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(ShareLink::class);
    }
}
