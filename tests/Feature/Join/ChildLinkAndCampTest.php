<?php

declare(strict_types=1);

use App\Models\CampConversionPrefill;
use App\Models\TrainerPlayer;
use App\Models\User;
use App\Notifications\CampInvitation;
use App\Notifications\ChildWantsToJoinTrainer;
use App\Services\CampConversion;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

it('blocks a child from using a trainer link and tells the parent once', function () {
    Notification::fake();
    $trainer = trainer('Coach Bob');
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create(['first_name' => 'Alex']);
    $link = playerLinkFor($trainer);

    $this->actingAs($child)->get("/join/{$link->code}")->assertOk()->assertInertia(fn ($page) => $page
        ->component('Join/ChildBlocked')
        ->where('trainer_name', 'Coach Bob'));
    $this->get("/join/{$link->code}")->assertOk();

    expect(TrainerPlayer::query()->count())->toBe(0)->and($link->fresh()->uses_count)->toBe(0);
    Notification::assertSentToTimes($parent, ChildWantsToJoinTrainer::class, 1);
    Notification::assertSentTo($parent, ChildWantsToJoinTrainer::class, function (ChildWantsToJoinTrainer $notification) use ($parent, $link) {
        $mail = $notification->toMail($parent);

        return $mail->subject === "Alex wants to join Coach Bob's program"
            && $mail->actionText === 'Review Registration'
            && $mail->actionUrl === $link->url;
    });
});

it('prevents a child from associating through the association endpoint', function () {
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create();
    $link = playerLinkFor(trainer());

    $this->actingAs($child)->post("/join/{$link->code}/associate", ['profile_ids' => [$child->playerProfile->id]])->assertForbidden();

    expect(TrainerPlayer::query()->count())->toBe(0);
});

it('creates a prefilled registration link from a camp submission', function () {
    $trainer = trainer();

    $prefill = app(CampConversion::class)->createPrefill($trainer, [
        'first_name' => 'Cam', 'last_name' => 'Parent', 'email' => 'cam@example.com', 'phone' => '+1 555 222 3333',
        'trains_self' => false,
        'children' => [['first_name' => 'Kid', 'last_name' => 'Parent', 'birth_date' => now()->subYears(8)->toDateString(), 'gender' => 'male']],
    ]);

    $url = app(CampConversion::class)->registrationUrl($prefill);

    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
        ->component('Join/RegisterPlayer')
        ->where('prefill.email', 'cam@example.com')
        ->where('prefill.children.0.first_name', 'Kid')
        ->where('prefill_token', $prefill->token));
});

it('converts a camp submission into an account without retyping and auto assigns the trainer', function () {
    Notification::fake();
    $trainer = trainer();
    $conversion = app(CampConversion::class);
    $prefill = $conversion->createPrefill($trainer, ['first_name' => 'Cam', 'email' => 'cam@example.com']);
    $link = $prefill->shareLink;

    $this->post("/join/{$link->code}/register", [
        'first_name' => 'Cam', 'last_name' => 'Parent', 'email' => 'cam@example.com', 'phone' => '+1 555 222 3333',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
        'trains_self' => true, 'confirm_adult' => true, 'gender' => 'male', 'prefill' => $prefill->token,
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'cam@example.com')->firstOrFail();
    expect($user->playerProfile->activeAssociations()->where('trainer_id', $trainer->id)->exists())->toBeTrue()
        ->and($prefill->fresh()->consumed_at)->not->toBeNull();

    auth()->logout();
    $this->get($conversion->registrationUrl($prefill))->assertInertia(fn ($page) => $page->where('prefill', null));
});

it('ignores unknown, expired or foreign prefill tokens', function () {
    $trainer = trainer();
    $conversion = app(CampConversion::class);
    $prefill = $conversion->createPrefill($trainer, ['first_name' => 'Cam']);
    $otherLink = playerLinkFor(trainer());

    expect($conversion->findUsable('nope', $prefill->shareLink))->toBeNull()
        ->and($conversion->findUsable($prefill->token, $otherLink))->toBeNull();

    $this->travel(CampConversion::TTL_DAYS + 1)->days();
    expect($conversion->findUsable($prefill->token, $prefill->shareLink))->toBeNull();
});

it('can email the ShareLink for later registration instead', function () {
    Notification::fake();
    $conversion = app(CampConversion::class);
    $prefill = $conversion->createPrefill(trainer('Elite'), ['first_name' => 'Cam']);

    $conversion->sendInvitation($prefill, 'cam@example.com');

    Notification::assertSentTo(new AnonymousNotifiable, CampInvitation::class);
    expect($prefill->fresh()->invitation_sent_at)->not->toBeNull()->and(CampConversionPrefill::query()->count())->toBe(1);
});
