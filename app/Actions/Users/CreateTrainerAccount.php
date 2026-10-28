<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Notifications\TrainerAccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final class CreateTrainerAccount
{
    public const DELIVERY_TEMPORARY_PASSWORD = 'temporary_password';

    public const DELIVERY_INVITE_LINK = 'invite_link';

    /**
     * @param  array{business_name: string, first_name: string, last_name: string, email: string, phone: string, delivery: string}  $data
     */
    public function handle(User $admin, array $data): User
    {
        $usesTemporaryPassword = $data['delivery'] === self::DELIVERY_TEMPORARY_PASSWORD;
        $temporaryPassword = $usesTemporaryPassword ? Str::random(14) : null;

        $trainer = DB::transaction(function () use ($admin, $data, $temporaryPassword) {
            $trainer = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => UserRole::Trainer,
                'status' => UserStatus::Active,
                'password' => $temporaryPassword ?? Str::random(64),
                'must_change_password' => true,
            ]);

            TrainerProfile::query()->create([
                'user_id' => $trainer->id,
                'business_name' => $data['business_name'],
            ]);

            AuditLog::record($admin, 'trainer.created', $trainer, [
                'business_name' => $data['business_name'],
                'delivery' => $data['delivery'],
            ]);

            return $trainer;
        });

        $setupUrl = $usesTemporaryPassword ? null : URL::temporarySignedRoute(
            'password.setup',
            now()->addDays((int) config('auth.setup_link_expire_days')),
            ['user' => $trainer->id],
        );

        $trainer->notify(new TrainerAccountCreated($data['business_name'], $temporaryPassword, $setupUrl));

        return $trainer;
    }
}
