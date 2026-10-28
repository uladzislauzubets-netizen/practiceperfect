<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ImageProcessor;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    private const MIME_TYPES = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'svg' => 'image/svg+xml'];

    public function photo(string $filename): Response
    {
        return $this->serve("photos/{$filename}", 'private, max-age=3600');
    }

    public function logo(string $filename): Response
    {
        return $this->serve("logos/{$filename}", 'public, max-age=3600');
    }

    private function serve(string $path, string $cacheControl): Response
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $disk = Storage::disk(ImageProcessor::DISK);

        abort_unless(
            preg_match('/^(photos|logos)\/[A-Za-z0-9_-]+\.(jpg|png|svg)$/', $path) === 1 && $disk->exists($path),
            404,
        );

        return response($disk->get($path), 200, [
            'Content-Type' => self::MIME_TYPES[$extension],
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $cacheControl,
        ]);
    }
}
