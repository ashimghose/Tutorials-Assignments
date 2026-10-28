<?php

namespace App\Models;

use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ADM-2: a month's payout to a teacher or expert, kept as calculated when
 * the admin marked it paid. Classes are paid to tuition, orders to academic.
 *
 * @property Carbon $month
 * @property int $classes
 * @property int $class_amount
 * @property int $orders
 * @property int $order_amount
 * @property int $total
 * @property Carbon $paid_on
 */
#[ObservedBy(AuditAdminChanges::class)]
class Payout extends Model
{
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'paid_on' => 'date',
            'classes' => 'integer',
            'class_amount' => 'integer',
            'orders' => 'integer',
            'order_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
