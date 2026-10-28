<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CHAT-3: chat attachments are private; the link is signed and short-lived,
 * and access to the conversation is checked again on every download.
 */
class MessageAttachmentController extends Controller
{
    public function __invoke(Message $message): StreamedResponse
    {
        Gate::authorize('view', $message);
        abort_if($message->attachment_path === null || $message->attachment_disk === null, 404);

        $disk = Storage::disk($message->attachment_disk);
        abort_unless($disk->exists($message->attachment_path), 404);

        return $disk->download($message->attachment_path, $message->attachment_name);
    }
}
