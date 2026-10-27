<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MKT-2: a campaign landing page at /c/{slug} with its own form.
 *
 * @property bool $is_active
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['slug', 'offering', 'title', 'headline', 'offer', 'body', 'interest', 'is_active'])]
class Campaign extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
