<?php

namespace App\Filament\Resources\Conversations;

use App\Enums\Offering;
use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Models\Conversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * CHAT-2: the admin inbox. Every conversation with its unread count and
 * reported messages; opening one goes to the message page, where the admin
 * replies, assigns it to a teacher or expert, or closes it.
 */
class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationLabel = 'Inbox';

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return config('features.chat') && parent::canAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $reported = Conversation::query()->whereHas('messages', fn (Builder $q) => $q->whereNotNull('reported_at'))->whereNull('closed_at')->count();

        return $reported > 0 ? (string) $reported : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Open conversations with a reported message';
    }

    public static function table(Table $table): Table
    {
        $tz = config('app.display_timezone');

        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                /** @var Builder<Conversation> $query */
                $query->withUnreadFor(Auth::user())
                    ->withCount(['messages as reported_count' => fn (Builder $q) => $q->whereNotNull('reported_at')])
                    ->with('starter', 'student', 'assignee');
            })
            ->defaultSort('last_message_at', 'desc')
            ->recordUrl(fn (Conversation $record): string => route('messages.show', $record))
            ->columns([
                TextColumn::make('subject')->searchable()->limit(50)->weight('medium'),
                TextColumn::make('offering')->badge()->formatStateUsing(fn (Offering $state): string => $state->label())
                    ->color(fn (Offering $state): string => $state === Offering::Tuition ? 'warning' : 'primary'),
                TextColumn::make('starter.name')->label('From')->placeholder('Deleted account'),
                TextColumn::make('student.first_name')->label('About')->placeholder('—'),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('Admin team'),
                TextColumn::make('unread_count')->label('Unread')->badge()->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('reported_count')->label('Reported')->badge()->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('last_message_at')->label('Last message')->dateTime('j M Y, g:i a', $tz)->sortable(),
                TextColumn::make('closed_at')->label('Closed')->dateTime('j M Y', $tz)->placeholder('Open')->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('closed_at')->label('Status')->nullable()->placeholder('All')
                    ->trueLabel('Closed')->falseLabel('Open')->default(false),
                SelectFilter::make('offering')->options([Offering::Tuition->value => 'Tuition', Offering::Academic->value => 'Academic services']),
                Filter::make('reported')->label('Has a reported message')
                    ->query(fn (Builder $query) => $query->whereHas('messages', fn (Builder $q) => $q->whereNotNull('reported_at'))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
        ];
    }
}
