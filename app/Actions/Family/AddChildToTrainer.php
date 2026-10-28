<?php

declare(strict_types=1);

namespace App\Actions\Family;

use App\Actions\Join\AssociatePlayersWithTrainer;
use App\Enums\ShareLinkType;
use App\Models\PlayerProfile;
use App\Models\ShareLink;
use App\Models\User;
use App\Services\TrainerAssociations;
use Illuminate\Validation\ValidationException;

final class AddChildToTrainer
{
    public function __construct(
        private readonly AssociatePlayersWithTrainer $associate,
        private readonly TrainerAssociations $associations,
    ) {}

    public function handle(User $parent, PlayerProfile $profile, ?string $shareCode, ?int $trainerId): User
    {
        if ($shareCode !== null && $shareCode !== '') {
            $link = ShareLink::query()
                ->with('trainer')
                ->where('code', $this->extractCode($shareCode))
                ->where('type', ShareLinkType::Player)
                ->first();

            if ($link === null || ! $link->isUsable()) {
                throw ValidationException::withMessages(['share_code' => $link?->state()->message() ?? 'That ShareLink was not found.']);
            }

            $this->associate->handle($link, $parent, collect([$profile]));

            return $link->trainer;
        }

        if ($trainerId === null || ! in_array($trainerId, $parent->trainerIds(), true)) {
            throw ValidationException::withMessages(['trainer_id' => 'Choose one of your trainers or enter a ShareLink.']);
        }

        $trainer = User::query()->findOrFail($trainerId);
        $this->associations->attach($profile, $trainer);

        return $trainer;
    }

    private function extractCode(string $input): string
    {
        $path = parse_url(trim($input), PHP_URL_PATH);

        return strtolower(basename($path !== null && $path !== false ? $path : $input));
    }
}
