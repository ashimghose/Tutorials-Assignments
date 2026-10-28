<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Support\OfferingOptions;
use App\Models\Campaign;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * MKT-2: campaign landing pages (headline, offer, form) at /c/{slug}.
 * Share the page with UTM tags so each lead records where it came from.
 */
class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $modelLabel = 'campaign page';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('title')->label('Name (page title)')->required()->maxLength(255)->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')->label('Web address')->prefix(url('c').'/')->required()->alphaDash()->maxLength(80)->unique(ignoreRecord: true),
            ]),
            Select::make('offering')->label('Section')->options(OfferingOptions::sections())->required(),
            TextInput::make('headline')->required()->maxLength(255),
            TextInput::make('offer')->maxLength(255)->placeholder('First demo class free this month'),
            MarkdownEditor::make('body')->label('Page text'),
            Grid::make(2)->schema([
                TextInput::make('interest')->label('Recorded on each lead as')->maxLength(120)->placeholder('Free demo: Class 10 Maths'),
                Toggle::make('is_active')->label('Page is live')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug')->label('Address')->formatStateUsing(fn (string $state): string => '/c/'.$state)
                    ->copyable()->copyableState(fn (Campaign $record): string => route('campaign.show', $record)),
                TextColumn::make('offering')->label('Section')->badge(),
                TextColumn::make('leads_count')->counts('leads')->label('Leads')->sortable(),
                IconColumn::make('is_active')->label('Live')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
