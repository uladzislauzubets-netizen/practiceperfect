<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('lets any user edit common fields but never email or role', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($user)->get('/profile')->assertOk()->assertInertia(fn ($page) => $page->component('Profile/Edit')->where('profile.email', 'keep@example.com'));

    $this->post('/profile', [
        'first_name' => 'New', 'last_name' => 'Name', 'phone' => '+1 555 111 2222',
        'email' => 'hacker@example.com', 'role' => 'super_admin', 'status' => 'inactive',
    ])->assertRedirect();

    $user->refresh();
    expect($user->first_name)->toBe('New')->and($user->phone)->toBe('+1 555 111 2222')
        ->and($user->email)->toBe('keep@example.com')->and($user->role->value)->toBe('player')->and($user->isActive())->toBeTrue();
});

it('validates names and phone format', function () {
    $this->actingAs(User::factory()->create())->post('/profile', ['first_name' => '', 'last_name' => 'X', 'phone' => 'bad'])->assertSessionHasErrors(['first_name', 'phone']);
});

it('uploads a photo, generates a thumbnail and replaces the old files', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo' => UploadedFile::fake()->image('me.jpg', 1000, 700)])->assertSessionHasNoErrors();

    $user->refresh();
    Storage::disk('local')->assertExists([$user->photo_path, $user->photo_thumb_path]);
    [$width] = getimagesizefromstring(Storage::disk('local')->get($user->photo_path));
    expect($width)->toBe(512);
    $old = [$user->photo_path, $user->photo_thumb_path];

    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo' => UploadedFile::fake()->image('new.png', 300, 300)]);

    Storage::disk('local')->assertMissing($old);
    Storage::disk('local')->assertExists($user->fresh()->photo_path);

    $this->get('/media/photo/'.basename($user->fresh()->photo_thumb_path))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'remove_photo' => true]);
    expect($user->fresh()->photo_path)->toBeNull();
});

it('accepts a https photo url but not other schemes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo_url' => 'javascript:alert(1)'])->assertSessionHasErrors('photo_url');
    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo_url' => 'http://example.com/a.png'])->assertSessionHasErrors('photo_url');
    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo_url' => 'https://example.com/a.png'])->assertSessionHasNoErrors();

    expect($user->fresh()->photo_url)->toBe('https://example.com/a.png');
});

it('rejects non image uploads and oversized photos', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('photo');
    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo' => UploadedFile::fake()->image('x.jpg')->size(6000)])->assertSessionHasErrors('photo');
    $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B', 'photo' => UploadedFile::fake()->createWithContent('x.png', 'not really an image')])->assertSessionHasErrors('photo');
});

it('edits role specific fields', function () {
    $trainer = trainer();
    $this->actingAs($trainer)->post('/profile', [
        'first_name' => 'T', 'last_name' => 'R', 'business_name' => 'Hoops Inc', 'address' => '1 Court St', 'website' => 'https://hoops.example', 'description' => 'We train.',
    ]);
    expect($trainer->trainerProfile->fresh()->business_name)->toBe('Hoops Inc')->and($trainer->trainerProfile->fresh()->website)->toBe('https://hoops.example');
    $this->post('/profile', ['first_name' => 'T', 'last_name' => 'R', 'business_name' => ''])->assertSessionHasErrors('business_name');

    $coach = coachFor(trainer());
    $this->actingAs($coach)->post('/profile', [
        'first_name' => 'C', 'last_name' => 'O', 'bio' => 'Ten years of coaching.', 'credentials' => 'USA Basketball', 'certifications' => 'CPR', 'is_public' => true,
    ]);
    expect($coach->coachProfile->fresh()->bio)->toBe('Ten years of coaching.')->and($coach->coachProfile->fresh()->is_public)->toBeTrue();

    $player = User::factory()->player()->create();
    $this->actingAs($player)->post('/profile', [
        'first_name' => 'P', 'last_name' => 'L', 'school' => 'Oak High', 'jersey_number' => '7',
        'emergency_contact_name' => 'Mom', 'emergency_contact_phone' => '+1 555 000 9999', 'emergency_contact_relationship' => 'Mother',
    ]);
    $player->refresh();
    expect($player->playerProfile->school)->toBe('Oak High')->and($player->playerProfile->jersey_number)->toBe('7')
        ->and($player->emergency_contact_name)->toBe('Mom');
});

it('shows the trainer set skill level as read only', function () {
    $trainer = trainer();
    $player = User::factory()->player()->create();
    connect($player->playerProfile, $trainer);
    $player->playerProfile->update(['skill_level' => 'Advanced']);

    $this->actingAs($player)->get('/profile')->assertInertia(fn ($page) => $page->where('profile.skill_level', 'Advanced'));
    $this->post('/profile', ['first_name' => 'P', 'last_name' => 'L', 'skill_level' => 'Elite']);
    expect($player->playerProfile->fresh()->skill_level)->toBe('Advanced');
});

it('limits a child login to photo and basics', function () {
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create(['first_name' => 'Kid', 'last_name' => 'Smith', 'phone' => '+1 555 000 1234']);

    $this->actingAs($child)->get('/profile')->assertInertia(fn ($page) => $page->where('limited', true));

    $this->post('/profile', [
        'first_name' => 'Hacked', 'last_name' => 'Name', 'phone' => '+1 555 999 9999', 'school' => 'Oak', 'jersey_number' => '9',
        'photo' => UploadedFile::fake()->image('kid.jpg', 200, 200), 'photo_url' => 'https://evil.example/x.png',
    ])->assertSessionHasNoErrors();

    $child->refresh();
    expect($child->first_name)->toBe('Kid')->and($child->phone)->toBe('+1 555 000 1234')->and($child->photo_url)->toBeNull()
        ->and($child->photo_path)->not->toBeNull()
        ->and($child->playerProfile->school)->toBe('Oak')->and($child->playerProfile->jersey_number)->toBe('9')
        ->and($child->playerProfile->photo_path)->not->toBeNull();
});

it('lets a child login update only its photo and basics even when sending email or role', function () {
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create(['email' => 'kid@example.com']);

    $this->actingAs($child)->post('/profile', ['email' => 'other@example.com', 'role' => 'trainer', 'parent_id' => null]);

    expect($child->fresh()->email)->toBe('kid@example.com')->and($child->fresh()->parent_id)->toBe($parent->id)->and($child->fresh()->role->value)->toBe('player');
});
