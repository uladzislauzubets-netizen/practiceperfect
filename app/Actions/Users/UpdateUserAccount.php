<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateUserAccount
{
    private const ACCOUNT_FIELDS = ['first_name', 'last_name', 'email', 'phone', 'school'];

    private const TRAINER_FIELDS = ['business_name', 'address', 'website', 'description'];

    private const COACH_FIELDS = ['bio', 'credentials', 'certifications', 'is_public'];

    /** @param  array<string, mixed>  $data */
    public function handle(User $admin, User $user, array $data): User
    {
        return DB::transaction(function () use ($admin, $user, $data) {
            $user->fill(Arr::only($data, self::ACCOUNT_FIELDS))->save();
            $changed = array_keys($user->getChanges());

            if ($user->isTrainer() && $user->trainerProfile !== null) {
                $user->trainerProfile->fill(Arr::only($data, self::TRAINER_FIELDS))->save();
                $changed = [...$changed, ...array_keys($user->trainerProfile->getChanges())];
            }

            if ($user->isCoach() && $user->coachProfile !== null) {
                $user->coachProfile->fill(Arr::only($data, self::COACH_FIELDS))->save();
                $changed = [...$changed, ...array_keys($user->coachProfile->getChanges())];
            }

            AuditLog::record($admin, 'user.updated', $user, ['fields' => array_values(array_unique(array_diff($changed, ['updated_at'])))]);

            return $user;
        });
    }
}
