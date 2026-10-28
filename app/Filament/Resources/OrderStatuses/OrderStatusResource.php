<?php

namespace App\Filament\Resources\OrderStatuses;

use App\Filament\Resources\OrderStatuses\Pages\ListOrderStatuses;
use App\Models\OrderStatus;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * ACA-6: the order statuses, in the order shown to students. Drag to
 * reorder; the first one is where new orders wait for payment.
 */
class OrderStatusResource extends Resource
{
    protected static ?string $model = OrderStatus::class;

    protected static ?string $modelLabel = 'order status';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(60),
                Select::make('color')->options(OrderStatus::COLORS)->required()->default('gray'),
            ]),
            Toggle::make('notify_student')->label('Email the student when an order reaches it'),
            Toggle::make('is_final')->label('Final: the order is finished or cancelled')->helperText('Files are deleted after the retention period from here.'),
            Toggle::make('is_cancelled')->label('Means cancelled: no expert payout, not counted as completed'),
            Toggle::make('expert_can_set')->label('Experts may set it'),
            Toggle::make('is_revision')->label('Students reach it by asking for a revision'),
            Toggle::make('needs_tracking')->label('Ask for the courier and tracking number'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->badge()->color(fn (OrderStatus $record): string => $record->color),
                IconColumn::make('notify_student')->label('Emails student')->boolean(),
                IconColumn::make('is_final')->label('Final')->boolean(),
                IconColumn::make('expert_can_set')->label('Expert sets')->boolean(),
                TextColumn::make('orders_count')->counts('orders')->label('Orders now'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderStatuses::route('/'),
        ];
    }
}
