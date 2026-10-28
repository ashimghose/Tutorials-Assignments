<?php

namespace App\Models;

use App\Enums\AddonKind;
use App\Enums\AddonUnit;
use App\Observers\AuditAdminChanges;
use Database\Factories\ServiceAddonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ACA-2: printing, binding, courier or express, priced per copy or per order.
 *
 * @property AddonKind $kind
 * @property AddonUnit $unit
 * @property int $price
 * @property bool $is_active
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['name', 'kind', 'unit', 'price', 'is_active', 'sort_order'])]
class ServiceAddon extends Model
{
    /** @use HasFactory<ServiceAddonFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'kind' => AddonKind::class,
            'unit' => AddonUnit::class,
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }
}
