<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use App\Support\SiteMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * CMS-1: a real quote from a parent or student, shown only once published
 * (with their permission).
 *
 * @property bool $is_published
 * @property string|null $image
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['offering', 'quote', 'image', 'name', 'detail', 'is_published', 'sort'])]
class Testimonial extends Model
{
    protected static function booted(): void
    {
        // CMS-1: make the WebP copy of a newly uploaded screenshot.
        static::saving(function (Testimonial $testimonial): void {
            if (is_string($testimonial->image) && $testimonial->image !== '') {
                SiteMedia::processShot($testimonial->image);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
