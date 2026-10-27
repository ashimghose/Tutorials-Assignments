<?php

namespace App\Filament\Resources\AcademicOrders\Pages;

use App\Actions\Orders\ChangeOrderStatus;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\SendQuote;
use App\Enums\Offering;
use App\Enums\OrderFileKind;
use App\Filament\Resources\AcademicOrders\AcademicOrderResource;
use App\Filament\Support\MoneyInput;
use App\Models\AcademicOrder;
use App\Models\AuditLog;
use App\Models\OrderFile;
use App\Models\OrderStatus;
use App\Models\Teacher;
use App\Notifications\OrderAssigned;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @property AcademicOrder $record
 */
class ViewAcademicOrder extends ViewRecord
{
    protected static string $resource = AcademicOrderResource::class;

    public function getTitle(): string
    {
        return "Order {$this->record->reference()}";
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->markPaidAction(),
            $this->quoteAction(),
            $this->changeStatusAction(),
            ActionGroup::make([
                $this->assignAction(),
                $this->uploadAction(),
            ])->label('More')->button()->color('gray'),
        ];
    }

    /**
     * PAY-2 (by hand until online checkout): the money arrived by Payment Link or UPI.
     */
    private function markPaidAction(): Action
    {
        return Action::make('markPaid')
            ->label('Payment received')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (): bool => ! $this->record->isPaid() && $this->record->total !== null && ! $this->record->isClosed())
            ->requiresConfirmation()
            ->modalDescription(fn (): string => 'Use this after '.Money::format((int) $this->record->total).' has reached the bank account. The order moves on to "'.OrderStatus::afterPayment()->name.'".')
            ->action(function (MarkOrderPaid $markPaid): void {
                $markPaid->handle($this->record, Auth::user());
                Notification::make()->success()->title('Marked as paid')->send();
            });
    }

    /**
     * ACA-5: price a job outside the price list.
     */
    private function quoteAction(): Action
    {
        return Action::make('quote')
            ->label(fn (): string => $this->record->quote_amount === null ? 'Send quote' : 'Change quote')
            ->icon(Heroicon::OutlinedCurrencyRupee)
            ->visible(fn (): bool => $this->record->is_quote && ! $this->record->isPaid() && ! $this->record->isClosed())
            ->fillForm(fn (): array => [
                'amount' => $this->record->quote_amount,
                'expires_at' => $this->record->quote_expires_at ?? now(config('app.display_timezone'))->addDays((int) config('academic.quote_valid_days'))->toDateString(),
                'note' => $this->record->quote_note,
            ])
            ->schema([
                MoneyInput::make('amount')->label('Price for the job (before GST)')->required()->minValue(1)
                    ->helperText('Add-ons on the order and GST are added on top.'),
                DatePicker::make('expires_at')->label('Open until')->required()->afterOrEqual('today'),
                Textarea::make('note')->label('Note for the student')->maxLength(255),
            ])
            ->action(function (array $data, SendQuote $send): void {
                $send->handle($this->record, (int) $data['amount'], CarbonImmutable::parse($data['expires_at']), $data['note'] ?? null);
                Notification::make()->success()->title('Quote sent to the student')->send();
            });
    }

    /**
     * ACA-6, ACA-7: any status, with a note; Shipped asks for the tracking number.
     */
    private function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->schema([
                Select::make('status')->options(fn () => OrderStatus::query()->ordered()->whereKeyNot($this->record->order_status_id)->pluck('name', 'id')->all())
                    ->required()->live(),
                Textarea::make('note')->label('Note (shown to the student)')->maxLength(500),
                TextInput::make('courier')->label('Courier')->maxLength(80)
                    ->visible(fn (Get $get): bool => (bool) OrderStatus::query()->find((int) $get('status'))?->needs_tracking)->required(),
                TextInput::make('tracking')->label('Tracking number')->maxLength(80)
                    ->visible(fn (Get $get): bool => (bool) OrderStatus::query()->find((int) $get('status'))?->needs_tracking)->required(),
            ])
            ->action(function (array $data, ChangeOrderStatus $change): void {
                $change->handle($this->record, OrderStatus::query()->findOrFail((int) $data['status']), Auth::user(), $data['note'] ?? null, $data['courier'] ?? null, $data['tracking'] ?? null);
                Notification::make()->success()->title('Status changed')->send();
            });
    }

    /**
     * ACA-9: the expert who works on the order.
     */
    private function assignAction(): Action
    {
        return Action::make('assign')
            ->label('Assign expert')
            ->icon(Heroicon::OutlinedUserPlus)
            ->fillForm(fn (): array => ['expert_id' => $this->record->expert_id])
            ->schema([
                Select::make('expert_id')->label('Expert')->required()
                    ->options(fn () => Teacher::query()->whereIn('offering', [Offering::Academic, Offering::Both])->with('user')->get()
                        ->mapWithKeys(fn (Teacher $teacher) => [$teacher->id => $teacher->user->name])),
            ])
            ->action(function (array $data): void {
                $this->record->forceFill(['expert_id' => $data['expert_id']])->save();
                AuditLog::record('order.assigned', $this->record);
                $this->record->load('expert.user');
                $this->record->expert?->user->notify(new OrderAssigned($this->record));
                Notification::make()->success()->title('Expert assigned')->send();
            });
    }

    /**
     * The admin adds a draft or final, for example when doing the work themselves.
     */
    private function uploadAction(): Action
    {
        $disk = (string) config('academic.files_disk');

        return Action::make('upload')
            ->label('Upload file')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->schema([
                Select::make('kind')->options([OrderFileKind::Draft->value => 'Draft', OrderFileKind::Final->value => 'Final'])->required()->default(OrderFileKind::Draft->value),
                FileUpload::make('files')->multiple()->required()->maxFiles((int) config('academic.max_files'))
                    ->disk($disk)->directory(fn (): string => "orders/{$this->record->id}")->visibility('private')
                    ->acceptedFileTypes(array_values(config('academic.mimes')))->maxSize((int) config('academic.max_upload_kb'))
                    ->storeFileNamesIn('names'),
            ])
            ->action(function (array $data) use ($disk): void {
                foreach ($data['files'] as $path) {
                    $file = new OrderFile;
                    $file->forceFill([
                        'academic_order_id' => $this->record->id,
                        'uploaded_by' => Auth::id(),
                        'kind' => $data['kind'],
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => $data['names'][$path] ?? basename($path),
                        'mime' => (string) Storage::disk($disk)->mimeType($path),
                        'size' => (int) Storage::disk($disk)->size($path),
                    ])->save();
                }
                Notification::make()->success()->title('Uploaded')->send();
            });
    }
}
