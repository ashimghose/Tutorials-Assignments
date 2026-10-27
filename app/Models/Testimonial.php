<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * CMS-1: a real quote from a parent or student, shown only once published
 * (with their permission).
 *
 * @property bool $is_published
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['offering', 'quote', 'name', 'detail', 'is_published', 'sort'])]
class Testimonial extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
