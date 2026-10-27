<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\OfferingOptions;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * MKT-6: free guides on the Resources pages.
 */
class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $modelLabel = 'guide';

    protected static ?string $navigationLabel = 'Guides';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            TextInput::make('slug')->label('Web address')->prefix(url('resources').'/')->required()->alphaDash()->maxLength(120)->unique(ignoreRecord: true),
            Textarea::make('description')->label('Summary (shown in lists and search results)')->required()->rows(2)->maxLength(300),
            Grid::make(3)->schema([
                Select::make('offering')->label('Section')->options(OfferingOptions::sections())->required(),
                DatePicker::make('published_on')->label('Publish on')->required()->default(now()),
                Toggle::make('is_published')->label('Published')->default(true)->inline(false),
            ]),
            MarkdownEditor::make('body')->required()->toolbarButtons([
                ['bold', 'italic', 'link'], ['heading'], ['blockquote', 'bulletList', 'orderedList', 'table'], ['undo', 'redo'],
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_on', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('offering')->label('Section')->badge()->formatStateUsing(fn (string $state): string => OfferingOptions::sections()[$state] ?? $state),
                TextColumn::make('published_on')->date('j M Y')->sortable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                SelectFilter::make('offering')->label('Section')->options(OfferingOptions::sections()),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
