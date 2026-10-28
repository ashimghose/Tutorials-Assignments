<?php

namespace App\Filament\Support;

use App\Actions\Money\RecordPayment;
use App\Enums\PaymentMethod;
use App\Models\AcademicOrder;
use App\Models\Enrolment;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * ADM-4: the "how much, how and when" fields on the admin's "Fee received"
 * and "Payment received" buttons.
 */
class PaymentFields
{
    /**
     * @param  Closure(mixed): int  $defaultAmount  paise
     * @return array<int, Grid>
     */
    public static function make(Closure $defaultAmount): array
    {
        return [
            Grid::make(2)->schema([
                MoneyInput::make('amount')->label('Amount received')->required()->minValue(1)
                    ->default(fn ($record): int => $defaultAmount($record)), // paise: MoneyInput shows it in rupees
                DatePicker::make('received_on')->label('Received on')->required()->maxDate(now()->addDay())
                    ->default(fn (): string => now(config('app.display_timezone'))->toDateString()),
                Select::make('method')->options(PaymentMethod::manualOptions())->required()->default(PaymentMethod::PaymentLink->value),
                TextInput::make('reference')->label('Reference (UTR or payment id)')->maxLength(100),
                MoneyInput::make('gateway_fee')->label('Gateway fee, if any')->default(0)
                    ->helperText('Razorpay\'s fee and GST on it, from the payment\'s details.'),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function record(RecordPayment $recordPayment, Enrolment|AcademicOrder $for, array $data, User $by): Payment
    {
        return $recordPayment->handle(
            $for,
            (int) $data['amount'],
            PaymentMethod::from($data['method']),
            CarbonImmutable::parse($data['received_on']),
            $data['reference'] ?: null,
            (int) ($data['gateway_fee'] ?? 0),
            $by,
        );
    }
}
