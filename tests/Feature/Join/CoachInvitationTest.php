<?php

declare(strict_types=1);

use App\Actions\Trainer\InviteCoach;
use App\Enums\ActiveStatus;
use App\Enums\ShareLinkType;
use App\Models\CoachProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Notifications\CoachInvitation;
use Illuminate\Database\QueryException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function invite(User $trainer, string $email = 'coach@example.com'): ShareLink
{
    return app(InviteCoach::class)->handle($trainer, $email, 'Casey', 'Welcome aboard');
}

function coachRegistration(array $overrides = []): array
{
    return [
        'first_name' => 'Casey', 'last_name' => 'Jones', 'email' => 'coach@example.com', 'phone' => '+1 555 987 6543',
        'password' => 'Password123', 'password_confirmation' => 'Password123', ...$overrides,
    ];
}

it('creates a one time seven day coach link and emails it', function () {
    Notification::fake();
    $trainer = trainer('Elite');

    $this->actingAs($trainer)->post('/trainer/coaches/invitations', ['email' => 'Coach@Example.com', 'name' => 'Casey', 'message' => 'Hi'])
        ->assertRedirect()->assertSessionHas('success');

    $link = ShareLink::query()->firstOrFail();
    expect($link->type)->toBe(ShareLinkType::Coach)
        ->and($link->target_email)->toBe('coach@example.com')
        ->and($link->max_uses)->toBe(1)
        ->and((int) now()->diffInDays($link->expires_at))->toBe(6);

    Notification::assertSentTo(new AnonymousNotifiable, CoachInvitation::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'coach@example.com');
});

it('registers a coach through the invitation, linking them to exactly one trainer', function () {
    $trainer = trainer('Elite');
    $link = invite($trainer);

    $this->get("/join/{$link->code}")->assertOk()->assertInertia(fn ($page) => $page->component('Join/RegisterCoach')->where('link.email', 'coach@example.com'));

    $this->post("/join/{$link->code}/register-coach", coachRegistration())->assertRedirect(route('dashboard'));

    $coach = User::query()->where('email', 'coach@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($coach);
    expect($coach->isCoach())->toBeTrue()
        ->and($coach->hasVerifiedEmail())->toBeTrue()
        ->and($coach->coachProfile->trainer_id)->toBe($trainer->id)
        ->and($coach->coachProfile->status)->toBe(ActiveStatus::Active)
        ->and($link->fresh()->uses_count)->toBe(1)
        ->and($link->fresh()->invitationStatus())->toBe('accepted');
});

it('only lets the invited email register', function () {
    $link = invite(trainer());

    $this->post("/join/{$link->code}/register-coach", coachRegistration(['email' => 'someone-else@example.com']))
        ->assertSessionHasErrors('email');
    expect(User::query()->where('email', 'someone-else@example.com')->exists())->toBeFalse();
});

it('cannot be used twice', function () {
    $link = invite(trainer());
    $this->post("/join/{$link->code}/register-coach", coachRegistration())->assertRedirect();
    auth()->logout();

    $this->get("/join/{$link->code}")->assertStatus(410)->assertInertia(fn ($page) => $page->where('message', 'This link has already been used. Ask your trainer to resend the invitation.'));
    $this->post("/join/{$link->code}/register-coach", coachRegistration(['email' => 'x@example.com']))->assertStatus(410);
});

it('expires after seven days with a clear message', function () {
    $link = invite(trainer());

    $this->travel(8)->days();

    $this->get("/join/{$link->code}")->assertStatus(410)->assertInertia(fn ($page) => $page->where('message', 'This link has expired. Ask your trainer to resend the invitation.'));
    expect($link->fresh()->invitationStatus())->toBe('expired');
});

it('lets the trainer resend, which replaces the old link', function () {
    Notification::fake();
    $trainer = trainer();
    $old = invite($trainer);
    $this->travel(8)->days();

    $this->actingAs($trainer)->post("/trainer/coaches/invitations/{$old->id}/resend")->assertRedirect();

    $fresh = ShareLink::query()->where('is_active', true)->firstOrFail();
    expect($fresh->id)->not->toBe($old->id)->and($fresh->isUsable())->toBeTrue()->and($old->fresh()->isUsable())->toBeFalse();
    Notification::assertSentTimes(CoachInvitation::class, 2);
});

it('does not let another trainer resend your invitation', function () {
    $link = invite(trainer());

    $this->actingAs(trainer())->post("/trainer/coaches/invitations/{$link->id}/resend")->assertForbidden();
});

it('lets an existing coach accept while logged in', function () {
    $trainer = trainer();
    $coach = User::factory()->coach(trainer())->create(['email' => 'coach@example.com']);
    $coach->coachProfile->update(['status' => ActiveStatus::Inactive]);

    $link = invite($trainer);

    $this->actingAs($coach)->get("/join/{$link->code}")->assertInertia(fn ($page) => $page->component('Join/AcceptCoach')->where('blocked', false));
    $this->post("/join/{$link->code}/accept")->assertRedirect(route('dashboard'));

    expect($coach->coachProfile->fresh()->trainer_id)->toBe($trainer->id)->and($coach->coachProfile->fresh()->status)->toBe(ActiveStatus::Active);
});

it('refuses a coach who is still active under another trainer', function () {
    $other = trainer();
    $coach = User::factory()->coach($other)->create(['email' => 'coach@example.com']);

    expect(fn () => invite(trainer()))->toThrow(ValidationException::class, 'already active under another trainer');

    $link = ShareLink::query()->create([
        'code' => 'manualcode1', 'type' => ShareLinkType::Coach, 'trainer_id' => trainer()->id, 'created_by' => $other->id,
        'target_email' => 'coach@example.com', 'max_uses' => 1, 'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($coach)->get("/join/{$link->code}")->assertInertia(fn ($page) => $page->where('blocked', true));
    $this->post("/join/{$link->code}/accept")->assertSessionHasErrors('link');

    expect($coach->coachProfile->fresh()->trainer_id)->toBe($other->id)->and($link->fresh()->uses_count)->toBe(0);
});

it('refuses to invite your own active coach or a non coach email', function () {
    $trainer = trainer();
    $coach = coachFor($trainer);
    $player = player();

    expect(fn () => invite($trainer, $coach->email))->toThrow(ValidationException::class, 'already part of your organization');
    expect(fn () => invite($trainer, $player->email))->toThrow(ValidationException::class, 'not a coach');
});

it('blocks non coach accounts from using a coach link', function () {
    $link = invite(trainer());

    $this->actingAs(player())->get("/join/{$link->code}")->assertForbidden();
    $this->actingAs(player())->post("/join/{$link->code}/accept")->assertSessionHasErrors('link');
});

it('lists invitations and coaches for the trainer only', function () {
    $trainer = trainer();
    $mine = coachFor($trainer);
    coachFor(trainer());
    invite($trainer, 'pending@example.com');

    $this->actingAs($trainer)->get('/trainer/coaches')->assertInertia(fn ($page) => $page
        ->component('Trainer/Coaches/Index')
        ->has('coaches', 1)
        ->where('coaches.0.id', $mine->id)
        ->has('invitations', 1)
        ->where('invitations.0.status', 'pending'));
});

it('lets a trainer remove their coach but not someone elses', function () {
    $trainer = trainer();
    $mine = coachFor($trainer);
    $theirs = coachFor(trainer());

    $this->actingAs($trainer)->delete("/trainer/coaches/{$theirs->id}")->assertNotFound();
    $this->delete("/trainer/coaches/{$mine->id}")->assertRedirect();

    expect($mine->coachProfile->fresh()->status)->toBe(ActiveStatus::Inactive)->and($theirs->coachProfile->fresh()->status)->toBe(ActiveStatus::Active);
});

it('allows a coach only one trainer by database constraint', function () {
    $coach = coachFor(trainer());

    expect(fn () => CoachProfile::query()->create(['user_id' => $coach->id, 'trainer_id' => trainer()->id]))
        ->toThrow(QueryException::class);
});
