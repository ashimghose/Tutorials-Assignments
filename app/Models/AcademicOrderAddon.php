<?php

namespace App\Models;

use App\Enums\AddonKind;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An add-on on an order, with its price copied at the time of ordering.
 *
 * @property AddonKind $kind
 * @property int $unit_price
 * @property int $quantity
 * @property int $line_total
 */
#[WithoutTimestamps]
class AcademicOrderAddon extends Model
{
    protected function casts(): array
    {
        return [
            'kind' => AddonKind::class,
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AcademicOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(AcademicOrder::class, 'academic_order_id');
    }
}
