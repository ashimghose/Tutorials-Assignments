<?php

namespace App\Filament\Resources\Enrolments;

use App\Actions\Enrolments\ActivateEnrolment;
use App\Actions\Enrolments\RemoveFromBatch;
use App\Enums\EnrolmentStatus;
use App\Models\Enrolment;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Admin actions on an enrolment, shared by the enrolments list and the
 * batch screen.
 */
class EnrolmentActions
{
    /**
     * PAY-2 (by hand until online checkout): the fee arrived by Payment
     * Link, UPI or cash, so the child can join until the paid-through date.
     */
    public static function activate(): Action
    {
        return Action::make('activate')
            ->label('Fee received')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Enrolment $record): bool => $record->status !== EnrolmentStatus::Removed)
            ->modalDescription('Use this after the fee has reached the bank account (Payment Link, UPI or cash). The parent gets an email and the child can join classes until this date.')
            ->schema([
                DatePicker::make('paid_through')->label('Paid until')->required()
                    ->default(fn (Enrolment $record): string => self::nextPaidThrough($record)->toDateString()),
            ])
            ->action(function (Enrolment $record, array $data, ActivateEnrolment $activate): void {
                $activate->handle($record, CarbonImmutable::parse($data['paid_through']));
                Notification::make()->success()->title('Enrolment active')->send();
            });
    }

    /**
     * CLS-7: end the child's place in the batch.
     */
    public static function remove(): Action
    {
        return Action::make('remove')
            ->label('Remove from batch')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->visible(fn (Enrolment $record): bool => $record->status !== EnrolmentStatus::Removed)
            ->schema([
                Textarea::make('reason')->label('Reason (sent to the parent)')->required()->maxLength(255),
            ])
            ->action(function (Enrolment $record, array $data, RemoveFromBatch $remove): void {
                $remove->handle($record, $data['reason']);
                Notification::make()->title('Removed from the batch')->send();
            });
    }

    /**
     * One month on from the current paid-through date, or from today if it has passed.
     */
    public static function nextPaidThrough(Enrolment $enrolment): CarbonImmutable
    {
        $today = Enrolment::today();
        $current = $enrolment->paid_through ? CarbonImmutable::parse($enrolment->paid_through->toDateString()) : null;

        return $current !== null && $current->gte($today->subDays(Enrolment::GRACE_DAYS))
            ? $current->addMonthNoOverflow()
            : $today->addMonthNoOverflow()->subDay();
    }
}
