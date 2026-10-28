<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\Media;

final class BrandingResolver
{
    public const DEFAULT_COLOR = '#00B300';

    public function __construct(private readonly ContextResolver $contexts) {}

    /** @return array{name: ?string, color: string, logo_url: ?string, is_custom: bool} */
    public function forUser(?User $user): array
    {
        $trainer = $user === null ? null : $this->contexts->trainerFor($user);

        return $this->forTrainer($trainer);
    }

    /** @return array{name: ?string, color: string, logo_url: ?string, is_custom: bool} */
    public function forTrainer(?User $trainer): array
    {
        $profile = $trainer?->trainerProfile;

        return [
            'name' => $profile?->business_name,
            'color' => $profile?->brand_color ?? self::DEFAULT_COLOR,
            'logo_url' => Media::logoUrl($profile?->logo_path),
            'is_custom' => $profile !== null && ($profile->brand_color !== null || $profile->logo_path !== null),
        ];
    }
}
