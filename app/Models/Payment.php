<?php

namespace App\Models;

use App\Enums\Offering;
use App\Enums\PaymentMethod;
use App\Observers\AuditAdminChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money received for a tuition fee or an academic order (ADM-4). The admin
 * records it for now; the payments module will record verified gateway
 * payments the same way (PAY-2).
 *
 * @property Offering $offering
 * @property PaymentMethod $method
 * @property int $amount
 * @property int $gateway_fee
 * @property Carbon $received_on
 */
#[ObservedBy(AuditAdminChanges::class)]
#[Fillable(['amount', 'gateway_fee', 'method', 'reference', 'received_on'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'offering' => Offering::class,
            'method' => PaymentMethod::class,
            'amount' => 'integer',
            'gateway_fee' => 'integer',
            'received_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Enrolment, $this>
     */
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    /**
     * @return BelongsTo<AcademicOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(AcademicOrder::class, 'academic_order_id');
    }

    /**
     * What the payment was for, for lists.
     */
    public function description(): string
    {
        if ($this->order !== null) {
            return "{$this->order->reference()} · {$this->order->service_title}";
        }

        if ($this->enrolment !== null) {
            return "{$this->enrolment->student->first_name} · {$this->enrolment->batch->name()}";
        }

        return '—';
    }
}
