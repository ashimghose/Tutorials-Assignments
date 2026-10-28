<?php

namespace App\Models;

use App\Enums\AcademicLevel;
use App\Observers\AuditAdminChanges;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ACA-1: one line of the academic price list, with a price for each level
 * (empty means the job is quoted, ACA-5) and the add-ons it allows (ACA-2).
 *
 * @property list<string>|null $includes
 * @property int|null $price_bachelors
 * @property int|null $price_masters
 * @property int|null $price_phd
 * @property int $free_revisions
 * @property bool $is_active
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['title', 'type', 'description', 'includes', 'price_bachelors', 'price_masters', 'price_phd', 'turnaround', 'free_revisions', 'is_active', 'sort_order'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
        'free_revisions' => 0,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'includes' => 'array',
            'price_bachelors' => 'integer',
            'price_masters' => 'integer',
            'price_phd' => 'integer',
            'free_revisions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<ServiceAddon, $this>
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(ServiceAddon::class);
    }

    /**
     * @param  Builder<Service>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The listed price for a level in paise, or null when it is quoted.
     */
    public function priceFor(AcademicLevel $level): ?int
    {
        $price = $this->getAttribute($level->priceColumn());

        return is_numeric($price) ? (int) $price : null;
    }
}
