<?php

declare(strict_types=1);

use App\Actions\Trainer\GetPlayerShareLink;
use App\Enums\ActiveStatus;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\TrainerAssociations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

function admin(): User
{
    return User::factory()->superAdmin()->create();
}

function trainer(?string $business = null): User
{
    return User::factory()->trainer($business)->create();
}

function coachFor(User $trainer): User
{
    return User::factory()->coach($trainer)->create();
}

function player(): User
{
    return User::factory()->player()->create();
}

function parentWithChild(string $childFirstName = 'Alex'): array
{
    $parent = User::factory()->create();
    $child = PlayerProfile::factory()->childOf($parent)->create(['first_name' => $childFirstName]);

    return [$parent, $child];
}

function connect(PlayerProfile $profile, User $trainer): void
{
    app(TrainerAssociations::class)->attach($profile, $trainer);
}

function playerLinkFor(User $trainer): ShareLink
{
    return app(GetPlayerShareLink::class)->handle($trainer);
}

function activeCoachProfile(User $coach): ?object
{
    return $coach->coachProfile()->where('status', ActiveStatus::Active)->first();
}
