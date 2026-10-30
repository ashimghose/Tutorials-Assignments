<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * CMS-1: one page's content (home, tuition, academic, contact and the text
 * pages), in the same shape as resources/content/{key}.php.
 *
 * @property array<string, mixed> $content
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['key', 'content'])]
class SiteSection extends Model
{
    public const KEYS = ['home', 'tuition', 'academic', 'about', 'contact', 'privacy', 'terms', 'refunds'];

    /** Pages that are a title, intro and Markdown body. */
    public const TEXT_PAGES = ['about', 'privacy', 'terms', 'refunds'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function label(): string
    {
        return match ($this->key) {
            'home' => 'Home page',
            'tuition' => 'Tuition section',
            'academic' => 'Academic section',
            'about' => 'About us',
            'contact' => 'Contact us',
            'privacy' => 'Privacy policy',
            'terms' => 'Terms of use',
            'refunds' => 'Refund policy',
            default => ucfirst($this->key),
        };
    }
}
