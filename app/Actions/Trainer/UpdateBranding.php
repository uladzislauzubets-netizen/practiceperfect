<?php

declare(strict_types=1);

namespace App\Actions\Trainer;

use App\Models\User;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;

final class UpdateBranding
{
    public function __construct(private readonly ImageProcessor $images) {}

    public function handle(User $trainer, ?string $color, ?UploadedFile $logo, bool $removeLogo): void
    {
        $profile = $trainer->trainerProfile;
        $attributes = ['brand_color' => $color !== null ? strtoupper($color) : null];

        if ($logo !== null) {
            $attributes['logo_path'] = $this->images->storeLogo($logo);
            $this->images->delete($profile->logo_path);
        } elseif ($removeLogo) {
            $this->images->delete($profile->logo_path);
            $attributes['logo_path'] = null;
        }

        $profile->update($attributes);
    }

    public function resetColor(User $trainer): void
    {
        $trainer->trainerProfile->update(['brand_color' => null]);
    }
}
