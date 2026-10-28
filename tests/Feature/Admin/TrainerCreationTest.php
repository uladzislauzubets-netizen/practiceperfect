<?php

declare(strict_types=1);

use App\Actions\Users\CreateTrainerAccount;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TrainerAccountCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function trainerPayload(array $overrides = []): array
{
    return [
        'role' => 'trainer',
        'business_name' => 'Elite Basketball Academy',
        'first_name' => 'Mia',
        'last_name' => 'Rivera',
        'email' => 'mia@example.com',
        'phone' => '+1 555 010 2030',
        'delivery' => CreateTrainerAccount::DELIVERY_INVITE_LINK,
        ...$overrides,
    ];
}

it('lets a super admin create a trainer with a setup link', function () {
    Notification::fake();
    $admin = admin();

    $this->actingAs($admin)->post('/admin/users', trainerPayload())->assertRedirect(route('admin.users.index'));

    $trainer = User::query()->where('email', 'mia@example.com')->firstOrFail();
    expect($trainer->role)->toBe(UserRole::Trainer)
        ->and($trainer->isActive())->toBeTrue()
        ->and($trainer->trainerProfile->business_name)->toBe('Elite Basketball Academy')
        ->and($trainer->must_change_password)->toBeTrue();

    Notification::assertSentTo($trainer, TrainerAccountCreated::class, function (TrainerAccountCreated $notification) use ($trainer) {
        $mail = $notification->toMail($trainer);

        return str_contains((string) $mail->actionUrl, '/setup-password/'.$trainer->id);
    });

    expect(AuditLog::query()->where('action', 'trainer.created')->where('actor_id', $admin->id)->exists())->toBeTrue();
});

it('creates a trainer with a temporary password that must be changed on first login', function () {
    Notification::fake();
    $captured = null;

    $this->actingAs(admin())->post('/admin/users', trainerPayload(['delivery' => CreateTrainerAccount::DELIVERY_TEMPORARY_PASSWORD]));

    $trainer = User::query()->where('email', 'mia@example.com')->firstOrFail();

    Notification::assertSentTo($trainer, TrainerAccountCreated::class, function (TrainerAccountCreated $notification) use ($trainer, &$captured) {
        $lines = $notification->toMail($trainer)->introLines;
        $captured = $lines[2];

        return true;
    });

    expect(Hash::check($captured, $trainer->password))->toBeTrue();

    auth()->logout();
    $this->post('/login', ['email' => 'mia@example.com', 'password' => $captured])->assertRedirect();
    $this->get('/dashboard')->assertRedirect(route('password.change'));
});

it('forces the new trainer to pick a password before anything else, then verifies the email', function () {
    $trainer = User::factory()->trainer()->create(['must_change_password' => true, 'email_verified_at' => null]);

    $this->actingAs($trainer)->get('/dashboard')->assertRedirect(route('password.change'));
    $this->actingAs($trainer)->get('/password/change')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/ChangePassword'));

    $this->post('/password/change', [
        'current_password' => 'password',
        'password' => 'BrandNewPass1',
        'password_confirmation' => 'BrandNewPass1',
    ])->assertRedirect(route('dashboard'));

    $trainer->refresh();
    expect($trainer->must_change_password)->toBeFalse()
        ->and($trainer->hasVerifiedEmail())->toBeTrue();
    $this->get('/dashboard')->assertOk();
});

it('lets the trainer finish setup from the invitation link and invalidates it afterwards', function () {
    Notification::fake();
    $this->actingAs(admin())->post('/admin/users', trainerPayload());
    $trainer = User::query()->where('email', 'mia@example.com')->firstOrFail();
    auth()->logout();

    $url = URL::temporarySignedRoute('password.setup', now()->addDay(), ['user' => $trainer->id]);

    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page->component('Auth/SetupPassword'));

    $this->post($url, ['password' => 'ChosenPass123', 'password_confirmation' => 'ChosenPass123'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($trainer);

    $trainer->refresh();
    expect($trainer->must_change_password)->toBeFalse()->and($trainer->hasVerifiedEmail())->toBeTrue();

    auth()->logout();
    $this->get($url)->assertForbidden();
});

it('refuses setup links that are unsigned', function () {
    $trainer = User::factory()->trainer()->create(['must_change_password' => true]);

    $this->get('/setup-password/'.$trainer->id)->assertForbidden();
});

it('shows a clear error for a duplicate email', function () {
    User::factory()->create(['email' => 'mia@example.com']);

    $this->actingAs(admin())->post('/admin/users', trainerPayload())
        ->assertSessionHasErrors(['email' => 'A user with this email already exists.']);
});

it('requires every field', function () {
    $this->actingAs(admin())->post('/admin/users', [])->assertSessionHasErrors(['business_name', 'first_name', 'last_name', 'email', 'phone', 'delivery', 'role']);
});

it('validates the phone format', function () {
    $this->actingAs(admin())->post('/admin/users', trainerPayload(['phone' => 'call me maybe']))->assertSessionHasErrors('phone');
});

it('only allows the trainer role to be created here', function () {
    $this->actingAs(admin())->post('/admin/users', trainerPayload(['role' => 'super_admin']))->assertSessionHasErrors('role');
});

it('forbids everyone except super admins', function (string $who) {
    $user = match ($who) {
        'trainer' => trainer(),
        'coach' => coachFor(trainer()),
        'player' => player(),
    };

    $this->actingAs($user)->post('/admin/users', trainerPayload())->assertForbidden();
    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
})->with(['trainer', 'coach', 'player']);

it('has no public trainer self registration', function () {
    $this->post('/register', trainerPayload())->assertNotFound();
    $this->get('/register')->assertNotFound();
});
