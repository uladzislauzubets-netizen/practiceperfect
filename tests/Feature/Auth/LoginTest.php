<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

it('shows the login page to guests', function () {
    $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('logs in an active user and records the login time', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('blocks deactivated accounts with a clear message', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Account deactivated. Contact support.']);

    $this->assertGuest();
});

it('rate limits repeated failed logins', function () {
    $user = User::factory()->create();
    RateLimiter::clear(strtolower($user->email).'|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => $user->email, 'password' => 'nope']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs a user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
});

it('redirects guests away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('kicks out a user who is deactivated mid session', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->forceFill(['status' => UserStatus::Inactive])->save();

    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('sends each role to a dashboard component for that role', function (string $role, string $component) {
    $user = match ($role) {
        'admin' => admin(),
        'trainer' => trainer(),
        'coach' => coachFor(trainer()),
        'player' => player(),
    };

    $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page->component($component));
})->with([
    'super admin' => ['admin', 'Dashboards/Admin'],
    'trainer' => ['trainer', 'Dashboards/Trainer'],
    'coach' => ['coach', 'Dashboards/Coach'],
    'player' => ['player', 'Dashboards/Player'],
]);
