<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

it('emails a reset link to active users', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether an email exists', function () {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('success');

    Notification::assertNothingSent();
});

it('does not send reset links to deactivated users', function () {
    Notification::fake();
    $user = User::factory()->inactive()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->unverified()->create();
    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->email_verified_at)->not->toBeNull();
});

it('rejects reset tokens older than one hour', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->travel(61)->minutes();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('enforces the password policy on reset', function () {
    $user = User::factory()->create();

    $this->post('/reset-password', [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});

it('keeps unverified users on the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get('/email/verify')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/VerifyEmail'));
});

it('verifies the email through a signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('lets verification links live for 24 hours', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('success');

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return abs((int) $query['expires'] - now()->addDay()->timestamp) < 5;
    });
});

it('rejects expired verification links', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
