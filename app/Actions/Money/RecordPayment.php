<?php

namespace App\Actions\Money;

use App\Enums\Offering;
use App\Enums\PaymentMethod;
use App\Models\AcademicOrder;
use App\Models\Enrolment;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * ADM-4: records money received for a fee or an order. The admin's "Fee
 * received" and "Payment received" buttons call it now; the payments module
 * will call it with the gateway's fee after verifying a payment (PAY-2).
 */
class RecordPayment
{
    public function handle(Enrolment|AcademicOrder $for, int $amount, PaymentMethod $method, CarbonInterface $receivedOn, ?string $reference = null, int $gatewayFee = 0, ?User $by = null): Payment
    {
        $payment = new Payment([
            'amount' => $amount,
            'gateway_fee' => $gatewayFee,
            'method' => $method,
            'reference' => $reference,
            'received_on' => $receivedOn->toDateString(),
        ]);
        $payment->forceFill([
            'offering' => $for instanceof Enrolment ? Offering::Tuition : Offering::Academic,
            'enrolment_id' => $for instanceof Enrolment ? $for->id : null,
            'academic_order_id' => $for instanceof AcademicOrder ? $for->id : null,
            'recorded_by' => $by?->id,
        ])->save();

        return $payment;
    }
}
