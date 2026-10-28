<?php

declare(strict_types=1);

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\PlayerProfile;
use App\Models\ShareLinkUse;
use App\Models\TrainerPlayer;
use App\Models\User;
use App\Notifications\PlayerWelcome;
use App\Services\TrainerAssociations;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

function registrationPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Sarah',
        'last_name' => 'Lee',
        'email' => 'sarah@example.com',
        'phone' => '+1 555 123 4567',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'trains_self' => true,
        'confirm_adult' => true,
        'gender' => 'female',
        ...$overrides,
    ];
}

it('shows the registration form to guests with the trainer branding', function () {
    $trainer = trainer('Elite Academy');
    $trainer->trainerProfile->update(['brand_color' => '#FF0000']);
    $link = playerLinkFor($trainer);

    $this->get("/join/{$link->code}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('Join/RegisterPlayer')
        ->where('link.trainer_name', 'Elite Academy')
        ->where('branding.color', '#FF0000'));
});

it('registers a player, associates them with the trainer and sends confirmation emails', function () {
    Notification::fake();
    $trainer = trainer('Elite Academy');
    $link = playerLinkFor($trainer);

    $this->post("/join/{$link->code}/register", registrationPayload())->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'sarah@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe(UserRole::Player)
        ->and($user->playerProfile->is_child)->toBeFalse()
        ->and($user->playerProfile->activeAssociations()->where('trainer_id', $trainer->id)->exists())->toBeTrue();

    expect($link->fresh()->uses_count)->toBe(1)
        ->and(ShareLinkUse::query()->where('share_link_id', $link->id)->where('user_id', $user->id)->exists())->toBeTrue();

    Notification::assertSentTo($user, PlayerWelcome::class);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('registers a parent with children and no self profile', function () {
    Notification::fake();
    $trainer = trainer();
    $link = playerLinkFor($trainer);

    $this->post("/join/{$link->code}/register", registrationPayload([
        'trains_self' => false,
        'children' => [
            ['first_name' => 'Alex', 'last_name' => 'Lee', 'birth_date' => now()->subYears(9)->toDateString(), 'gender' => 'male'],
            ['first_name' => 'Maya', 'last_name' => 'Lee', 'birth_date' => now()->subYears(12)->toDateString(), 'gender' => 'female'],
        ],
    ]))->assertRedirect(route('dashboard'));

    $parent = User::query()->where('email', 'sarah@example.com')->firstOrFail();
    expect($parent->playerProfile)->toBeNull()
        ->and($parent->children)->toHaveCount(2)
        ->and(TrainerPlayer::query()->where('trainer_id', $trainer->id)->count())->toBe(2)
        ->and($link->fresh()->uses_count)->toBe(1)
        ->and(ShareLinkUse::query()->count())->toBe(2);
});

it('requires somebody to train', function () {
    $link = playerLinkFor(trainer());

    $this->post("/join/{$link->code}/register", registrationPayload(['trains_self' => false, 'confirm_adult' => false]))
        ->assertSessionHasErrors('trains_self');
});

it('requires adult confirmation when registering oneself', function () {
    $link = playerLinkFor(trainer());

    $this->post("/join/{$link->code}/register", registrationPayload(['confirm_adult' => false]))->assertSessionHasErrors('confirm_adult');
});

it('only accepts children between 1 and 18', function (int $years) {
    $link = playerLinkFor(trainer());

    $this->post("/join/{$link->code}/register", registrationPayload([
        'trains_self' => false,
        'children' => [['first_name' => 'A', 'last_name' => 'B', 'birth_date' => now()->subYears($years)->subDay()->toDateString(), 'gender' => 'male']],
    ]))->assertSessionHasErrors('children.0.birth_date');
})->with([0, 19, 30]);

it('validates the registration fields', function () {
    $link = playerLinkFor(trainer());

    $this->post("/join/{$link->code}/register", registrationPayload(['phone' => 'abc', 'email' => 'nope', 'password' => 'short', 'password_confirmation' => 'other']))
        ->assertSessionHasErrors(['phone', 'email', 'password']);
});

it('tells existing users to log in instead of creating a duplicate', function () {
    User::factory()->create(['email' => 'sarah@example.com']);
    $link = playerLinkFor(trainer());

    $this->post("/join/{$link->code}/register", registrationPayload())
        ->assertSessionHasErrors(['email' => 'An account with this email already exists. Log in to connect with this trainer.']);
    expect(User::query()->where('email', 'sarah@example.com')->count())->toBe(1);
});

it('stores the intended url so login returns to the link', function () {
    $link = playerLinkFor(trainer());

    $this->get("/join/{$link->code}");
    $user = User::factory()->player()->create();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(url("/join/{$link->code}"));
});

it('instantly connects a logged in player to a new trainer without a second account', function () {
    $first = trainer('First');
    $second = trainer('Second');
    $user = User::factory()->player()->create();
    connect($user->playerProfile, $first);

    $this->actingAs($user)->get('/join/'.playerLinkFor($second)->code)->assertRedirect(route('dashboard'));

    expect($user->playerProfile->activeAssociations()->count())->toBe(2)
        ->and(User::query()->count())->toBe(3)
        ->and($user->fresh()->active_context)->toBe(['profile_id' => $user->playerProfile->id, 'trainer_id' => $second->id]);
});

it('asks a parent with children who will train with the new trainer', function () {
    $trainer = trainer('Bob');
    [$parent, $child] = parentWithChild('Alex');
    $self = PlayerProfile::query()->create(['user_id' => $parent->id, 'first_name' => $parent->first_name, 'last_name' => $parent->last_name, 'is_child' => false]);
    $other = PlayerProfile::factory()->childOf($parent)->create(['first_name' => 'Maya']);
    $link = playerLinkFor($trainer);

    $this->actingAs($parent)->get("/join/{$link->code}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('Join/SelectProfiles')
        ->has('profiles', 3));

    $this->post("/join/{$link->code}/associate", ['profile_ids' => [$child->id, $self->id]])->assertRedirect(route('dashboard'));

    expect($child->activeAssociations()->count())->toBe(1)
        ->and($self->activeAssociations()->count())->toBe(1)
        ->and($other->activeAssociations()->count())->toBe(0);
});

it('rejects associating profiles that belong to someone else', function () {
    $trainer = trainer();
    [$parent] = parentWithChild();
    PlayerProfile::factory()->childOf($parent)->create();
    [, $strangersChild] = parentWithChild('Other');
    $link = playerLinkFor($trainer);

    $this->actingAs($parent)->post("/join/{$link->code}/associate", ['profile_ids' => [$strangersChild->id]])->assertForbidden();

    expect(TrainerPlayer::query()->count())->toBe(0);
});

it('requires at least one selection', function () {
    $link = playerLinkFor(trainer());
    [$parent] = parentWithChild();

    $this->actingAs($parent)->post("/join/{$link->code}/associate", ['profile_ids' => []])->assertSessionHasErrors('profile_ids');
});

it('reactivates a previous association instead of duplicating it', function () {
    $trainer = trainer();
    $user = User::factory()->player()->create();
    connect($user->playerProfile, $trainer);
    app(TrainerAssociations::class)->detach($user->playerProfile, $trainer);

    $this->actingAs($user)->get('/join/'.playerLinkFor($trainer)->code)->assertRedirect();

    expect(TrainerPlayer::query()->count())->toBe(1)
        ->and(TrainerPlayer::query()->first()->status)->toBe(ActiveStatus::Active);
});

it('shows a clear message for unknown, inactive and deactivated trainer links', function () {
    $this->get('/join/doesnotexist')->assertNotFound()->assertInertia(fn ($page) => $page->component('Join/Unavailable'));

    $trainer = trainer();
    $link = playerLinkFor($trainer);
    $link->update(['is_active' => false]);
    $this->get("/join/{$link->code}")->assertStatus(410)->assertInertia(fn ($page) => $page->where('message', 'This link is no longer active. Ask your trainer for a new one.'));

    $other = trainer();
    $otherLink = playerLinkFor($other);
    $other->forceFill(['status' => UserStatus::Inactive])->save();
    $this->get("/join/{$otherLink->code}")->assertStatus(410);
    $this->post("/join/{$otherLink->code}/register", registrationPayload())->assertStatus(410);
});

it('blocks coaches and admins from using a player link', function () {
    $trainer = trainer();
    $link = playerLinkFor($trainer);

    $this->actingAs(coachFor(trainer()))->get("/join/{$link->code}")->assertForbidden();
    $this->actingAs(admin())->get("/join/{$link->code}")->assertForbidden();
});

it('sends a player with no profile to the family page', function () {
    $link = playerLinkFor(trainer());
    $user = User::factory()->create();

    $this->actingAs($user)->get("/join/{$link->code}")->assertRedirect(route('family.index'));
});

it('keeps static player links unlimited and never expiring', function () {
    $link = playerLinkFor(trainer());

    expect($link->max_uses)->toBeNull()->and($link->expires_at)->toBeNull();
    $this->travel(5)->years();
    expect($link->fresh()->isUsable())->toBeTrue();
});

it('reuses one static link per trainer', function () {
    $trainer = trainer();

    expect(playerLinkFor($trainer)->id)->toBe(playerLinkFor($trainer)->id)
        ->and(playerLinkFor($trainer)->code)->not->toBe(playerLinkFor(trainer())->code);
});
