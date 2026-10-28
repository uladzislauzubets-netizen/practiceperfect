<?php

declare(strict_types=1);

use App\Enums\ActiveStatus;
use App\Models\AvailabilitySlot;
use App\Models\PlayerProfile;
use App\Models\TrainerPlayer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function childPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Alex', 'last_name' => 'Lee', 'birth_date' => now()->subYears(10)->toDateString(),
        'gender' => 'male', ...$overrides,
    ];
}

it('lets a parent add a child profile with no trainer when they have none', function () {
    $parent = User::factory()->create();

    $this->actingAs($parent)->post('/family/profiles', childPayload(['school' => 'Oak Elementary']))->assertRedirect();

    $child = $parent->children()->firstOrFail();
    expect($child->is_child)->toBeTrue()->and($child->school)->toBe('Oak Elementary')->and($child->associations)->toHaveCount(0);
});

it('associates the child with the single trainer when the parent says yes', function () {
    $trainer = trainer();
    $parent = User::factory()->player()->create();
    connect($parent->playerProfile, $trainer);

    $this->actingAs($parent)->post('/family/profiles', childPayload(['trainer_ids' => [$trainer->id]]))->assertRedirect();

    expect($parent->children()->first()->activeAssociations()->pluck('trainer_id')->all())->toBe([$trainer->id]);
});

it('leaves the child unassociated when the parent says no', function () {
    $trainer = trainer();
    $parent = User::factory()->player()->create();
    connect($parent->playerProfile, $trainer);

    $this->actingAs($parent)->post('/family/profiles', childPayload(['trainer_ids' => []]))->assertRedirect();

    expect(TrainerPlayer::query()->where('player_profile_id', $parent->children()->first()->id)->count())->toBe(0);
});

it('associates the child with only the chosen trainers among several', function () {
    [$a, $b, $c] = [trainer('A'), trainer('B'), trainer('C')];
    $parent = User::factory()->player()->create();
    foreach ([$a, $b, $c] as $trainer) {
        connect($parent->playerProfile, $trainer);
    }

    $this->actingAs($parent)->post('/family/profiles', childPayload(['trainer_ids' => [$a->id, $c->id]]));

    expect($parent->children()->first()->activeAssociations()->pluck('trainer_id')->sort()->values()->all())->toBe([$a->id, $c->id]);
});

it('does not let a parent pick trainers they are not connected to', function () {
    $stranger = trainer();
    $parent = User::factory()->create();

    $this->actingAs($parent)->post('/family/profiles', childPayload(['trainer_ids' => [$stranger->id]]))->assertSessionHasErrors('trainer_ids.0');

    expect($parent->children()->count())->toBe(0);
});

it('validates child name, age and gender', function () {
    $parent = User::factory()->create();

    $this->actingAs($parent)->post('/family/profiles', [])->assertSessionHasErrors(['first_name', 'last_name', 'birth_date', 'gender']);
    $this->post('/family/profiles', childPayload(['birth_date' => now()->subYears(25)->toDateString()]))->assertSessionHasErrors('birth_date');
    $this->post('/family/profiles', childPayload(['birth_date' => now()->addDay()->toDateString()]))->assertSessionHasErrors('birth_date');
});

it('warns about a similar child unless confirmed', function () {
    $parent = User::factory()->create();
    $this->actingAs($parent)->post('/family/profiles', childPayload());

    $this->post('/family/profiles', childPayload())->assertSessionHasErrors('duplicate');
    expect($parent->children()->count())->toBe(1);

    $this->post('/family/profiles', childPayload(['confirm_duplicate' => true]))->assertSessionHasNoErrors();
    expect($parent->children()->count())->toBe(2);
});

it('stores a child photo with a thumbnail', function () {
    Storage::fake('local');
    $parent = User::factory()->create();

    $this->actingAs($parent)->post('/family/profiles', childPayload(['photo' => UploadedFile::fake()->image('kid.png', 800, 600)]))->assertSessionHasNoErrors();

    $child = $parent->children()->first();
    Storage::disk('local')->assertExists([$child->photo_path, $child->photo_thumb_path]);
    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($child->photo_thumb_path));
    expect([$width, $height])->toBe([128, 128]);
});

it('lets a parent train themselves only after confirming they are an adult', function () {
    $parent = User::factory()->create();

    $this->actingAs($parent)->post('/family/self', [])->assertSessionHasErrors('confirm_adult');
    $this->post('/family/self', ['confirm_adult' => true, 'gender' => 'female'])->assertRedirect();

    $parent = $parent->fresh();
    expect($parent->playerProfile)->not->toBeNull()->and($parent->playerProfile->is_child)->toBeFalse();
    $this->actingAs($parent)->post('/family/self', ['confirm_adult' => true])->assertSessionHasErrors('profile');
});

it('lists children with their trainers on the family page', function () {
    $trainer = trainer('Coach Bob');
    [$parent, $child] = parentWithChild('Alex');
    connect($child, $trainer);

    $this->actingAs($parent)->get('/family')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Family/Index')
        ->has('profiles', 1)
        ->where('profiles.0.name', $child->name)
        ->where('profiles.0.trainers.0.name', 'Coach Bob')
        ->has('trainers', 1));
});

it('adds a child to a trainer with a pasted ShareLink url or a known trainer', function () {
    $first = trainer('First');
    $second = trainer('Second');
    $parent = User::factory()->player()->create();
    connect($parent->playerProfile, $first);
    $child = PlayerProfile::factory()->childOf($parent)->create();
    $secondLink = playerLinkFor($second);

    $this->actingAs($parent)->post("/family/profiles/{$child->id}/trainers", ['share_code' => $secondLink->url])->assertSessionHasNoErrors();
    expect($child->activeAssociations()->where('trainer_id', $second->id)->exists())->toBeTrue()
        ->and($secondLink->fresh()->uses_count)->toBe(1);

    $this->post("/family/profiles/{$child->id}/trainers", ['trainer_id' => $first->id])->assertSessionHasNoErrors();
    expect($child->activeAssociations()->where('trainer_id', $first->id)->exists())->toBeTrue();
});

it('rejects invalid ShareLinks and trainers the parent does not know', function () {
    $parent = User::factory()->player()->create();
    $child = PlayerProfile::factory()->childOf($parent)->create();
    $stranger = trainer();

    $this->actingAs($parent)->post("/family/profiles/{$child->id}/trainers", ['share_code' => 'nonsense'])->assertSessionHasErrors('share_code');
    $this->post("/family/profiles/{$child->id}/trainers", ['trainer_id' => $stranger->id])->assertSessionHasErrors('trainer_id');
    $this->post("/family/profiles/{$child->id}/trainers", [])->assertSessionHasErrors(['share_code', 'trainer_id']);
});

it('removes a child from a trainer, keeping history and clearing their trainer availability', function () {
    $trainer = trainer();
    [$parent, $child] = parentWithChild();
    connect($child, $trainer);
    $child->availabilitySlots()->create(['trainer_id' => $trainer->id, 'day_of_week' => 1, 'start_time' => '17:00:00', 'end_time' => '20:00:00', 'is_available' => true]);

    $this->actingAs($parent)->delete("/family/profiles/{$child->id}/trainers/{$trainer->id}")->assertRedirect();

    $association = TrainerPlayer::query()->firstOrFail();
    expect($association->status)->toBe(ActiveStatus::Inactive)->and($association->disconnected_at)->not->toBeNull()
        ->and($child->availabilitySlots()->count())->toBe(0)
        ->and(AvailabilitySlot::withTrashed()->count())->toBe(1);

    $this->actingAs($trainer)->get('/trainer/players')->assertInertia(fn ($page) => $page->has('players', 0));
    $this->actingAs($parent)->delete("/family/profiles/{$child->id}/trainers/{$trainer->id}")->assertNotFound();
});

it('prevents managing other families', function () {
    [$parent] = parentWithChild();
    [, $strangersChild] = parentWithChild('Other');
    $trainer = trainer();
    connect($strangersChild, $trainer);

    $this->actingAs($parent)->post("/family/profiles/{$strangersChild->id}", childPayload())->assertForbidden();
    $this->post("/family/profiles/{$strangersChild->id}/trainers", ['trainer_id' => $trainer->id])->assertForbidden();
    $this->delete("/family/profiles/{$strangersChild->id}/trainers/{$trainer->id}")->assertForbidden();
    $this->post("/family/profiles/{$strangersChild->id}/login", ['email' => 'x@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertForbidden();
});

it('lets a parent edit a child and set the per child token rule', function () {
    [$parent, $child] = parentWithChild();
    [, $other] = [null, PlayerProfile::factory()->childOf($parent)->create()];

    $this->actingAs($parent)->post("/family/profiles/{$child->id}", childPayload(['first_name' => 'Alexander', 'jersey_number' => '23', 'allow_token_spending_without_approval' => true]))->assertRedirect();

    expect($child->fresh()->first_name)->toBe('Alexander')
        ->and($child->fresh()->jersey_number)->toBe('23')
        ->and($child->fresh()->allow_token_spending_without_approval)->toBeTrue()
        ->and($other->fresh()->allow_token_spending_without_approval)->toBeFalse();
});

it('defaults token spending approval to required', function () {
    [, $child] = parentWithChild();

    expect($child->fresh()->allow_token_spending_without_approval)->toBeFalse();
});

it('creates a limited login for a child', function () {
    [$parent, $child] = parentWithChild('Alex');

    $this->actingAs($parent)->post("/family/profiles/{$child->id}/login", [
        'email' => 'alex.login@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123',
    ])->assertSessionHasNoErrors();

    $account = User::query()->where('email', 'alex.login@example.com')->firstOrFail();
    expect($account->isChildAccount())->toBeTrue()
        ->and($account->parent_id)->toBe($parent->id)
        ->and($child->fresh()->user_id)->toBe($account->id);

    $this->post("/family/profiles/{$child->id}/login", ['email' => 'second@example.com', 'password' => 'Password123', 'password_confirmation' => 'Password123'])->assertSessionHasErrors('email');
});

it('keeps children out of every family management route', function () {
    $parent = User::factory()->create();
    $account = User::factory()->childAccountOf($parent)->create();
    $trainer = trainer();
    connect($account->playerProfile, $trainer);
    $profile = $account->playerProfile;

    $this->actingAs($account);
    $this->get('/family')->assertForbidden();
    $this->post('/family/profiles', childPayload())->assertForbidden();
    $this->post('/family/self', ['confirm_adult' => true])->assertForbidden();
    $this->post("/family/profiles/{$profile->id}/trainers", ['trainer_id' => $trainer->id])->assertForbidden();
    $this->delete("/family/profiles/{$profile->id}/trainers/{$trainer->id}")->assertForbidden();
    $this->get('/family/approvals')->assertForbidden();
    expect($profile->activeAssociations()->count())->toBe(1);
});

it('does not expose the parents data to a child login', function () {
    $trainer = trainer();
    $parent = User::factory()->player()->create();
    connect($parent->playerProfile, $trainer);
    $account = User::factory()->childAccountOf($parent)->create();
    connect($account->playerProfile, $trainer);

    $this->actingAs($account)->get('/dashboard')->assertInertia(fn ($page) => $page
        ->where('context.groups.0.title', 'Your Training')
        ->has('context.groups', 1)
        ->has('context.groups.0.options', 1)
        ->where('context.groups.0.options.0.profile_id', $account->playerProfile->id));
});
