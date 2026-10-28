<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainer;

use App\Actions\Trainer\InviteCoach;
use App\Enums\ActiveStatus;
use App\Enums\ShareLinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\InviteCoachRequest;
use App\Models\CoachProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Support\AvailabilitySummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoachController extends Controller
{
    public function index(Request $request): Response
    {
        $trainer = $request->user();

        $coaches = CoachProfile::query()
            ->with(['user.availabilitySlots'])
            ->where('trainer_id', $trainer->id)
            ->where('status', ActiveStatus::Active)
            ->get()
            ->map(fn (CoachProfile $profile) => [
                'id' => $profile->user->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'phone' => $profile->user->phone,
                'joined_at' => $profile->joined_trainer_at?->toIso8601String(),
                'times' => AvailabilitySummary::format($profile->user->availabilitySlots),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        $invitations = ShareLink::query()
            ->where('trainer_id', $trainer->id)
            ->where('type', ShareLinkType::Coach)
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('uses_count', '>', 0))
            ->latest()
            ->get()
            ->map(fn (ShareLink $link) => [
                'id' => $link->id,
                'email' => $link->target_email,
                'name' => $link->invitee_name,
                'status' => $link->invitationStatus(),
                'expires_at' => $link->expires_at?->toIso8601String(),
                'created_at' => $link->created_at->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Trainer/Coaches/Index', ['coaches' => $coaches, 'invitations' => $invitations]);
    }

    public function invite(InviteCoachRequest $request, InviteCoach $invite): RedirectResponse
    {
        $invite->handle(
            $request->user(),
            $request->string('email')->toString(),
            $request->input('name'),
            $request->input('message'),
        );

        return back()->with('success', 'Invitation sent. The link works once and expires in '.ShareLink::COACH_LINK_VALID_DAYS.' days.');
    }

    public function resend(Request $request, ShareLink $link, InviteCoach $invite): RedirectResponse
    {
        abort_unless(
            $link->trainer_id === $request->user()->id && $link->type === ShareLinkType::Coach && $link->uses_count === 0,
            403,
        );

        $invite->handle($request->user(), (string) $link->target_email, $link->invitee_name, $link->message);

        return back()->with('success', 'Invitation resent with a fresh 7 day link.');
    }

    public function destroy(Request $request, User $coach): RedirectResponse
    {
        $profile = CoachProfile::query()
            ->where('user_id', $coach->id)
            ->where('trainer_id', $request->user()->id)
            ->where('status', ActiveStatus::Active)
            ->firstOrFail();

        $profile->update(['status' => ActiveStatus::Inactive]);

        return back()->with('success', "{$coach->name} was removed from your organization.");
    }
}
