<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ACA-6: the admin's list of order statuses. The first in sort order is
 * where a new order waits for payment; nothing else is hard-coded, only
 * these flags.
 *
 * @property bool $notify_student
 * @property bool $is_final
 * @property bool $expert_can_set
 * @property bool $is_revision
 * @property bool $needs_tracking
 * @property bool $is_cancelled
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['name', 'sort_order', 'color', 'notify_student', 'is_final', 'expert_can_set', 'is_revision', 'needs_tracking', 'is_cancelled'])]
class OrderStatus extends Model
{
    public const COLORS = ['gray' => 'Grey', 'warning' => 'Amber', 'info' => 'Blue', 'primary' => 'Green', 'success' => 'Bright green', 'danger' => 'Red'];

    protected function casts(): array
    {
        return [
            'notify_student' => 'boolean',
            'is_final' => 'boolean',
            'expert_can_set' => 'boolean',
            'is_revision' => 'boolean',
            'needs_tracking' => 'boolean',
            'is_cancelled' => 'boolean',
        ];
    }

    /**
     * @param  Builder<OrderStatus>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Where new orders start: "Awaiting payment" in the default list.
     */
    public static function initial(): self
    {
        return self::query()->ordered()->firstOrFail();
    }

    /**
     * Where an order goes once paid: the next status that isn't final.
     */
    public static function afterPayment(): self
    {
        $initial = self::initial();

        return self::query()->ordered()->whereKeyNot($initial->id)->where('is_final', false)->first() ?? $initial;
    }

    public function isInitial(): bool
    {
        return $this->is(self::initial());
    }

    /**
     * @return HasMany<AcademicOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(AcademicOrder::class);
    }

    /**
     * Flux badge colour for the student's pages.
     */
    public function badgeColor(): string
    {
        return match ($this->color) {
            'warning' => 'amber',
            'info' => 'blue',
            'primary', 'success' => 'green',
            'danger' => 'red',
            default => 'zinc',
        };
    }
}
