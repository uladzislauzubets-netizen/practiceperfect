<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\TrainerAssociations;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(TrainerAssociations $associations): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo data is for local development only. Use `php artisan admin:create` instead.');
        }

        User::factory()->superAdmin()->create(['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@example.com']);

        $trainer = User::factory()->trainer('Elite Basketball Academy')->create(['first_name' => 'Tina', 'last_name' => 'Trainer', 'email' => 'trainer@example.com']);
        User::factory()->coach($trainer)->create(['first_name' => 'Carl', 'last_name' => 'Coach', 'email' => 'coach@example.com']);

        $parent = User::factory()->player()->create(['first_name' => 'Sarah', 'last_name' => 'Parent', 'email' => 'parent@example.com']);
        $alex = PlayerProfile::factory()->childOf($parent)->create(['first_name' => 'Alex', 'last_name' => 'Parent', 'gender' => 'male']);
        $associations->attach($parent->playerProfile, $trainer);
        $associations->attach($alex, $trainer);

        User::factory()->childAccountOf($parent, $alex)->create(['first_name' => 'Alex', 'last_name' => 'Parent', 'email' => 'alex@example.com']);

        $player = User::factory()->player()->create(['first_name' => 'Pat', 'last_name' => 'Player', 'email' => 'player@example.com']);
        $associations->attach($player->playerProfile, $trainer);
    }
}
