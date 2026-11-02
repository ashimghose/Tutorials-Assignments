<?php

namespace App\Support;

/**
 * Reads the video id from a YouTube link the admin pastes (watch, youtu.be,
 * shorts or embed links, or the bare 11-character id). Only the id is used
 * on the page, so a pasted link can never inject other addresses.
 */
final class YouTube
{
    public static function id(string $link): ?string
    {
        $link = trim($link);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $link) === 1) {
            return $link;
        }

        $pattern = '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~';

        return preg_match($pattern, $link, $match) === 1 ? $match[1] : null;
    }
}
