<?php

namespace App\Filament\Resources\AcademicOrders;

use App\Filament\Resources\AcademicOrders\Pages\ListAcademicOrders;
use App\Filament\Resources\AcademicOrders\Pages\ViewAcademicOrder;
use App\Models\AcademicOrder;
use App\Models\OrderFile;
use App\Models\OrderStatus;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

/**
 * ACA-6 to ACA-9: every academic order. Payment, quotes, expert and status
 * are changed with the actions on the order page.
 */
class AcademicOrderResource extends Resource
{
    protected static ?string $model = AcademicOrder::class;

    protected static ?string $modelLabel = 'order';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $open = AcademicOrder::query()->whereHas('status', fn (Builder $query) => $query->where('is_final', false))->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');
        $money = fn (?int $state): string => $state === null ? '—' : Money::format($state);

        return $schema->columns(3)->components([
            Section::make('Order')->columnSpan(2)->columns(2)->schema([
                TextEntry::make('status.name')->label('Status')->badge()->color(fn (AcademicOrder $record): string => $record->status->color),
                TextEntry::make('expert.user.name')->label('Expert')->placeholder('Not assigned'),
                TextEntry::make('service_title')->label('Service'),
                TextEntry::make('level')->formatStateUsing(fn (AcademicOrder $record): string => $record->level->label()),
                TextEntry::make('academicStudent.user.name')->label('Student'),
                TextEntry::make('academicStudent.mobile')->label('Mobile')
                    ->url(fn (AcademicOrder $record): string => 'https://wa.me/91'.$record->academicStudent->mobile, shouldOpenInNewTab: true),
                TextEntry::make('university'),
                TextEntry::make('course')->formatStateUsing(fn (AcademicOrder $record): string => trim($record->course.($record->stream ? ' · '.$record->stream : ''))),
                TextEntry::make('topic')->columnSpanFull(),
                TextEntry::make('deadline')->date('j M Y'),
                TextEntry::make('pages')->formatStateUsing(fn (AcademicOrder $record): string => collect([$record->pages ? $record->pages.' pages' : null, $record->words ? $record->words.' words' : null])->filter()->implode(', '))->placeholder('—'),
                TextEntry::make('notes')->columnSpanFull()->placeholder('—'),
            ]),
            Section::make('Price')->columnSpan(1)->schema([
                TextEntry::make('is_quote')->label('Pricing')->formatStateUsing(fn (bool $state): string => $state ? 'Quote' : 'Price list'),
                TextEntry::make('quote_amount')->label('Quote (before GST)')->formatStateUsing($money)->visible(fn (AcademicOrder $record): bool => $record->is_quote),
                TextEntry::make('quote_expires_at')->label('Quote open until')->date('j M Y')->visible(fn (AcademicOrder $record): bool => $record->is_quote)->placeholder('—'),
                TextEntry::make('addons_list')->label('Add-ons')->state(fn (AcademicOrder $record): string => $record->addons->map(fn ($a) => "{$a->name} × {$a->quantity}")->implode(', '))->placeholder('None'),
                TextEntry::make('gst')->label('GST')->formatStateUsing($money)->placeholder('—'),
                TextEntry::make('total')->formatStateUsing($money)->weight('bold')->placeholder('Not priced yet'),
                TextEntry::make('paid_at')->label('Paid')->dateTime('j M Y, g:i a', $tz)->placeholder('Not paid'),
                TextEntry::make('revisions_used')->label('Revisions used')->formatStateUsing(fn (AcademicOrder $record): string => "{$record->revisions_used} of {$record->revisions_allowed}"),
            ]),
            Section::make('Files')->columnSpan(2)->schema([
                RepeatableEntry::make('files')->hiddenLabel()->placeholder('No files yet')->columns(3)->schema([
                    TextEntry::make('original_name')->label('File')
                        ->url(fn (OrderFile $record): string => URL::temporarySignedRoute('orders.files.download', now()->addMinutes(10), ['orderFile' => $record])),
                    TextEntry::make('kind')->formatStateUsing(fn (OrderFile $record): string => $record->kind->label()),
                    TextEntry::make('created_at')->label('Added')->dateTime('j M Y', $tz),
                ]),
            ]),
            Section::make('Delivery')->columnSpan(1)->visible(fn (AcademicOrder $record): bool => $record->delivery_address !== null)->schema([
                TextEntry::make('delivery_name')->label('Name'),
                TextEntry::make('delivery_phone')->label('Mobile'),
                TextEntry::make('delivery_address')->label('Address')
                    ->formatStateUsing(fn (AcademicOrder $record): string => "{$record->delivery_address}, {$record->delivery_city}, {$record->delivery_state} {$record->delivery_pincode}"),
                TextEntry::make('tracking_number')->label('Tracking')
                    ->formatStateUsing(fn (AcademicOrder $record): string => "{$record->courier_name} {$record->tracking_number}")->placeholder('—'),
            ]),
            Section::make('Timeline')->columnSpanFull()->schema([
                RepeatableEntry::make('history')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('toStatus.name')->label('Status'),
                    TextEntry::make('created_at')->label('When')->dateTime('j M Y, g:i a', $tz),
                    TextEntry::make('changedBy.name')->label('By')->placeholder('System'),
                    TextEntry::make('note')->placeholder('—'),
                ]),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextEntry::make('utm_source')->label('Came from')->placeholder('—'),
                TextEntry::make('utm_campaign')->label('Campaign')->placeholder('—'),
                TextEntry::make('created_at')->label('Placed')->dateTime('j M Y, g:i a', $tz),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('status', 'academicStudent.user', 'expert.user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('Order')->formatStateUsing(fn (AcademicOrder $record): string => $record->reference())->sortable(),
                TextColumn::make('academicStudent.user.name')->label('Student')->searchable(),
                TextColumn::make('service_title')->label('Service')->wrap()->searchable(),
                TextColumn::make('status.name')->label('Status')->badge()->color(fn (AcademicOrder $record): string => $record->status->color),
                TextColumn::make('expert.user.name')->label('Expert')->placeholder('—'),
                TextColumn::make('deadline')->date('j M Y')->sortable(),
                TextColumn::make('total')->formatStateUsing(fn (?int $state): string => $state === null ? 'Quote' : Money::format($state))->placeholder('Quote'),
                IconColumn::make('paid_at')->label('Paid')->boolean()->state(fn (AcademicOrder $record): bool => $record->isPaid()),
            ])
            ->filters([
                SelectFilter::make('order_status_id')->label('Status')->options(fn () => OrderStatus::query()->ordered()->pluck('name', 'id')->all()),
                SelectFilter::make('expert')->relationship('expert.user', 'name'),
                Filter::make('open')->label('Open orders')->default()
                    ->query(fn (Builder $query) => $query->whereHas('status', fn (Builder $query) => $query->where('is_final', false))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicOrders::route('/'),
            'view' => ViewAcademicOrder::route('/{record}'),
        ];
    }
}
