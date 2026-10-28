<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Support\OfferingOptions;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
 * CMS-1: quotes from real parents and students, published only with their
 * permission.
 */
class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Textarea::make('quote')->required()->rows(4)->maxLength(600),
            Grid::make(2)->schema([
                TextInput::make('name')->label('Name as shown')->required()->maxLength(80)->helperText('A first name and initial is enough.'),
                TextInput::make('detail')->maxLength(120)->placeholder('Parent of a Class 8 student'),
            ]),
            Grid::make(3)->schema([
                Select::make('offering')->label('Section')->options(OfferingOptions::sections())->required(),
                TextInput::make('sort')->label('Order')->integer()->default(0),
                Toggle::make('is_published')->label('Published (they agreed)')->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('quote')->limit(80)->wrap(),
                TextColumn::make('name'),
                TextColumn::make('offering')->label('Section')->badge(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
