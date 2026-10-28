<?php

declare(strict_types=1);

namespace App\Actions\Join;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\CampConversionPrefill;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Notifications\PlayerWelcome;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

final class RegisterPlayerViaShareLink
{
    public function __construct(private readonly AssociatePlayersWithTrainer $associate) {}

    /**
     * @param  array{first_name: string, last_name: string, email: string, password: string, phone: string, trains_self: bool, gender?: ?string, birth_date?: ?string, children?: array<int, array<string, mixed>>}  $data
     */
    public function handle(ShareLink $link, array $data, ?CampConversionPrefill $prefill = null): User
    {
        $user = DB::transaction(function () use ($link, $data, $prefill) {
            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::Player,
                'status' => UserStatus::Active,
            ]);

            $profiles = collect();

            if ($data['trains_self']) {
                $profiles->push(PlayerProfile::query()->create([
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'gender' => $data['gender'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'is_child' => false,
                ]));
            }

            foreach ($data['children'] ?? [] as $child) {
                $profiles->push(PlayerProfile::query()->create([
                    'parent_id' => $user->id,
                    'first_name' => $child['first_name'],
                    'last_name' => $child['last_name'],
                    'birth_date' => $child['birth_date'],
                    'gender' => $child['gender'],
                    'is_child' => true,
                ]));
            }

            $this->associate->handle($link, $user, $profiles);
            $prefill?->update(['consumed_at' => now()]);

            return $user;
        });

        event(new Registered($user));
        $user->notify(new PlayerWelcome($link->trainer->trainerProfile?->business_name ?? $link->trainer->name));

        return $user;
    }
}
