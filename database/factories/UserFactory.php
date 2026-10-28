<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\CoachProfile;
use App\Models\PlayerProfile;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+1555'.fake()->numerify('#######'),
            'role' => UserRole::Player,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Inactive, 'deactivated_at' => now()]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => UserRole::SuperAdmin]);
    }

    public function trainer(?string $businessName = null): static
    {
        return $this->state(fn () => ['role' => UserRole::Trainer])
            ->afterCreating(fn (User $user) => TrainerProfile::query()->create([
                'user_id' => $user->id,
                'business_name' => $businessName ?? fake()->company(),
            ]));
    }

    public function coach(?User $trainer = null): static
    {
        return $this->state(fn () => ['role' => UserRole::Coach])
            ->afterCreating(fn (User $user) => CoachProfile::query()->create([
                'user_id' => $user->id,
                'trainer_id' => ($trainer ?? User::factory()->trainer()->create())->id,
                'status' => ActiveStatus::Active,
                'joined_trainer_at' => now(),
            ]));
    }

    public function player(): static
    {
        return $this->state(fn () => ['role' => UserRole::Player])
            ->afterCreating(fn (User $user) => PlayerProfile::query()->create([
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'is_child' => false,
            ]));
    }

    public function childAccountOf(User $parent, ?PlayerProfile $profile = null): static
    {
        return $this->state(fn () => ['role' => UserRole::Player, 'parent_id' => $parent->id])
            ->afterCreating(function (User $user) use ($parent, $profile) {
                $profile ??= PlayerProfile::query()->create([
                    'parent_id' => $parent->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'birth_date' => now()->subYears(10),
                    'is_child' => true,
                ]);
                $profile->update(['user_id' => $user->id]);
            });
    }
}
