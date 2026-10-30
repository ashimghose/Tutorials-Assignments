<?php

namespace App\Filament\Resources\SiteSections;

use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Filament\Resources\SiteSections\Pages\ListSiteSections;
use App\Filament\Support\MoneyInput;
use App\Models\SiteSection;
use BackedEnum;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * CMS-1: the admin edits every public page's text without code: the home,
 * tuition and academic sections (headings, buttons, fee plans, price list,
 * steps, FAQs, banner), the contact page and the text pages.
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
            'contact' => self::contactFields(),
            null => [],
            default => self::textPageFields(),
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
                self::textTab([
                    'seo_title' => 'Page title for Google and browser tabs',
                    'seo_description' => 'Description for Google and link previews',
                    'tuition_button' => 'Tuition card button',
                    'academic_button' => 'Academic card button',
                    'guides_title' => 'Guides heading (also the Guides page title)',
                    'guides_lead' => 'Guides intro',
                    'guides_link' => 'Link to all guides',
                    'guides_page_lead' => 'Intro on the Guides page',
                    'help_title' => 'WhatsApp box heading',
                    'help_body' => 'WhatsApp box text (office hours are added below it)',
                    'help_button' => 'WhatsApp box button',
                    'footer' => 'Footer text on every page',
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
                self::textTab([
                    'seo_title' => 'Page title for Google and browser tabs',
                    'seo_description' => 'Description for Google and link previews',
                    'eyebrow' => 'Small heading above the headline',
                    'demo_button' => 'Demo button',
                    'question_button' => 'Question button',
                    'boards_label' => 'Word before the boards list',
                    'fees_eyebrow' => 'Fees: small heading',
                    'fees_title' => 'Fees: heading',
                    'fees_lead' => 'Fees: intro',
                    'subjects_label' => 'Word before each plan\'s subjects',
                    'plan_button' => 'Button on each fee plan',
                    'steps_eyebrow' => 'How it works: small heading',
                    'steps_title' => 'How it works: heading',
                    'people_eyebrow' => 'Teachers: small heading',
                    'people_title' => 'Teachers: heading',
                    'testimonials_eyebrow' => 'Testimonials: small heading',
                    'testimonials_title' => 'Testimonials: heading',
                    'faqs_eyebrow' => 'Questions: small heading',
                    'faqs_title' => 'Questions: heading',
                    'cta_title' => 'Bottom box: heading',
                    'cta_body' => 'Bottom box: text',
                    'cta_button' => 'Bottom box: button',
                    'waitlist_button' => 'Waitlist button',
                    'guides_title' => 'Guides heading',
                ]),
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
                    // M6: with online orders on, services and add-ons have their own screens.
                    Text::make('Services, prices and add-ons are edited under Academic → Services and Add-ons.')
                        ->visible(fn (): bool => (bool) config('features.academic_orders')),
                    Repeater::make('content.services')->label('Services')
                        ->hidden(fn (): bool => (bool) config('features.academic_orders'))
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
                        ->hidden(fn (): bool => (bool) config('features.academic_orders'))
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
                self::textTab([
                    'seo_title' => 'Page title for Google and browser tabs',
                    'seo_description' => 'Description for Google and link previews',
                    'eyebrow' => 'Small heading above the headline',
                    'quote_button' => 'Price button',
                    'prices_button' => 'Price list button',
                    'order_button' => 'Order online button',
                    'under_18' => 'Note for school students',
                    'under_18_link' => 'Link to Tuition',
                    'integrity_title' => 'Integrity notice: heading',
                    'integrity_body' => 'Integrity notice: text',
                    'prices_eyebrow' => 'Price list: small heading',
                    'prices_title' => 'Price list: heading',
                    'prices_lead' => 'Price list: intro',
                    'quote_label' => 'Shown instead of a price',
                    'enquire_button' => 'Button on a priced service',
                    'quote_only_button' => 'Button on a quote-only service',
                    'addons_title' => 'Add-ons heading',
                    'address_note' => 'Note under the add-ons',
                    'steps_eyebrow' => 'How it works: small heading',
                    'steps_title' => 'How it works: heading',
                    'people_eyebrow' => 'Experts: small heading',
                    'people_title' => 'Experts: heading',
                    'testimonials_eyebrow' => 'Testimonials: small heading',
                    'testimonials_title' => 'Testimonials: heading',
                    'faqs_eyebrow' => 'Questions: small heading',
                    'faqs_title' => 'Questions: heading',
                    'cta_title' => 'Bottom box: heading',
                    'cta_body' => 'Bottom box: text',
                    'cta_button' => 'Bottom box: button',
                    'waitlist_button' => 'Waitlist button',
                    'guides_title' => 'Guides heading',
                ], 'Keep the integrity notice: we help students with their own work and never supply work for them to submit as theirs (UGC 2018 rules).'),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function contactFields(): array
    {
        return [
            ...self::pageTopFields(),
            ...collect(['tuition' => 'Tuition box', 'academic' => 'Academic box'])->map(fn (string $label, string $key) => Section::make($label)->schema([
                TextInput::make("content.cards.{$key}.title")->label('Heading')->required()->maxLength(80),
                Textarea::make("content.cards.{$key}.body")->label('Text')->required()->rows(2)->maxLength(300),
                Grid::make(2)->schema([
                    TextInput::make("content.cards.{$key}.whatsapp")->label('WhatsApp button')->required()->maxLength(60),
                    TextInput::make("content.cards.{$key}.enquire")->label('Enquiry button')->required()->maxLength(60),
                ]),
            ]))->values()->all(),
            TextInput::make('content.office_hours_note')->label('Note after the office hours')->required()->maxLength(200)
                ->helperText('Office hours, email and WhatsApp numbers are server settings; ask Claude to change them.'),
        ];
    }

    /**
     * About, privacy, terms and refunds: a title, intro and Markdown body.
     *
     * @return array<int, mixed>
     */
    private static function textPageFields(): array
    {
        return [
            ...self::pageTopFields(),
            MarkdownEditor::make('content.body')->label('Page text')->required()
                ->helperText('Use the toolbar for headings, bold text, lists and links.'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function pageTopFields(): array
    {
        return [
            TextInput::make('content.title')->label('Page title')->required()->maxLength(120),
            Textarea::make('content.lead')->label('Intro under the title')->rows(2)->maxLength(400),
            Textarea::make('content.description')->label('Description for Google and link previews')->required()->rows(2)->maxLength(300),
        ];
    }

    /**
     * The headings, buttons and short notes around a page's sections.
     *
     * @param  array<string, string>  $labels
     */
    private static function textTab(array $labels, ?string $note = null): Tab
    {
        $fields = collect($labels)->map(fn (string $label, string $key) => str_ends_with($key, 'body') || str_ends_with($key, 'lead') || in_array($key, ['seo_description', 'footer', 'under_18'], true)
            ? Textarea::make("content.text.{$key}")->label($label)->required()->rows(2)->maxLength(600)
            : TextInput::make("content.text.{$key}")->label($label)->required()->maxLength(200)
        )->values()->all();

        return Tab::make('Headings and buttons')->schema($note === null ? $fields : [Text::make($note), ...$fields]);
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
