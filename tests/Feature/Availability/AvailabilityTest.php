<?php

declare(strict_types=1);

use App\Models\AvailabilitySlot;
use App\Models\CoachAvailabilityOverride;
use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\Availability\AvailabilityInsights;
use App\Services\Availability\CoachAvailabilityChecker;
use Carbon\Carbon;

function slot(int $day, string $start, string $end, bool $available = true): array
{
    return ['day_of_week' => $day, 'start_time' => $start, 'end_time' => $end, 'is_available' => $available];
}

function connectedPlayer(User $trainer, string $name = 'Pat'): User
{
    $user = User::factory()->player()->create(['first_name' => $name]);
    connect($user->playerProfile, $trainer);

    return $user;
}

it('saves a players best times for the current trainer context', function () {
    $trainer = trainer();
    $player = connectedPlayer($trainer);

    $this->actingAs($player)->put('/availability', ['slots' => [
        slot(1, '17:00', '20:00'),
        slot(3, '00:00', '23:59', false),
        slot(5, '09:00', '11:00'),
        slot(5, '14:00', '16:00'),
    ]])->assertRedirect()->assertSessionHas('success', 'Availability saved. Trainers can see these preferences when planning sessions.');

    $slots = $player->playerProfile->availabilitySlots()->orderBy('day_of_week')->orderBy('start_time')->get();
    expect($slots)->toHaveCount(4)
        ->and($slots->every(fn ($slot) => $slot->trainer_id === $trainer->id))->toBeTrue()
        ->and($slots[1]->is_available)->toBeFalse()
        ->and($slots[0]->start_time)->toBe('17:00:00');

    $this->get('/availability')->assertInertia(fn ($page) => $page->component('Availability/Player')->has('slots', 4)->where('slots.0.start_time', '17:00'));
});

it('replaces previous slots on save', function () {
    $player = connectedPlayer(trainer());

    $this->actingAs($player)->put('/availability', ['slots' => [slot(1, '17:00', '20:00')]]);
    $this->put('/availability', ['slots' => [slot(2, '08:00', '09:00')]]);

    expect(AvailabilitySlot::query()->count())->toBe(1)->and(AvailabilitySlot::query()->first()->day_of_week)->toBe(2);
});

it('rejects overlapping, reversed and invalid slots', function () {
    $player = connectedPlayer(trainer());

    $this->actingAs($player)->put('/availability', ['slots' => [slot(1, '17:00', '20:00'), slot(1, '19:00', '21:00')]])->assertSessionHasErrors('slots');
    $this->put('/availability', ['slots' => [slot(1, '20:00', '17:00')]])->assertSessionHasErrors('slots');
    $this->put('/availability', ['slots' => [slot(1, '00:00', '23:59', false), slot(1, '10:00', '11:00')]])->assertSessionHasErrors('slots');
    $this->put('/availability', ['slots' => [slot(9, '10:00', '11:00')]])->assertSessionHasErrors('slots.0.day_of_week');
    $this->put('/availability', ['slots' => [['day_of_week' => 1, 'is_available' => true, 'start_time' => 'noon', 'end_time' => '13:00']]])->assertSessionHasErrors('slots.0.start_time');
    expect(AvailabilitySlot::query()->count())->toBe(0);
});

it('keeps separate availability for each of a parents children', function () {
    $trainer = trainer();
    [$parent, $alex] = parentWithChild('Alex');
    $maya = PlayerProfile::factory()->childOf($parent)->create(['first_name' => 'Maya']);
    connect($alex, $trainer);
    connect($maya, $trainer);

    $this->actingAs($parent)->post('/context', ['profile_id' => $alex->id, 'trainer_id' => $trainer->id]);
    $this->put('/availability', ['slots' => [slot(1, '17:00', '18:00')]]);
    $this->post('/context', ['profile_id' => $maya->id, 'trainer_id' => $trainer->id]);
    $this->put('/availability', ['slots' => [slot(2, '10:00', '11:00')]]);

    expect($alex->availabilitySlots()->first()->day_of_week)->toBe(1)->and($maya->availabilitySlots()->first()->day_of_week)->toBe(2);
});

it('lets a child login set its own availability only', function () {
    $trainer = trainer();
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create();
    connect($child->playerProfile, $trainer);

    $this->actingAs($child)->put('/availability', ['slots' => [slot(4, '16:00', '17:00')]])->assertRedirect();

    expect($child->playerProfile->availabilitySlots()->count())->toBe(1);
});

it('tells players without a trainer to connect first', function () {
    $this->actingAs(player())->get('/availability')->assertInertia(fn ($page) => $page->where('context', null));
    $this->put('/availability', ['slots' => []])->assertNotFound();
});

it('lets a coach set several time ranges per day', function () {
    $coach = coachFor(trainer());

    $this->actingAs($coach)->put('/coach/my-times', ['slots' => [
        slot(1, '16:00', '18:00'), slot(1, '19:00', '21:00'), slot(6, '09:00', '12:00'),
    ]])->assertRedirect();

    expect($coach->availabilitySlots()->count())->toBe(3)->and($coach->availabilitySlots()->whereNull('trainer_id')->count())->toBe(3);
    $this->get('/coach/my-times')->assertInertia(fn ($page) => $page->component('Availability/CoachTimes')->has('slots', 3));
    $this->put('/coach/my-times', ['slots' => [slot(1, '16:00', '18:00'), slot(1, '17:00', '19:00')]])->assertSessionHasErrors('slots');
});

it('only coaches and players can reach their own times pages', function () {
    $this->actingAs(trainer())->get('/coach/my-times')->assertForbidden();
    $this->actingAs(coachFor(trainer()))->get('/availability')->assertForbidden();
});

it('shows a trainer each players best times as a summary', function () {
    $trainer = trainer();
    $player = connectedPlayer($trainer, 'Pat');
    $this->actingAs($player)->put('/availability', ['slots' => [slot(1, '17:00', '20:00'), slot(3, '18:00', '21:00'), slot(4, '00:00', '23:59', false)]]);

    $this->actingAs($trainer)->get('/trainer/players')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Trainer/Players/Index')
        ->where('players.0.best_times', 'Mon 5-8pm, Wed 6-9pm'));
});

it('filters players by a selected day and time', function () {
    $trainer = trainer();
    $early = connectedPlayer($trainer, 'Early');
    $late = connectedPlayer($trainer, 'Late');
    $none = connectedPlayer($trainer, 'None');
    $this->actingAs($early)->put('/availability', ['slots' => [slot(1, '15:00', '18:00')]]);
    $this->actingAs($late)->put('/availability', ['slots' => [slot(1, '17:00', '21:00')]]);

    $this->actingAs($trainer)->get('/trainer/players?day=1&from=17:00&to=18:00')->assertInertia(fn ($page) => $page
        ->has('players', 2));
    $this->get('/trainer/players?day=1&from=19:00')->assertInertia(fn ($page) => $page->has('players', 1)->where('players.0.name', $late->name));
    $this->get('/trainer/players?day=2&from=19:00')->assertInertia(fn ($page) => $page->has('players', 0));
    $this->get('/trainer/players')->assertInertia(fn ($page) => $page->has('players', 3));
    $this->get('/trainer/players?day=9')->assertSessionHasErrors('day');
});

it('only exposes availability a player set for this trainer', function () {
    $mine = trainer();
    $other = trainer();
    $player = User::factory()->player()->create();
    connect($player->playerProfile, $mine);
    connect($player->playerProfile, $other);

    $this->actingAs($player)->post('/context', ['profile_id' => $player->playerProfile->id, 'trainer_id' => $other->id]);
    $this->put('/availability', ['slots' => [slot(2, '10:00', '12:00')]]);

    $this->actingAs($mine)->get('/trainer/players')->assertInertia(fn ($page) => $page->where('players.0.best_times', ''));
    $this->actingAs($other)->get('/trainer/players')->assertInertia(fn ($page) => $page->where('players.0.best_times', 'Tue 10am-12pm'));
});

it('suggests the times most players have in common', function () {
    $trainer = trainer();
    foreach (['A', 'B', 'C'] as $index => $name) {
        $player = connectedPlayer($trainer, $name);
        $this->actingAs($player)->put('/availability', ['slots' => [slot(1, '17:00', $index === 0 ? '18:00' : '20:00')]]);
    }

    $windows = app(AvailabilityInsights::class)->bestWindows($trainer->id);

    expect($windows->first())->toMatchArray(['day_of_week' => 1, 'start' => '17:00', 'end' => '18:00', 'players' => 3])
        ->and($windows->get(1))->toMatchArray(['start' => '18:00', 'end' => '20:00', 'players' => 2]);
    $this->actingAs($trainer)->get('/trainer/players')->assertInertia(fn ($page) => $page->where('best_windows.0.label', 'Mon 5-6pm'));
});

it('checks a coachs availability and warns on conflicts', function () {
    $trainer = trainer();
    $coach = coachFor($trainer);
    $this->actingAs($coach)->put('/coach/my-times', ['slots' => [slot(1, '16:00', '20:00')]]);
    $monday = Carbon::parse('next monday');

    $this->actingAs($trainer)->postJson('/trainer/coach-assignments/check', [
        'coach_id' => $coach->id, 'starts_at' => $monday->copy()->setTime(17, 0)->toDateTimeString(), 'ends_at' => $monday->copy()->setTime(18, 0)->toDateTimeString(),
    ])->assertOk()->assertJson(['available' => true, 'message' => null]);

    $this->postJson('/trainer/coach-assignments/check', [
        'coach_id' => $coach->id, 'starts_at' => $monday->copy()->setTime(19, 0)->toDateTimeString(), 'ends_at' => $monday->copy()->setTime(21, 0)->toDateTimeString(),
    ])->assertOk()->assertJson(['available' => false, 'message' => "Coach {$coach->name} is not available at this time per their schedule. Continue anyway?"]);

    $this->postJson('/trainer/coach-assignments/check', [
        'coach_id' => $coach->id, 'starts_at' => $monday->copy()->addDay()->setTime(17, 0)->toDateTimeString(), 'ends_at' => $monday->copy()->addDay()->setTime(18, 0)->toDateTimeString(),
    ])->assertJson(['available' => false]);

    $this->postJson('/trainer/coach-assignments/check', [
        'coach_id' => $coach->id, 'starts_at' => $monday->copy()->setTime(23, 0)->toDateTimeString(), 'ends_at' => $monday->copy()->addDay()->setTime(1, 0)->toDateTimeString(),
    ])->assertStatus(422);
});

it('treats a coach with no times as unavailable', function () {
    $coach = coachFor(trainer());

    expect(app(CoachAvailabilityChecker::class)->isAvailable($coach, Carbon::parse('next monday 10:00'), Carbon::parse('next monday 11:00')))->toBeFalse();
});

it('records an override with a required reason', function () {
    $trainer = trainer();
    $coach = coachFor($trainer);

    $this->actingAs($trainer)->postJson('/trainer/coach-assignments/override', ['coach_id' => $coach->id, 'event_id' => 42])->assertStatus(422)->assertJsonValidationErrors('reason');
    $this->postJson('/trainer/coach-assignments/override', ['coach_id' => $coach->id, 'event_id' => 42, 'reason' => 'Short staffed this week'])->assertCreated();

    $override = CoachAvailabilityOverride::query()->firstOrFail();
    expect($override->event_id)->toBe(42)->and($override->coach_id)->toBe($coach->id)->and($override->overridden_by)->toBe($trainer->id)->and($override->reason)->toBe('Short staffed this week');
});

it('does not let a trainer check or override another trainers coach', function () {
    $trainer = trainer();
    $foreign = coachFor(trainer());

    $this->actingAs($trainer)->postJson('/trainer/coach-assignments/check', ['coach_id' => $foreign->id, 'starts_at' => '2030-01-07 10:00', 'ends_at' => '2030-01-07 11:00'])->assertNotFound();
    $this->postJson('/trainer/coach-assignments/override', ['coach_id' => $foreign->id, 'event_id' => 1, 'reason' => 'Because I said so'])->assertNotFound();
    expect(CoachAvailabilityOverride::query()->count())->toBe(0);
});
