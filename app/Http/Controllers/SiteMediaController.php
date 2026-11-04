<?php

namespace App\Http\Controllers;

use App\Support\SiteMedia;
use Symfony\Component\HttpFoundation\Response;

/**
 * CMS-1: serves the WebP copies of photos uploaded in the admin. Each file
 * name is unique to its upload, so browsers and Cloudflare may keep it for a
 * year.
 */
class SiteMediaController extends Controller
{
    public function __invoke(string $path): Response
    {
        $file = SiteMedia::DIRECTORY.'/'.$path;
        $disk = SiteMedia::disk();
        abort_unless($disk->exists($file), 404);

        return response((string) $disk->get($file), 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
