<?php

namespace App\Http\Controllers;

use App\Support\SiteMedia;
use Symfony\Component\HttpFoundation\Response;

/**
 * MKT-1: serves a guidance sample PDF uploaded in the admin. Only files in
 * the samples folder that really are PDFs; each name is unique to its
 * upload, so browsers and Cloudflare may keep it for a year.
 */
class SampleFileController extends Controller
{
    public function __invoke(string $name): Response
    {
        $file = SiteMedia::SAMPLES_DIRECTORY.'/'.$name;
        $disk = SiteMedia::disk();
        abort_unless($disk->exists($file), 404);

        $bytes = (string) $disk->get($file);
        abort_unless(str_starts_with($bytes, '%PDF-'), 404);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
