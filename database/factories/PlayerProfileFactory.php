<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerProfile>
 */
class PlayerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => now()->subYears(10),
            'gender' => fake()->randomElement(Gender::cases()),
            'is_child' => true,
        ];
    }

    public function childOf(User $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id, 'is_child' => true]);
    }
}
