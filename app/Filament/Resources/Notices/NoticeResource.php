<?php

namespace App\Filament\Resources\Notices;

use App\Actions\Notices\PublishNotice;
use App\Enums\NoticeAudience;
use App\Filament\Resources\Notices\Pages\ListNotices;
use App\Models\Batch;
use App\Models\Notice;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * NOT-2: notices to all parents, the parents of one batch, or all academic
 * students. Publishing shows it on their pages and emails it.
 */
class NoticeResource extends Resource
{
    protected static ?string $model = Notice::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return config('features.chat') && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('audience')->options(NoticeAudience::options())->required()->live()->default(NoticeAudience::Parents->value),
            Select::make('batch_id')->label('Batch')
                ->options(fn () => Batch::query()->orderBy('class_level')->get()->mapWithKeys(fn (Batch $batch) => [$batch->id => $batch->name()]))
                ->visible(fn (Get $get): bool => $get('audience') === NoticeAudience::Batch->value || $get('audience') === NoticeAudience::Batch)
                ->required(fn (Get $get): bool => $get('audience') === NoticeAudience::Batch->value || $get('audience') === NoticeAudience::Batch),
            TextInput::make('title')->required()->maxLength(160),
            Textarea::make('body')->required()->rows(6)->maxLength(5000),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('app.display_timezone');

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->weight('medium'),
                TextColumn::make('audience')->formatStateUsing(fn (NoticeAudience $state, Notice $record): string => $state === NoticeAudience::Batch ? 'Parents of '.$record->batch?->name() : $state->label()),
                TextColumn::make('published_at')->label('Published')->dateTime('j M Y, g:i a', $tz)->placeholder('Draft'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('publish')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->visible(fn (Notice $record): bool => Gate::allows('publish', $record))
                    ->requiresConfirmation()
                    ->modalDescription('It appears on their dashboard and is emailed to them now. You cannot edit it afterwards.')
                    ->action(function (Notice $record, PublishNotice $publish): void {
                        Gate::authorize('publish', $record);
                        $count = $publish->handle($record, Auth::user());
                        Notification::make()->success()->title("Published and emailed to {$count} people")->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotices::route('/'),
        ];
    }
}
