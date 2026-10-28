<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['subject_type', 'subject_id', 'trainer_id', 'day_of_week', 'start_time', 'end_time', 'is_available'])]
class AvailabilitySlot extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function coversRange(string $from, string $to): bool
    {
        return $this->is_available
            && substr($this->start_time, 0, 5) <= $from
            && substr($this->end_time, 0, 5) >= $to;
    }
}
