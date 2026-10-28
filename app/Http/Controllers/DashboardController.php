<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Trainer\GetPlayerShareLink;
use App\Enums\ActiveStatus;
use App\Enums\ApprovalStatus;
use App\Enums\ShareLinkType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ChildPurchaseApproval;
use App\Models\CoachProfile;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\Availability\AvailabilityInsights;
use App\Services\ContextResolver;
use App\Support\AvailabilitySummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ContextResolver $contexts): Response
    {
        $user = $request->user();

        return match ($user->role) {
            UserRole::SuperAdmin => $this->admin(),
            UserRole::Trainer => $this->trainer($user),
            UserRole::Coach => $this->coach($user),
            UserRole::Player => $this->player($user, $contexts),
        };
    }

    private function admin(): Response
    {
        return Inertia::render('Dashboards/Admin', [
            'stats' => [
                'total' => User::query()->where('status', '!=', UserStatus::Deleted)->count(),
                'trainers' => User::query()->where('role', UserRole::Trainer)->where('status', UserStatus::Active)->count(),
                'coaches' => User::query()->where('role', UserRole::Coach)->where('status', UserStatus::Active)->count(),
                'players' => User::query()->where('role', UserRole::Player)->where('status', UserStatus::Active)->count(),
                'inactive' => User::query()->where('status', UserStatus::Inactive)->count(),
            ],
        ]);
    }

    private function trainer(User $trainer): Response
    {
        $insights = app(AvailabilityInsights::class);

        return Inertia::render('Dashboards/Trainer', [
            'stats' => [
                'players' => PlayerProfile::query()->forTrainer($trainer->id)->count(),
                'coaches' => CoachProfile::query()->where('trainer_id', $trainer->id)->where('status', ActiveStatus::Active)->count(),
                'pending_invitations' => ShareLink::query()
                    ->where('trainer_id', $trainer->id)
                    ->where('type', ShareLinkType::Coach)
                    ->where('is_active', true)
                    ->where('uses_count', 0)
                    ->where('expires_at', '>', now())
                    ->count(),
            ],
            'share_link_url' => app(GetPlayerShareLink::class)->handle($trainer)->url,
            'best_windows' => $insights->bestWindows($trainer->id, 3)->map(fn (array $window) => [
                ...$window,
                'label' => AvailabilitySummary::dayName($window['day_of_week']).' '.AvailabilitySummary::range($window['start'], $window['end']),
            ])->all(),
        ]);
    }

    private function coach(User $coach): Response
    {
        $profile = $coach->coachProfile?->load('trainer.trainerProfile');

        return Inertia::render('Dashboards/Coach', [
            'trainer_name' => $profile?->status === ActiveStatus::Active ? $profile->trainer->trainerProfile?->business_name : null,
            'availability_set' => $coach->availabilitySlots()->exists(),
        ]);
    }

    private function player(User $user, ContextResolver $contexts): Response
    {
        $context = $contexts->current($user);

        return Inertia::render('Dashboards/Player', [
            'is_child' => $user->isChildAccount(),
            'current' => $context === null ? null : [
                'profile_name' => $context->profile->name,
                'trainer_name' => $context->trainerName(),
                'is_child_profile' => $context->profile->is_child,
            ],
            'trainer_count' => count($user->trainerIds()),
            'pending_approvals' => $user->isChildAccount() ? 0 : ChildPurchaseApproval::query()
                ->where('parent_id', $user->id)
                ->where('status', ApprovalStatus::Pending)
                ->count(),
            'my_pending_requests' => $user->isChildAccount() ? ChildPurchaseApproval::query()
                ->where('requested_by', $user->id)
                ->where('status', ApprovalStatus::Pending)
                ->count() : 0,
            'has_profiles' => $user->familyProfileIds() !== [],
        ]);
    }
}
