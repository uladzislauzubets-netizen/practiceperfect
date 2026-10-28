<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Join\AcceptCoachInvitation;
use App\Actions\Join\AssociatePlayersWithTrainer;
use App\Actions\Join\NotifyParentOfChildLinkClick;
use App\Actions\Join\RegisterCoachViaInvitation;
use App\Actions\Join\RegisterPlayerViaShareLink;
use App\Enums\ActiveStatus;
use App\Enums\ShareLinkState;
use App\Enums\ShareLinkType;
use App\Http\Requests\Join\AssociateProfilesRequest;
use App\Http\Requests\Join\RegisterCoachRequest;
use App\Http\Requests\Join\RegisterPlayerRequest;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\BrandingResolver;
use App\Services\CampConversion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class JoinController extends Controller
{
    public function __construct(
        private readonly BrandingResolver $branding,
        private readonly CampConversion $camp,
    ) {}

    public function show(Request $request, string $code): Response|RedirectResponse|SymfonyResponse
    {
        $link = ShareLink::findByCode($code);

        if ($link === null) {
            return $this->unavailable('This link was not found.', null, 404);
        }

        if ($link->state() !== ShareLinkState::Valid) {
            return $this->unavailable($link->state()->message(), $link, 410);
        }

        return $link->type === ShareLinkType::Coach
            ? $this->showCoach($request, $link)
            : $this->showPlayer($request, $link);
    }

    public function register(RegisterPlayerRequest $request, string $code, RegisterPlayerViaShareLink $register): RedirectResponse
    {
        $link = $this->usableLink($code, ShareLinkType::Player);
        $prefill = $this->camp->findUsable($request->input('prefill'), $link);

        $user = $register->handle($link, $request->registrationData(), $prefill);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome! You are now connected with '.$this->trainerName($link).'.');
    }

    public function registerCoach(RegisterCoachRequest $request, string $code, RegisterCoachViaInvitation $register): RedirectResponse
    {
        $link = $this->usableLink($code, ShareLinkType::Coach);

        $coach = $register->handle($link, $request->safe()->only(['first_name', 'last_name', 'email', 'password', 'phone']));

        Auth::login($coach);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome! You are now a coach for '.$this->trainerName($link).'.');
    }

    public function associate(AssociateProfilesRequest $request, string $code, AssociatePlayersWithTrainer $associate): RedirectResponse
    {
        $link = $this->usableLink($code, ShareLinkType::Player);
        $user = $request->user();

        $profiles = PlayerProfile::query()
            ->whereIn('id', $request->array('profile_ids'))
            ->whereIn('id', $user->familyProfileIds())
            ->get();

        abort_unless($profiles->count() === count(array_unique($request->array('profile_ids'))), 403);

        $associate->handle($link, $user, $profiles);

        return redirect()->route('dashboard')->with('success', 'Connected with '.$this->trainerName($link).'.');
    }

    public function accept(Request $request, string $code, AcceptCoachInvitation $accept): RedirectResponse
    {
        $link = $this->usableLink($code, ShareLinkType::Coach);

        $accept->handle($link, $request->user());

        return redirect()->route('dashboard')->with('success', 'You are now a coach for '.$this->trainerName($link).'.');
    }

    private function showPlayer(Request $request, ShareLink $link): Response|RedirectResponse|SymfonyResponse
    {
        $user = $request->user();
        $branding = $this->branding->forTrainer($link->trainer);
        $trainerName = $this->trainerName($link);

        if ($user === null) {
            $request->session()->put('url.intended', $request->fullUrl());
            $prefill = $this->camp->findUsable($request->query('prefill'), $link);

            return Inertia::render('Join/RegisterPlayer', [
                'link' => ['code' => $link->code, 'trainer_name' => $trainerName],
                'prefill' => $prefill?->payload,
                'prefill_token' => $prefill?->token,
                'branding' => $branding,
            ]);
        }

        if (! $user->isPlayer()) {
            return $this->unavailable("This link is for players. You are signed in as a {$user->role->label()}.", $link, 403);
        }

        if ($user->isChildAccount()) {
            app(NotifyParentOfChildLinkClick::class)->handle($link, $user);

            return Inertia::render('Join/ChildBlocked', ['trainer_name' => $trainerName, 'branding' => $branding]);
        }

        $profiles = PlayerProfile::query()
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('parent_id', $user->id))
            ->orderBy('is_child')->orderBy('first_name')
            ->get();

        if ($profiles->isEmpty()) {
            return redirect()->route('family.index')->with('error', 'Add your own player profile or a child before joining a trainer.');
        }

        if ($profiles->count() === 1) {
            app(AssociatePlayersWithTrainer::class)->handle($link, $user, $profiles);

            return redirect()->route('dashboard')->with('success', 'Connected with '.$trainerName.'.');
        }

        $connected = $link->trainer->id;

        return Inertia::render('Join/SelectProfiles', [
            'link' => ['code' => $link->code, 'trainer_name' => $trainerName],
            'branding' => $branding,
            'profiles' => $profiles->map(fn (PlayerProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'is_self' => $profile->user_id === $user->id,
                'already_connected' => $profile->activeAssociations()->where('trainer_id', $connected)->exists(),
            ])->all(),
        ]);
    }

    private function showCoach(Request $request, ShareLink $link): Response|SymfonyResponse
    {
        $user = $request->user();
        $branding = $this->branding->forTrainer($link->trainer);
        $details = [
            'code' => $link->code,
            'trainer_name' => $this->trainerName($link),
            'invitee_name' => $link->invitee_name,
            'email' => $link->target_email,
            'message' => $link->message,
        ];

        if ($user === null) {
            $request->session()->put('url.intended', $request->fullUrl());

            return Inertia::render('Join/RegisterCoach', [
                'link' => $details,
                'branding' => $branding,
                'account_exists' => User::query()->where('email', $link->target_email)->exists(),
            ]);
        }

        if (! $user->isCoach()) {
            return $this->unavailable('This invitation is for a coach account.', $link, 403);
        }

        $profile = $user->coachProfile;
        $blocked = $profile?->status === ActiveStatus::Active && $profile->trainer_id !== $link->trainer_id;

        return Inertia::render('Join/AcceptCoach', [
            'link' => $details,
            'branding' => $branding,
            'blocked' => $blocked,
        ]);
    }

    private function unavailable(string $message, ?ShareLink $link, int $status): SymfonyResponse
    {
        return Inertia::render('Join/Unavailable', [
            'message' => $message,
            'branding' => $this->branding->forTrainer($link?->trainer),
        ])->toResponse(request())->setStatusCode($status);
    }

    private function usableLink(string $code, ShareLinkType $type): ShareLink
    {
        $link = ShareLink::findByCode($code);

        abort_if($link === null || $link->type !== $type, 404);

        if (! $link->isUsable()) {
            abort(410, $link->state()->message());
        }

        return $link;
    }

    private function trainerName(ShareLink $link): string
    {
        return $link->trainer->trainerProfile?->business_name ?? $link->trainer->name;
    }
}
