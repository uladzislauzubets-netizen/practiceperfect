<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['original_user_id', 'original_email', 'deleted_by', 'reason', 'original_data', 'deleted_at'])]
class UserDeletion extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'original_data' => 'encrypted:array',
            'deleted_at' => 'datetime',
        ];
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
