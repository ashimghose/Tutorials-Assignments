<?php

namespace App\Http\Controllers;

use App\Models\OrderFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Order files are private: the link is signed and short-lived, and access
 * to the order is checked again on every download.
 */
class OrderFileController extends Controller
{
    public function __invoke(OrderFile $orderFile): StreamedResponse
    {
        Gate::authorize('download', $orderFile);

        $disk = Storage::disk($orderFile->disk);
        abort_unless($disk->exists($orderFile->path), 404);

        return $disk->download($orderFile->path, $orderFile->original_name);
    }
}
