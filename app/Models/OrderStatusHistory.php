<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ACA-7: one step of an order's timeline: who moved it, when, and why.
 *
 * @property Carbon $created_at
 */
#[WithoutTimestamps]
class OrderStatusHistory extends Model
{
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderStatus, $this>
     */
    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'to_status_id');
    }

    /**
     * @return BelongsTo<OrderStatus, $this>
     */
    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'from_status_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
