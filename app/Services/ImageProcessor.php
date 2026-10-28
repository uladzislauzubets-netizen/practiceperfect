<?php

declare(strict_types=1);

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ImageProcessor
{
    public const DISK = 'local';

    private const MAX_SOURCE_DIMENSION = 8000;

    private const PHOTO_MAX = 512;

    private const PHOTO_THUMB = 128;

    private const LOGO_MAX = 200;

    /** @return array{path: string, thumb: string} */
    public function storePhoto(UploadedFile $file, string $field = 'photo'): array
    {
        $source = $this->decode($file, $field);
        $name = Str::uuid()->toString();
        $disk = Storage::disk(self::DISK);

        $paths = ['path' => "photos/{$name}.jpg", 'thumb' => "photos/{$name}_thumb.jpg"];
        $disk->put($paths['path'], $this->encodeJpeg($this->fit($source, self::PHOTO_MAX)));
        $disk->put($paths['thumb'], $this->encodeJpeg($this->cropSquare($source, self::PHOTO_THUMB)));

        return $paths;
    }

    public function storeLogo(UploadedFile $file, string $field = 'logo'): string
    {
        $disk = Storage::disk(self::DISK);
        $name = Str::uuid()->toString();

        if (strtolower((string) $file->getClientOriginalExtension()) === 'svg' || $file->getMimeType() === 'image/svg+xml') {
            $contents = (string) $file->get();
            $previous = libxml_use_internal_errors(true);
            $document = simplexml_load_string($contents, options: LIBXML_NONET);
            libxml_use_internal_errors($previous);

            if ($document === false || strtolower($document->getName()) !== 'svg') {
                throw ValidationException::withMessages([$field => 'The SVG file is not valid.']);
            }

            $disk->put("logos/{$name}.svg", $contents);

            return "logos/{$name}.svg";
        }

        $image = $this->fit($this->decode($file, $field), self::LOGO_MAX);
        ob_start();
        imagepng($image);
        $disk->put("logos/{$name}.png", (string) ob_get_clean());

        return "logos/{$name}.png";
    }

    public function delete(?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    private function decode(UploadedFile $file, string $field): GdImage
    {
        $contents = (string) $file->get();
        $size = @getimagesizefromstring($contents);

        if ($size === false || $size[0] > self::MAX_SOURCE_DIMENSION || $size[1] > self::MAX_SOURCE_DIMENSION) {
            throw ValidationException::withMessages([$field => 'The image could not be processed.']);
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw ValidationException::withMessages([$field => 'The image could not be processed.']);
        }

        return $image;
    }

    private function fit(GdImage $source, int $max): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min($max / $width, $max / $height, 1.0);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = $this->transparentCanvas($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    private function cropSquare(GdImage $source, int $side): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $crop = min($width, $height);

        $target = $this->transparentCanvas($side, $side);
        imagecopyresampled($target, $source, 0, 0, intdiv($width - $crop, 2), intdiv($height - $crop, 2), $side, $side, $crop, $crop);

        return $target;
    }

    private function transparentCanvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        return $canvas;
    }

    private function encodeJpeg(GdImage $image): string
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($flat, null, 85);

        return (string) ob_get_clean();
    }
}
