<?php

namespace App\Filament\Resources\MarketingEmails;

use App\Actions\Notices\SendMarketingEmail;
use App\Enums\MarketingAudience;
use App\Filament\Resources\MarketingEmails\Pages\ListMarketingEmails;
use App\Models\MarketingEmail;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * NOT-3: newsletters and new-batch announcements, sent only to people who
 * opted in. Each email carries a one-click unsubscribe link.
 */
class MarketingEmailResource extends Resource
{
    protected static ?string $model = MarketingEmail::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return config('features.chat') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('audience')->label('Send to')->options(MarketingAudience::options())->required()
                ->helperText('Only people who ticked "news and offers" get it; everyone else is left out automatically.'),
            TextInput::make('subject')->required()->maxLength(160),
            Textarea::make('body')->required()->rows(10)->maxLength(10000)->helperText('Plain text. Leave a blank line between paragraphs.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('app.display_timezone');

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('subject')->searchable()->weight('medium'),
                TextColumn::make('audience')->formatStateUsing(fn (MarketingAudience $state): string => $state->label()),
                TextColumn::make('sent_at')->label('Sent')->dateTime('j M Y, g:i a', $tz)->placeholder('Draft'),
                TextColumn::make('recipients')->numeric(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('send')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->visible(fn (MarketingEmail $record): bool => Gate::allows('send', $record))
                    ->requiresConfirmation()
                    ->modalDescription(fn (MarketingEmail $record, SendMarketingEmail $send): string => 'Send it now to '.$send->recipients($record->audience)->count().' people who opted in? This cannot be undone.')
                    ->action(function (MarketingEmail $record, SendMarketingEmail $send): void {
                        Gate::authorize('send', $record);
                        $count = $send->handle($record, Auth::user());
                        Notification::make()->success()->title("Sending to {$count} people")->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketingEmails::route('/'),
        ];
    }
}
