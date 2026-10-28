<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\UserStatus;
use App\Models\PlayerProfile;
use App\Models\TrainerPlayer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ContextResolver
{
    /** @var array<int, Collection<int, PlayerContext>> */
    private array $cache = [];

    /** @return Collection<int, PlayerContext> */
    public function options(User $user): Collection
    {
        if (! $user->isPlayer()) {
            return collect();
        }

        return $this->cache[$user->id] ??= TrainerPlayer::query()
            ->with(['playerProfile', 'trainer.trainerProfile'])
            ->whereIn('player_profile_id', $user->familyProfileIds())
            ->where('status', ActiveStatus::Active)
            ->whereHas('trainer', fn (Builder $trainers) => $trainers->where('status', UserStatus::Active))
            ->get()
            ->map(fn (TrainerPlayer $association) => new PlayerContext($association->playerProfile, $association->trainer))
            ->sortBy(fn (PlayerContext $context) => [
                $context->profile->is_child ? 1 : 0,
                $context->profile->first_name,
                $context->trainerName(),
            ])
            ->values();
    }

    public function current(User $user): ?PlayerContext
    {
        $options = $this->options($user);
        $stored = $user->active_context;

        if (is_array($stored)) {
            $match = $options->first(fn (PlayerContext $context) => $context->profile->id === ($stored['profile_id'] ?? null)
                && $context->trainer->id === ($stored['trainer_id'] ?? null));

            if ($match !== null) {
                return $match;
            }
        }

        return $options->first();
    }

    public function switch(User $user, int $profileId, int $trainerId): PlayerContext
    {
        $context = $this->options($user)->first(fn (PlayerContext $option) => $option->profile->id === $profileId
            && $option->trainer->id === $trainerId);

        if ($context === null) {
            throw new AuthorizationException('That context is not available for this account.');
        }

        $this->remember($user, $context);

        return $context;
    }

    public function remember(User $user, PlayerContext $context): void
    {
        $user->forceFill(['active_context' => $context->toStored()])->save();
    }

    public function forget(User $user): void
    {
        unset($this->cache[$user->id]);
    }

    public function trainerFor(User $user): ?User
    {
        return match (true) {
            $user->isTrainer() => $user,
            $user->isCoach() => $user->coachProfile?->status === ActiveStatus::Active ? $user->coachProfile->trainer : null,
            $user->isPlayer() => $this->current($user)?->trainer,
            default => null,
        };
    }

    public function profileFor(User $user): ?PlayerProfile
    {
        return $user->isPlayer() ? $this->current($user)?->profile : null;
    }

    /** @return array{current: ?string, groups: array<int, array{title: string, options: array<int, array{profile_id: int, trainer_id: int, label: string, key: string}>}>} */
    public function forFrontend(User $user): array
    {
        $options = $this->options($user);
        $current = $this->current($user);

        $format = fn (Collection $contexts) => $contexts->map(fn (PlayerContext $context) => [
            'profile_id' => $context->profile->id,
            'trainer_id' => $context->trainer->id,
            'label' => $context->label($user),
            'key' => $context->key(),
        ])->values()->all();

        $groups = [];

        if ($user->isChildAccount()) {
            $groups[] = ['title' => 'Your Training', 'options' => $format($options)];
        } else {
            $own = $options->filter(fn (PlayerContext $context) => ! $context->profile->is_child);
            $children = $options->filter(fn (PlayerContext $context) => $context->profile->is_child);

            if ($own->isNotEmpty()) {
                $groups[] = ['title' => 'Your Training', 'options' => $format($own)];
            }

            if ($children->isNotEmpty()) {
                $groups[] = ['title' => "Your Children's Training", 'options' => $format($children)];
            }
        }

        return [
            'current' => $current?->key(),
            'current_label' => $current?->label($user),
            'groups' => $groups,
        ];
    }
}
