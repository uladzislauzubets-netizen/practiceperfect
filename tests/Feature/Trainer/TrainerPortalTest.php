<?php

declare(strict_types=1);

use App\Models\TrainerPlayer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('never leaks one trainers players or coaches to another', function () {
    $mine = trainer('Mine');
    $theirs = trainer('Theirs');
    $myPlayer = User::factory()->player()->create(['first_name' => 'Mine']);
    $theirPlayer = User::factory()->player()->create(['first_name' => 'Theirs']);
    connect($myPlayer->playerProfile, $mine);
    connect($theirPlayer->playerProfile, $theirs);
    $myCoach = coachFor($mine);
    coachFor($theirs);

    $this->actingAs($mine)->get('/trainer/players')->assertInertia(fn ($page) => $page
        ->has('players', 1)->where('players.0.id', $myPlayer->playerProfile->id));
    $this->get('/trainer/coaches')->assertInertia(fn ($page) => $page->has('coaches', 1)->where('coaches.0.id', $myCoach->id));
    $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('stats.players', 1)->where('stats.coaches', 1));
});

it('prevents a trainer from touching players that belong to someone else', function () {
    $mine = trainer();
    $theirs = trainer();
    $theirPlayer = User::factory()->player()->create();
    connect($theirPlayer->playerProfile, $theirs);

    $this->actingAs($mine)->patch("/trainer/players/{$theirPlayer->playerProfile->id}", ['skill_level' => 'Elite'])->assertForbidden();
    $this->delete("/trainer/players/{$theirPlayer->playerProfile->id}")->assertForbidden();

    expect($theirPlayer->playerProfile->fresh()->skill_level)->toBeNull()->and(TrainerPlayer::query()->count())->toBe(1);
});

it('lets the trainer set a skill level and remove a player from the roster', function () {
    $trainer = trainer();
    $player = User::factory()->player()->create();
    connect($player->playerProfile, $trainer);

    $this->actingAs($trainer)->patch("/trainer/players/{$player->playerProfile->id}", ['skill_level' => 'Intermediate'])->assertRedirect();
    expect($player->playerProfile->fresh()->skill_level)->toBe('Intermediate');

    $this->get('/trainer/players')->assertInertia(fn ($page) => $page->where('players.0.skill_level', 'Intermediate'));

    $this->delete("/trainer/players/{$player->playerProfile->id}")->assertRedirect();
    $this->get('/trainer/players')->assertInertia(fn ($page) => $page->has('players', 0));
    expect($player->fresh()->isActive())->toBeTrue();
});

it('shows the trainer their static share link with usage', function () {
    $trainer = trainer();
    $link = playerLinkFor($trainer);
    $link->increment('uses_count');

    $this->actingAs($trainer)->get('/trainer/share-link')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Trainer/ShareLink')
        ->where('url', url("/join/{$link->code}"))
        ->where('uses_count', 1));
});

it('only trainers can use trainer pages', function (string $who) {
    $user = match ($who) {
        'admin' => admin(), 'coach' => coachFor(trainer()), 'player' => player(),
    };

    $this->actingAs($user);
    foreach (['/trainer/players', '/trainer/coaches', '/trainer/share-link', '/trainer/branding'] as $url) {
        $this->get($url)->assertForbidden();
    }
})->with(['admin', 'coach', 'player']);

it('saves a logo and brand color which players and coaches see immediately', function () {
    $trainer = trainer('Elite');
    $player = User::factory()->player()->create();
    connect($player->playerProfile, $trainer);
    $coach = coachFor($trainer);

    $this->actingAs($trainer)->post('/trainer/branding', [
        'brand_color' => '#ff8800',
        'logo' => UploadedFile::fake()->image('logo.png', 600, 400),
    ])->assertRedirect()->assertSessionHas('success');

    $profile = $trainer->trainerProfile->fresh();
    expect($profile->brand_color)->toBe('#FF8800')->and($profile->logo_path)->toStartWith('logos/')->and($profile->logo_path)->toEndWith('.png');
    Storage::disk('local')->assertExists($profile->logo_path);
    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($profile->logo_path));
    expect(max($width, $height))->toBe(200);

    foreach ([$player, $coach, $trainer] as $viewer) {
        $this->actingAs($viewer)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('branding.color', '#FF8800')
            ->where('branding.logo_url', url('/media/logo/'.basename($profile->logo_path))));
    }

    auth()->logout();
    $this->get('/media/logo/'.basename($profile->logo_path))->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('does not enlarge small logos', function () {
    $trainer = trainer();

    $this->actingAs($trainer)->post('/trainer/branding', ['logo' => UploadedFile::fake()->image('small.png', 64, 64)]);

    [$width] = getimagesizefromstring(Storage::disk('local')->get($trainer->trainerProfile->fresh()->logo_path));
    expect($width)->toBe(64);
});

it('accepts svg logos and serves them sandboxed', function () {
    $trainer = trainer();
    $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>');

    $this->actingAs($trainer)->post('/trainer/branding', ['logo' => $svg])->assertSessionHasNoErrors();

    $path = $trainer->trainerProfile->fresh()->logo_path;
    expect($path)->toEndWith('.svg');
    $this->get('/media/logo/'.basename($path))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
});

it('rejects invalid logos and colors', function () {
    $trainer = trainer();
    $this->actingAs($trainer);

    $this->post('/trainer/branding', ['logo' => UploadedFile::fake()->image('big.png')->size(3000)])->assertSessionHasErrors('logo');
    $this->post('/trainer/branding', ['logo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])->assertSessionHasErrors('logo');
    $this->post('/trainer/branding', ['logo' => UploadedFile::fake()->createWithContent('evil.svg', '<html><script>alert(1)</script></html>')])->assertSessionHasErrors('logo');
    $this->post('/trainer/branding', ['brand_color' => 'red'])->assertSessionHasErrors('brand_color');
    $this->post('/trainer/branding', ['brand_color' => '#12345'])->assertSessionHasErrors('brand_color');
    expect($trainer->trainerProfile->fresh()->logo_path)->toBeNull();
});

it('resets the color and removes the logo', function () {
    $trainer = trainer();
    $this->actingAs($trainer)->post('/trainer/branding', ['brand_color' => '#123456', 'logo' => UploadedFile::fake()->image('l.png', 50, 50)]);
    $path = $trainer->trainerProfile->fresh()->logo_path;

    $this->post('/trainer/branding/reset')->assertRedirect();
    expect($trainer->trainerProfile->fresh()->brand_color)->toBeNull();

    $this->post('/trainer/branding', ['brand_color' => '#123456', 'remove_logo' => true]);
    expect($trainer->trainerProfile->fresh()->logo_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('refuses path traversal and unknown media files', function () {
    $this->get('/media/logo/..%2F..%2F.env')->assertNotFound();
    $this->get('/media/logo/missing.png')->assertNotFound();
    $this->get('/media/photo/anything.jpg')->assertRedirect(route('login'));
});
