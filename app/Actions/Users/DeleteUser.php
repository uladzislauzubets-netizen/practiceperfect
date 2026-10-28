<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\ActiveStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\UserDeletion;
use App\Services\ImageProcessor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DeleteUser
{
    public function __construct(private readonly ImageProcessor $images) {}

    public function handle(User $admin, User $user, string $reason): void
    {
        DB::transaction(function () use ($admin, $user, $reason) {
            $accounts = [[$user, $reason]];

            foreach ($user->childAccounts()->where('status', '!=', UserStatus::Deleted)->get() as $childAccount) {
                $accounts[] = [$childAccount, "Parent account deleted: {$reason}"];
            }

            foreach ($accounts as [$account, $accountReason]) {
                $this->backup($admin, $account, $accountReason);
            }

            foreach ($accounts as [$account, $accountReason]) {
                $this->anonymize($admin, $account, $accountReason);
            }
        });
    }

    private function backup(User $admin, User $user, string $reason): void
    {
        $profiles = $this->profilesOf($user);

        UserDeletion::query()->create([
            'original_user_id' => $user->id,
            'original_email' => $user->email,
            'deleted_by' => $admin->id,
            'reason' => $reason,
            'original_data' => [
                'user' => $user->toArray(),
                'trainer_profile' => $user->trainerProfile?->toArray(),
                'coach_profile' => $user->coachProfile?->toArray(),
                'player_profiles' => $profiles->toArray(),
            ],
            'deleted_at' => now(),
        ]);
    }

    private function anonymize(User $admin, User $user, string $reason): void
    {
        $originalEmail = $user->email;
        $profiles = $this->profilesOf($user);

        $this->images->delete($user->photo_path, $user->photo_thumb_path, $user->trainerProfile?->logo_path);

        $user->trainerProfile?->update([
            'business_name' => 'Deleted User',
            'address' => null,
            'website' => null,
            'description' => null,
            'stripe_account_id' => null,
            'brand_color' => null,
            'logo_path' => null,
        ]);

        $user->coachProfile?->update([
            'status' => ActiveStatus::Inactive,
            'bio' => null,
            'credentials' => null,
            'certifications' => null,
            'is_public' => false,
        ]);

        foreach ($profiles as $profile) {
            $this->images->delete($profile->photo_path, $profile->photo_thumb_path);
            $profile->update([
                'first_name' => 'Deleted',
                'last_name' => 'User',
                'birth_date' => null,
                'school' => null,
                'jersey_number' => null,
                'photo_path' => null,
                'photo_thumb_path' => null,
            ]);
        }

        ShareLink::query()->where('created_by', $user->id)->update(['is_active' => false]);

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $originalEmail)->delete();
        DB::table('notifications')->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)->delete();

        $user->forceFill([
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'email' => "deleted_{$user->id}@example.com",
            'phone' => null,
            'school' => null,
            'photo_path' => null,
            'photo_thumb_path' => null,
            'photo_url' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'emergency_contact_relationship' => null,
            'active_context' => null,
            'password' => Str::random(64),
            'remember_token' => null,
            'email_verified_at' => null,
            'must_change_password' => false,
            'status' => UserStatus::Deleted,
            'deactivated_at' => now(),
        ])->save();

        AuditLog::record($admin, 'user.deleted', $user, ['original_user_id' => $user->id, 'reason' => $reason]);
    }

    /** @return Collection<int, PlayerProfile> */
    private function profilesOf(User $user): Collection
    {
        return PlayerProfile::query()
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('parent_id', $user->id))
            ->get();
    }
}
