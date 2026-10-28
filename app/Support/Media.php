<?php

declare(strict_types=1);

namespace App\Support;

final class Media
{
    public static function photoUrl(?string $path): ?string
    {
        return $path === null ? null : route('media.photo', basename($path));
    }

    public static function logoUrl(?string $path): ?string
    {
        return $path === null ? null : route('media.logo', basename($path));
    }
}
