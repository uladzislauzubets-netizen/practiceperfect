<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Trainer\GetPlayerShareLink;
use App\Models\CampConversionPrefill;
use App\Models\ShareLink;
use App\Models\User;
use App\Notifications\CampInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class CampConversion
{
    public const TTL_DAYS = 14;

    public function __construct(private readonly GetPlayerShareLink $shareLinks) {}

    /**
     * Entry point for Epic-08: called after a camp or evaluation form submission.
     *
     * @param  array{first_name?: string, last_name?: string, email?: string, phone?: string, trains_self?: bool, gender?: string, birth_date?: string, children?: array<int, array<string, mixed>>}  $submission
     */
    public function createPrefill(User $trainer, array $submission): CampConversionPrefill
    {
        return CampConversionPrefill::query()->create([
            'token' => Str::random(48),
            'trainer_id' => $trainer->id,
            'share_link_id' => $this->shareLinks->handle($trainer)->id,
            'payload' => $submission,
            'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);
    }

    public function registrationUrl(CampConversionPrefill $prefill): string
    {
        return route('join.show', ['code' => $prefill->shareLink->code, 'prefill' => $prefill->token]);
    }

    public function sendInvitation(CampConversionPrefill $prefill, string $email): void
    {
        $prefill->loadMissing('shareLink.trainer.trainerProfile');
        $trainer = $prefill->shareLink->trainer;

        Notification::route('mail', $email)->notify(new CampInvitation(
            $trainer->trainerProfile?->business_name ?? $trainer->name,
            $this->registrationUrl($prefill),
        ));

        $prefill->update(['invitation_sent_at' => now()]);
    }

    public function findUsable(?string $token, ShareLink $link): ?CampConversionPrefill
    {
        if ($token === null || $token === '') {
            return null;
        }

        $prefill = CampConversionPrefill::query()
            ->where('token', $token)
            ->where('share_link_id', $link->id)
            ->first();

        return $prefill?->isUsable() ? $prefill : null;
    }
}
