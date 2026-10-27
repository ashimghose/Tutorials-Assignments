<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * CMS-1: one page section's content (home, tuition, academic), in the same
 * shape as resources/content/{key}.php.
 *
 * @property array<string, mixed> $content
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['key', 'content'])]
class SiteSection extends Model
{
    public const KEYS = ['home', 'tuition', 'academic'];

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
            default => ucfirst($this->key),
        };
    }
}
