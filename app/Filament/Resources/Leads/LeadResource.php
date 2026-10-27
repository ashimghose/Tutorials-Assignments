<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadKind;
use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Support\OfferingOptions;
use App\Models\AuditLog;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CMS-3, MKT-3: enquiries, demo bookings and waitlist sign-ups from the
 * site's forms, with where each came from.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $new = Lead::query()->where('status', LeadStatus::New)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            Section::make('Contact')->columns(2)->schema([
                TextEntry::make('name'),
                TextEntry::make('mobile')->url(fn (Lead $record): string => self::whatsappUrl($record), shouldOpenInNewTab: true),
                TextEntry::make('email')->placeholder('—'),
                IconEntry::make('marketing_consent')->label('Agreed to news and offers')->boolean(),
            ]),
            Section::make('Request')->columns(2)->schema([
                TextEntry::make('offering')->label('Section')->formatStateUsing(fn (string $state): string => OfferingOptions::sections()[$state] ?? $state),
                TextEntry::make('kind')->formatStateUsing(fn (LeadKind $state): string => $state->label()),
                TextEntry::make('interest')->placeholder('—'),
                TextEntry::make('class_or_service')->label('Class / service')->placeholder('—'),
                TextEntry::make('price_range')->label('Price they chose')->placeholder('—'),
                TextEntry::make('created_at')->label('Received')->dateTime('j M Y, g:i a', $tz),
                TextEntry::make('message')->columnSpanFull()->placeholder('—'),
            ]),
            Section::make('Where it came from')->columns(3)->schema([
                TextEntry::make('utm_source')->label('Source')->placeholder('—'),
                TextEntry::make('utm_medium')->label('Medium')->placeholder('—'),
                TextEntry::make('utm_campaign')->label('Campaign')->placeholder('—'),
                TextEntry::make('page')->placeholder('—'),
                TextEntry::make('campaign.title')->label('Campaign page')->placeholder('—'),
            ]),
            Section::make('Follow-up')->schema([
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn (LeadStatus $state): string => $state->label())
                    ->color(fn (LeadStatus $state): string => $state->color()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->dateTime('j M, g:i a', timezone: config('app.display_timezone'))->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('mobile')->searchable()->url(fn (Lead $record): string => self::whatsappUrl($record), shouldOpenInNewTab: true),
                TextColumn::make('offering')->label('Section')->badge()->color(fn (string $state): string => $state === 'tuition' ? 'warning' : 'primary'),
                TextColumn::make('kind')->formatStateUsing(fn (LeadKind $state): string => $state->label()),
                TextColumn::make('interest')->limit(30)->toggleable(),
                TextColumn::make('utm_source')->label('Source')->toggleable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (LeadStatus $state): string => $state->label())
                    ->color(fn (LeadStatus $state): string => $state->color()),
                IconColumn::make('marketing_consent')->label('News OK')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('offering')->label('Section')->options(OfferingOptions::sections()),
                SelectFilter::make('kind')->options(collect(LeadKind::cases())->mapWithKeys(fn (LeadKind $kind) => [$kind->value => $kind->label()])),
                SelectFilter::make('status')->options(collect(LeadStatus::cases())->mapWithKeys(fn (LeadStatus $status) => [$status->value => $status->label()])),
                SelectFilter::make('utm_source')->label('Source')
                    ->options(fn () => Lead::query()->whereNotNull('utm_source')->distinct()->orderBy('utm_source')->pluck('utm_source', 'utm_source')->all()),
                Filter::make('marketing_consent')->label('Agreed to news and offers')->query(fn (Builder $query) => $query->where('marketing_consent', true)),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(collect([LeadStatus::Contacted, LeadStatus::Converted, LeadStatus::Closed])
                    ->map(fn (LeadStatus $status) => self::statusAction($status))->all())
                    ->label('Mark as')->icon(Heroicon::OutlinedCheck),
            ]);
    }

    public static function statusAction(LeadStatus $status): Action
    {
        return Action::make('mark_'.$status->value)
            ->label($status->label())
            ->visible(fn (Lead $record): bool => $record->status !== $status)
            ->action(function (Lead $record) use ($status): void {
                $record->forceFill([
                    'status' => $status,
                    'became_customer_at' => $status === LeadStatus::Converted ? ($record->became_customer_at ?? now()) : $record->became_customer_at,
                ])->save();
                AuditLog::record('lead.status', $record, ['status' => $status->value]);
            });
    }

    public static function whatsappUrl(Lead $lead): string
    {
        return 'https://wa.me/91'.$lead->mobile;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'view' => ViewLead::route('/{record}'),
        ];
    }
}
