<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\ContextResolver;

function familyWithTrainers(): array
{
    $lisa = trainer('Coach Lisa');
    $mike = trainer('Coach Mike');
    $bob = trainer('Coach Bob');
    $sarah = User::factory()->player()->create(['first_name' => 'Sarah']);
    $alex = PlayerProfile::factory()->childOf($sarah)->create(['first_name' => 'Alex']);
    $maya = PlayerProfile::factory()->childOf($sarah)->create(['first_name' => 'Maya']);

    connect($sarah->playerProfile, $lisa);
    connect($sarah->playerProfile, $mike);
    connect($alex, $bob);
    connect($maya, $bob);
    connect($maya, $lisa);

    return compact('sarah', 'alex', 'maya', 'lisa', 'mike', 'bob');
}

it('groups a parent who also trains into Your Training and Your Childrens Training', function () {
    ['sarah' => $sarah] = familyWithTrainers();

    $this->actingAs($sarah)->get('/dashboard')->assertInertia(fn ($page) => $page
        ->has('context.groups', 2)
        ->where('context.groups.0.title', 'Your Training')
        ->where('context.groups.0.options.0.label', 'Sarah (Me) → Coach Lisa')
        ->where('context.groups.0.options.1.label', 'Sarah (Me) → Coach Mike')
        ->where('context.groups.1.title', "Your Children's Training")
        ->has('context.groups.1.options', 3)
        ->where('context.groups.1.options.0.label', 'Alex → Coach Bob')
        ->where('context.groups.1.options.1.label', 'Maya → Coach Bob')
        ->where('context.groups.1.options.2.label', 'Maya → Coach Lisa'));
});

it('shows only childrens training for a parent who does not train', function () {
    $bob = trainer('Coach Bob');
    [$parent, $alex] = parentWithChild('Alex');
    connect($alex, $bob);

    $this->actingAs($parent)->get('/dashboard')->assertInertia(fn ($page) => $page
        ->has('context.groups', 1)
        ->where('context.groups.0.title', "Your Children's Training")
        ->where('context.current_label', 'Alex → Coach Bob'));
});

it('shows a simple trainer list for a child login', function () {
    $bob = trainer('Coach Bob');
    $lisa = trainer('Coach Lisa');
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create();
    connect($child->playerProfile, $bob);
    connect($child->playerProfile, $lisa);

    $this->actingAs($child)->get('/dashboard')->assertInertia(fn ($page) => $page
        ->has('context.groups', 1)
        ->where('context.groups.0.options.0.label', 'Coach Bob')
        ->where('context.groups.0.options.1.label', 'Coach Lisa'));
});

it('switches context and persists it across sessions', function () {
    ['sarah' => $sarah, 'maya' => $maya, 'lisa' => $lisa] = familyWithTrainers();

    $this->actingAs($sarah)->post('/context', ['profile_id' => $maya->id, 'trainer_id' => $lisa->id])->assertRedirect();

    expect($sarah->fresh()->active_context)->toBe(['profile_id' => $maya->id, 'trainer_id' => $lisa->id]);

    auth()->logout();
    $this->actingAs($sarah->fresh())->get('/dashboard')->assertInertia(fn ($page) => $page
        ->where('context.current_label', 'Maya → Coach Lisa')
        ->where('current.profile_name', $maya->name)
        ->where('current.trainer_name', 'Coach Lisa')
        ->where('branding.name', 'Coach Lisa'));
});

it('refuses to switch to a context the user does not have', function () {
    ['sarah' => $sarah, 'alex' => $alex, 'mike' => $mike] = familyWithTrainers();

    $this->actingAs($sarah)->post('/context', ['profile_id' => $alex->id, 'trainer_id' => $mike->id])->assertForbidden();
    [, $strangers] = parentWithChild('Other');
    $this->post('/context', ['profile_id' => $strangers->id, 'trainer_id' => $mike->id])->assertForbidden();
});

it('falls back to the first context when the stored one is no longer valid', function () {
    ['sarah' => $sarah, 'lisa' => $lisa] = familyWithTrainers();
    $sarah->forceFill(['active_context' => ['profile_id' => 9999, 'trainer_id' => 9999]])->save();

    expect(app(ContextResolver::class)->current($sarah->fresh())->trainer->id)->toBe($lisa->id);
});

it('keeps views isolated per trainer for the same child', function () {
    ['sarah' => $sarah, 'maya' => $maya, 'lisa' => $lisa, 'bob' => $bob] = familyWithTrainers();

    $maya->availabilitySlots()->create(['trainer_id' => $lisa->id, 'day_of_week' => 2, 'start_time' => '18:00:00', 'end_time' => '20:00:00', 'is_available' => true]);

    $this->actingAs($sarah)->post('/context', ['profile_id' => $maya->id, 'trainer_id' => $lisa->id]);
    $this->get('/availability')->assertInertia(fn ($page) => $page->has('slots', 1));

    $this->post('/context', ['profile_id' => $maya->id, 'trainer_id' => $bob->id]);
    $this->get('/availability')->assertInertia(fn ($page) => $page->has('slots', 0));
});

it('hides inactive trainers from the switcher', function () {
    ['sarah' => $sarah, 'lisa' => $lisa] = familyWithTrainers();
    $lisa->forceFill(['status' => UserStatus::Inactive])->save();

    $this->actingAs($sarah)->get('/dashboard')->assertInertia(fn ($page) => $page
        ->has('context.groups.0.options', 1)
        ->where('context.groups.0.options.0.label', 'Sarah (Me) → Coach Mike'));
});

it('applies the context trainers branding for players, the trainers own for coaches', function () {
    $trainer = trainer('Branded Co');
    $trainer->trainerProfile->update(['brand_color' => '#AA00FF']);
    $player = User::factory()->player()->create();
    connect($player->playerProfile, $trainer);
    $coach = coachFor($trainer);

    $this->actingAs($player)->get('/dashboard')->assertInertia(fn ($page) => $page->where('branding.color', '#AA00FF')->where('branding.name', 'Branded Co'));
    $this->actingAs($coach)->get('/dashboard')->assertInertia(fn ($page) => $page->where('branding.color', '#AA00FF'));
    $this->actingAs(admin())->get('/dashboard')->assertInertia(fn ($page) => $page->where('branding.color', '#00B300')->where('branding.is_custom', false));
});
