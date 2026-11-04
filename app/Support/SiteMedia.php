<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * CMS-1: photos the admin uploads for the public pages. The original goes to
 * the site media disk (R2 in production, so the app server stays stateless),
 * and two 4:3 WebP copies (640 and 1200 px wide) are made next to it with GD.
 * The public reaches them through /media/..., which Cloudflare caches.
 */
class SiteMedia
{
    public const DIRECTORY = 'site-media';

    public const WIDTHS = [640, 1200];

    /** Width => height of each 4:3 copy. */
    private const SIZES = [640 => 480, 1200 => 900];

    public static function disk(): Filesystem
    {
        return Storage::disk(config('site.media_disk'));
    }

    /** The WebP copy's path for an uploaded original, e.g. site-media/abc-640.webp. */
    public static function variant(string $image, int $width): string
    {
        return self::DIRECTORY.'/'.pathinfo($image, PATHINFO_FILENAME).'-'.$width.'.webp';
    }

    public static function url(string $image, int $width): string
    {
        return route('media', ['path' => basename(self::variant($image, $width))]);
    }

    /** Makes the WebP copies of an uploaded original, unless they exist already. */
    public static function process(string $image): void
    {
        $disk = self::disk();
        if ($disk->exists(self::variant($image, self::WIDTHS[0]))) {
            return;
        }

        $bytes = $disk->get($image);
        $source = $bytes === null ? false : @imagecreatefromstring($bytes);
        if ($source === false) {
            throw new RuntimeException('The photo could not be read. Please upload a JPG, PNG or WebP image.');
        }

        $source = self::upright($source, $bytes);
        $width = imagesx($source);
        $height = imagesy($source);

        // Centre crop to 4:3.
        $cropWidth = min($width, (int) round($height * 4 / 3));
        $cropHeight = (int) round($cropWidth * 3 / 4);
        $x = intdiv($width - $cropWidth, 2);
        $y = intdiv($height - $cropHeight, 2);

        foreach (self::SIZES as $target => $targetHeight) {
            $copy = imagecreatetruecolor($target, $targetHeight);
            imagecopyresampled($copy, $source, 0, 0, $x, $y, $target, $targetHeight, $cropWidth, $cropHeight);

            ob_start();
            imagewebp($copy, null, 72);
            $disk->put(self::variant($image, $target), (string) ob_get_clean());
        }
    }

    /** Phone photos store their rotation in EXIF; turn the pixels to match. */
    private static function upright(\GdImage $image, string $bytes): \GdImage
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($bytes, "\xFF\xD8")) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
