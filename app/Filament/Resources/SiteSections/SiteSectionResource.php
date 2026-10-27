<?php

namespace App\Filament\Resources\SiteSections;

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Filament\Resources\SiteSections\Pages\ListSiteSections;
use App\Filament\Support\MoneyInput;
use App\Models\SiteSection;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * CMS-1: the admin edits the home, tuition and academic page text, fee
 * plans, price list, steps, FAQs and banner without code.
 */
class SiteSectionResource extends Resource
{
    protected static ?string $model = SiteSection::class;

    protected static ?string $modelLabel = 'page';

    protected static ?string $navigationLabel = 'Pages';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(fn (?SiteSection $record): array => match ($record?->key) {
            'home' => self::homeFields(),
            'tuition' => self::tuitionFields(),
            'academic' => self::academicFields(),
            default => [],
        })->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Page')->formatStateUsing(fn (SiteSection $record): string => $record->label()),
                TextColumn::make('updated_at')->label('Last changed')->since(config('app.display_timezone')),
            ])
            ->recordUrl(fn (SiteSection $record): string => self::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteSections::route('/'),
            'edit' => EditSiteSection::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function homeFields(): array
    {
        return [
            Tabs::make()->tabs([
                Tab::make('Top of page')->schema([
                    TextInput::make('content.hero.title')->label('Headline')->required()->maxLength(160),
                    Textarea::make('content.hero.lead')->label('Intro')->required()->rows(3)->maxLength(400),
                ]),
                Tab::make('Offering cards')->schema(
                    collect(['tuition' => 'Tuition card', 'academic' => 'Academic card'])->map(fn (string $label, string $key) => Section::make($label)->schema([
                        TextInput::make("content.offerings.{$key}.eyebrow")->label('Small heading')->required()->maxLength(60),
                        TextInput::make("content.offerings.{$key}.title")->label('Title')->required()->maxLength(80),
                        Textarea::make("content.offerings.{$key}.body")->label('Text')->required()->rows(3)->maxLength(400),
                        TagsInput::make("content.offerings.{$key}.points")->label('Ticked points'),
                    ]))->values()->all()
                ),
                Tab::make('Banner')->schema([
                    TextInput::make('content.banner.text')->label('Notice across the top of every page')->helperText('Leave empty to hide it.')->maxLength(160),
                    TextInput::make('content.banner.link')->label('Link (optional)')->url()->maxLength(255),
                ]),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function tuitionFields(): array
    {
        return [
            Tabs::make()->tabs([
                self::heroTab(),
                Tab::make('Fees')->schema([
                    TagsInput::make('content.boards')->label('Boards taught')->required(),
                    Repeater::make('content.plans')->label('Fee plans')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(80),
                            Grid::make(2)->schema([
                                MoneyInput::make('price')->label('Fee')->required(),
                                TextInput::make('period')->required()->maxLength(60),
                            ]),
                            TagsInput::make('subjects'),
                            TagsInput::make('features')->label('Ticked points'),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->collapsible()->reorderable(),
                    TagsInput::make('content.price_ranges')->label('Price question choices on the enquiry form'),
                ]),
                self::stepsTab(),
                self::faqsTab(),
                self::waitlistTab(),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function academicFields(): array
    {
        return [
            Tabs::make()->tabs([
                self::heroTab(),
                Tab::make('Price list')->schema([
                    TagsInput::make('content.levels')->label('Levels')->required(),
                    Repeater::make('content.services')->label('Services')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(120),
                            Textarea::make('body')->label('Description')->required()->rows(3),
                            Grid::make(3)->schema([
                                MoneyInput::make('price_bachelors')->label('Bachelors')->helperText('Empty = quote only'),
                                MoneyInput::make('price_masters')->label('Masters'),
                                MoneyInput::make('price_phd')->label('PhD'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('turnaround')->required()->maxLength(60),
                                TextInput::make('revisions')->label('Free revisions')->integer()->minValue(0),
                            ]),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->collapsible()->reorderable(),
                    Repeater::make('content.addons')->label('Add-ons')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('name')->required()->maxLength(80),
                                MoneyInput::make('price')->required(),
                                TextInput::make('unit')->required()->maxLength(40)->placeholder('per copy'),
                            ]),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->collapsible()->reorderable(),
                    TagsInput::make('content.price_ranges')->label('Budget question choices on the enquiry form'),
                ]),
                self::stepsTab(),
                self::faqsTab(),
                self::waitlistTab(),
            ]),
        ];
    }

    private static function heroTab(): Tab
    {
        return Tab::make('Top of page')->schema([
            TextInput::make('content.hero.title')->label('Headline')->required()->maxLength(160),
            Textarea::make('content.hero.lead')->label('Intro')->required()->rows(3)->maxLength(400),
            Repeater::make('content.highlights')->label('Three highlights')
                ->schema([
                    TextInput::make('title')->required()->maxLength(80),
                    Textarea::make('body')->required()->rows(2)->maxLength(240),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()->reorderable()->maxItems(6),
        ]);
    }

    private static function stepsTab(): Tab
    {
        return Tab::make('How it works')->schema([
            Repeater::make('content.steps')->label('Steps')
                ->schema([
                    TextInput::make('title')->required()->maxLength(80),
                    Textarea::make('body')->required()->rows(2)->maxLength(300),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()->reorderable(),
        ]);
    }

    private static function faqsTab(): Tab
    {
        return Tab::make('Questions')->schema([
            Repeater::make('content.faqs')->label('Questions and answers')
                ->schema([
                    TextInput::make('q')->label('Question')->required()->maxLength(200),
                    Textarea::make('a')->label('Answer')->required()->rows(3),
                ])
                ->itemLabel(fn (array $state): ?string => $state['q'] ?? null)
                ->collapsible()->collapsed()->reorderable(),
        ]);
    }

    private static function waitlistTab(): Tab
    {
        return Tab::make('Waitlist')->schema([
            TextInput::make('content.waitlist.title')->required()->maxLength(120),
            Textarea::make('content.waitlist.body')->required()->rows(2)->maxLength(300),
            TextInput::make('content.waitlist.interest')->label('Recorded on the lead as')->required()->maxLength(160),
        ]);
    }
}
