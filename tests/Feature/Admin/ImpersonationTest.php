<?php

declare(strict_types=1);

use App\Models\ImpersonationAction;
use App\Models\ImpersonationLog;
use App\Models\User;

it('starts impersonation, shows the target view and logs the session', function () {
    $admin = admin();
    $trainer = trainer();

    $this->actingAs($admin)->post("/admin/users/{$trainer->id}/impersonate")->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($trainer);
    $log = ImpersonationLog::query()->firstOrFail();
    expect($log->admin_id)->toBe($admin->id)
        ->and($log->impersonated_id)->toBe($trainer->id)
        ->and($log->ended_at)->toBeNull()
        ->and((int) $log->started_at->diffInMinutes($log->expires_at))->toBe(60);

    $this->get('/dashboard')->assertInertia(fn ($page) => $page
        ->component('Dashboards/Trainer')
        ->where('impersonation.target_name', $trainer->name));
});

it('cannot impersonate another super admin, yourself or an inactive user', function () {
    $admin = admin();

    $this->actingAs($admin)->post('/admin/users/'.admin()->id.'/impersonate')->assertForbidden();
    $this->post("/admin/users/{$admin->id}/impersonate")->assertForbidden();
    $this->post('/admin/users/'.User::factory()->inactive()->create()->id.'/impersonate')->assertForbidden();
    expect(ImpersonationLog::query()->count())->toBe(0);
});

it('ends impersonation and records the duration', function () {
    $admin = admin();
    $player = player();

    $this->actingAs($admin)->post("/admin/users/{$player->id}/impersonate");
    $this->travel(5)->minutes();

    $this->post('/impersonation/stop')->assertRedirect(route('admin.users.index'));

    $this->assertAuthenticatedAs($admin);
    $log = ImpersonationLog::query()->firstOrFail();
    expect($log->ended_at)->not->toBeNull()->and($log->duration_seconds)->toBe(300);
    $this->get('/dashboard')->assertInertia(fn ($page) => $page->component('Dashboards/Admin')->where('impersonation', null));
});

it('expires an impersonation session after one hour', function () {
    $admin = admin();
    $player = player();

    $this->actingAs($admin)->post("/admin/users/{$player->id}/impersonate");
    $this->travel(61)->minutes();

    $this->get('/dashboard')->assertRedirect(route('admin.users.index'));

    $this->assertAuthenticatedAs($admin);
    $log = ImpersonationLog::query()->firstOrFail();
    expect($log->duration_seconds)->toBe(3600);
});

it('logs state changing actions with the admin context', function () {
    $admin = admin();
    $trainer = trainer();

    $this->actingAs($admin)->post("/admin/users/{$trainer->id}/impersonate");
    $this->post('/trainer/branding', ['brand_color' => '#112233'])->assertRedirect();
    $this->get('/trainer/branding');

    $actions = ImpersonationAction::query()->get();
    expect($actions)->toHaveCount(1)
        ->and($actions->first()->method)->toBe('POST')
        ->and($actions->first()->path)->toBe('/trainer/branding');
    expect($trainer->trainerProfile->fresh()->brand_color)->toBe('#112233');
});

it('does not force a temporary password change while impersonating', function () {
    $admin = admin();
    $trainer = User::factory()->trainer()->create(['must_change_password' => true]);

    $this->actingAs($admin)->post("/admin/users/{$trainer->id}/impersonate");

    $this->get('/dashboard')->assertOk();
});

it('shows the impersonation history to admins only', function () {
    $admin = admin();
    $player = player();
    $this->actingAs($admin)->post("/admin/users/{$player->id}/impersonate");
    $this->post('/impersonation/stop');

    $this->get('/admin/impersonation-history')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Admin/Impersonation/History')
        ->has('logs.data', 1)
        ->where('logs.data.0.impersonated', $player->name));

    $log = ImpersonationLog::query()->firstOrFail();
    $this->get("/admin/impersonation-history/{$log->id}")->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Impersonation/Show'));

    $this->actingAs(trainer())->get('/admin/impersonation-history')->assertForbidden();
});

it('refuses to stop when not impersonating', function () {
    $this->actingAs(admin())->post('/impersonation/stop')->assertForbidden();
});

it('closes abandoned impersonation logs through the scheduled command', function () {
    $admin = admin();
    $player = player();
    $log = ImpersonationLog::query()->create([
        'admin_id' => $admin->id, 'impersonated_id' => $player->id,
        'started_at' => now()->subHours(3), 'expires_at' => now()->subHours(2),
    ]);

    $this->artisan('impersonation:close-expired')->assertSuccessful();

    expect($log->fresh()->ended_at)->not->toBeNull()->and($log->fresh()->duration_seconds)->toBe(3600);
});
