<?php

declare(strict_types=1);

use App\Enums\ActiveStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\UserDeletion;
use Illuminate\Support\Facades\DB;

it('lists users with a tool specific search', function () {
    $admin = admin();
    User::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Fitzgerald', 'email' => 'zelda@example.com']);
    User::factory()->create(['first_name' => 'Other', 'last_name' => 'Person']);

    $this->actingAs($admin)->get('/admin/users?q=zelda')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users.data', 1)
        ->where('users.data.0.email', 'zelda@example.com'));

    $this->get('/admin/users?q=Zelda Fitz')->assertInertia(fn ($page) => $page->has('users.data', 1));
    $this->get('/admin/users?q=%25')->assertInertia(fn ($page) => $page->has('users.data', 0));
});

it('filters by role and status', function () {
    $admin = admin();
    trainer();
    User::factory()->inactive()->create();

    $this->actingAs($admin)->get('/admin/users?role=trainer')->assertInertia(fn ($page) => $page->has('users.data', 1));
    $this->get('/admin/users?status=inactive')->assertInertia(fn ($page) => $page->has('users.data', 1));
    $this->get('/admin/users?role=wizard')->assertSessionHasErrors('role');
});

it('edits an account and role profile and logs the change', function () {
    $admin = admin();
    $trainer = trainer('Old Name');

    $this->actingAs($admin)->patch("/admin/users/{$trainer->id}", [
        'first_name' => 'Nora',
        'last_name' => 'Quinn',
        'email' => 'nora@example.com',
        'phone' => '+1 555 777 8888',
        'business_name' => 'New Name',
        'website' => 'https://example.com',
    ])->assertRedirect(route('admin.users.index'));

    $trainer->refresh();
    expect($trainer->first_name)->toBe('Nora')
        ->and($trainer->email)->toBe('nora@example.com')
        ->and($trainer->trainerProfile->business_name)->toBe('New Name');
    expect(AuditLog::query()->where('action', 'user.updated')->exists())->toBeTrue();
});

it('keeps emails unique when editing', function () {
    $taken = User::factory()->create(['email' => 'taken@example.com']);
    $trainer = trainer();

    $this->actingAs(admin())->patch("/admin/users/{$trainer->id}", [
        'first_name' => 'A', 'last_name' => 'B', 'email' => $taken->email, 'business_name' => 'X',
    ])->assertSessionHasErrors('email');
});

it('deactivates a user, ends their sessions and preserves history', function () {
    $admin = admin();
    $user = player();
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($admin)->post("/admin/users/{$user->id}/deactivate")->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::Inactive)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(PlayerProfile::query()->where('user_id', $user->id)->exists())->toBeTrue();

    auth()->logout();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Account deactivated. Contact support.']);
});

it('reactivates a deactivated user', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs(admin())->post("/admin/users/{$user->id}/reactivate")->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::Active)->and($user->fresh()->deactivated_at)->toBeNull();
    auth()->logout();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

it('does not let an admin deactivate or delete themselves', function () {
    $admin = admin();

    $this->actingAs($admin)->post("/admin/users/{$admin->id}/deactivate")->assertForbidden();
    $this->delete("/admin/users/{$admin->id}", ['reason' => 'because'])->assertForbidden();
});

it('anonymizes a deleted user but keeps history and a compliance record', function () {
    $admin = admin();
    $trainer = trainer();
    $user = User::factory()->player()->create(['first_name' => 'Pat', 'last_name' => 'Doe', 'email' => 'pat@example.com', 'phone' => '+1 555 000 1111']);
    $profile = $user->playerProfile;
    connect($profile, $trainer);
    $created = ShareLink::query()->create(['code' => 'delcode123', 'type' => 'coach', 'trainer_id' => $trainer->id, 'created_by' => $user->id, 'max_uses' => 1, 'expires_at' => now()->addDay()]);

    $this->actingAs($admin)->delete("/admin/users/{$user->id}", ['reason' => 'GDPR request from user'])->assertRedirect(route('admin.users.index'));

    $user->refresh();
    expect($user->status)->toBe(UserStatus::Deleted)
        ->and($user->name)->toBe('Deleted User')
        ->and($user->email)->toBe("deleted_{$user->id}@example.com")
        ->and($user->phone)->toBeNull()
        ->and($user->photo_path)->toBeNull();

    $profile->refresh();
    expect($profile->name)->toBe('Deleted User')
        ->and($profile->activeAssociations()->count())->toBe(1)
        ->and($created->fresh()->is_active)->toBeFalse();

    $record = UserDeletion::query()->where('original_user_id', $user->id)->firstOrFail();
    expect($record->original_email)->toBe('pat@example.com')
        ->and($record->deleted_by)->toBe($admin->id)
        ->and($record->reason)->toBe('GDPR request from user')
        ->and($record->original_data['user']['first_name'])->toBe('Pat');
    expect(DB::table('user_deletions')->value('original_data'))->not->toContain('Pat');

    auth()->logout();
    $this->post('/login', ['email' => 'pat@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->actingAs($admin)->post("/admin/users/{$user->id}/reactivate")->assertForbidden();
    $this->patch("/admin/users/{$user->id}", ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com'])->assertForbidden();
});

it('requires a reason when deleting', function () {
    $user = player();

    $this->actingAs(admin())->delete("/admin/users/{$user->id}")->assertSessionHasErrors('reason');

    expect($user->fresh()->status)->toBe(UserStatus::Active);
});

it('anonymizes a parent together with their children and child logins', function () {
    $parent = User::factory()->create(['first_name' => 'Pam']);
    $child = PlayerProfile::factory()->childOf($parent)->create(['first_name' => 'Kid']);
    $childAccount = User::factory()->childAccountOf($parent)->create(['first_name' => 'Kiddo']);

    $this->actingAs(admin())->delete("/admin/users/{$parent->id}", ['reason' => 'Parent asked for erasure'])->assertRedirect();

    expect($child->fresh()->name)->toBe('Deleted User')
        ->and($childAccount->fresh()->status)->toBe(UserStatus::Deleted)
        ->and(UserDeletion::query()->count())->toBe(2);
    expect(UserDeletion::query()->where('original_user_id', $childAccount->id)->first()->original_data['player_profiles'][0]['first_name'])->not->toBe('Deleted');
});

it('shows impersonate buttons only where allowed', function () {
    $admin = admin();
    $other = admin();
    $player = player();

    $this->actingAs($admin)->get('/admin/users?q='.urlencode($other->email))->assertInertia(fn ($page) => $page->where('users.data.0.can.impersonate', false));
    $this->get('/admin/users?q='.urlencode($player->email))->assertInertia(fn ($page) => $page->where('users.data.0.can.impersonate', true));
});

it('cleans up trainer coach profile state for deleted coaches', function () {
    $trainer = trainer();
    $coach = coachFor($trainer);

    $this->actingAs(admin())->delete("/admin/users/{$coach->id}", ['reason' => 'Coach asked for deletion'])->assertRedirect();

    expect($coach->coachProfile->fresh()->status)->toBe(ActiveStatus::Inactive)->and($coach->coachProfile->fresh()->bio)->toBeNull();
});
