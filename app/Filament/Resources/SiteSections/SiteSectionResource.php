<?php

namespace App\Filament\Resources\SiteSections;

use App\Enums\Offering;
use App\Filament\Resources\SiteSections\Pages\EditSiteSection;
use App\Filament\Resources\SiteSections\Pages\ListSiteSections;
use App\Filament\Support\MoneyInput;
use App\Models\SiteSection;
use App\Support\Phone;
use App\Support\SiteLinks;
use App\Support\SiteMedia;
use App\Support\YouTube;
use BackedEnum;
use Closure;
use Filament\Forms\Components\FileUpload;
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
            'music' => self::musicFields(),
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
                    collect(['academic' => 'Academic card', 'tuition' => 'Tuition card', 'music' => 'Music card'])->map(fn (string $label, string $key) => Section::make($label)->schema([
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
                    'music_button' => 'Music card button',
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
                self::photosTab(),
                Tab::make('Fees')->schema([
                    TagsInput::make('content.boards')->label('Boards taught')->required(),
                    Repeater::make('content.plans')->label('Fee plans')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(80),
                            Grid::make(3)->schema([
                                MoneyInput::make('price')->label('Online fee')->required(),
                                // MKT-1: optional second price for classes at our centre.
                                MoneyInput::make('classroom_price')->label('Physical classroom fee')
                                    ->helperText('Leave empty if this plan is online only.'),
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
                self::videosTab(),
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
                    'online_label' => 'Fees: word above the online fee',
                    'classroom_label' => 'Fees: words above the physical classroom fee',
                    'subjects_label' => 'Word before each plan\'s subjects',
                    'plan_button' => 'Button on each fee plan',
                    'steps_eyebrow' => 'How it works: small heading',
                    'steps_title' => 'How it works: heading',
                    'videos_eyebrow' => 'Videos: small heading',
                    'videos_title' => 'Videos: heading',
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
    private static function musicFields(): array
    {
        return [
            Tabs::make()->tabs([
                self::heroTab(),
                self::photosTab(),
                Tab::make('Fees')->schema([
                    TagsInput::make('content.styles')->label('Instruments and singing styles')->required(),
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
                Tab::make('Chords')->schema([
                    Repeater::make('content.chords')->label('Chord boxes on the page')
                        ->helperText('Frets from the thickest string to the thinnest: x = not played, 0 = open, a number = the fret to press. For example C is x32010.')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')->required()->maxLength(8),
                                TextInput::make('frets')->required()->regex('/^[x0-5]{6}$/')->placeholder('x32010'),
                            ]),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->collapsible()->reorderable()->maxItems(8),
                ]),
                self::stepsTab(),
                self::videosTab(),
                self::faqsTab(),
                self::waitlistTab(),
                self::textTab([
                    'seo_title' => 'Page title for Google and browser tabs',
                    'seo_description' => 'Description for Google and link previews',
                    'eyebrow' => 'Small heading above the headline',
                    'demo_button' => 'Trial lesson button',
                    'question_button' => 'Question button',
                    'styles_label' => 'Words before the instruments and styles list',
                    'chords_eyebrow' => 'Chords: small heading',
                    'chords_title' => 'Chords: heading',
                    'chords_lead' => 'Chords: intro',
                    'chords_note' => 'Chords: how to read a chord box',
                    'fees_eyebrow' => 'Fees: small heading',
                    'fees_title' => 'Fees: heading',
                    'fees_lead' => 'Fees: intro',
                    'subjects_label' => 'Word before each plan\'s subjects',
                    'plan_button' => 'Button on each fee plan',
                    'steps_eyebrow' => 'How it works: small heading',
                    'steps_title' => 'How it works: heading',
                    'videos_eyebrow' => 'Videos: small heading',
                    'videos_title' => 'Videos: heading',
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
                Tab::make('Assignments')->schema([
                    Text::make('The Assignments box under the top of the page: one card for college students and one for parents of school students.'),
                    TextInput::make('content.assignments.eyebrow')->label('Small heading')->maxLength(60),
                    TextInput::make('content.assignments.title')->label('Heading')->required()->maxLength(120),
                    Textarea::make('content.assignments.lead')->label('Intro')->rows(2)->maxLength(400),
                    Section::make('College and university card')->schema([
                        TextInput::make('content.assignments.college_title')->label('Title')->required()->maxLength(120),
                        Textarea::make('content.assignments.college_body')->label('Text')->required()->rows(3)->maxLength(600),
                        TextInput::make('content.assignments.college_button')->label('Button')->required()->maxLength(60),
                    ]),
                    Section::make('School students card (for parents)')->schema([
                        TextInput::make('content.assignments.school_title')->label('Title')->required()->maxLength(120),
                        Textarea::make('content.assignments.school_body')->label('Text')->required()->rows(3)->maxLength(600)
                            ->helperText('Keep it clear that a teacher guides the child and the child does the work.'),
                        TextInput::make('content.assignments.school_button')->label('Button')->required()->maxLength(60),
                        Textarea::make('content.assignments.school_message')->label('WhatsApp message the parent starts with')->rows(2)->maxLength(300)
                            ->helperText('Sent to the Academic WhatsApp number (Website → Pages → Contact us).'),
                    ]),
                ]),
                self::photosTab(),
                Tab::make('Price list')->schema([
                    TagsInput::make('content.levels')->label('Levels')->required()
                        ->helperText('One per price column, in order: Bachelors, Masters, PhD. Each service has a price for these three; a level added after them shows the quote label.'),
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
                self::videosTab(),
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
                    'videos_eyebrow' => 'Videos: small heading',
                    'videos_title' => 'Videos: heading',
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
            ...collect(['academic' => 'Academic box', 'tuition' => 'Tuition box', 'music' => 'Music box'])->map(fn (string $label, string $key) => Section::make($label)->schema([
                TextInput::make("content.cards.{$key}.title")->label('Heading')->required()->maxLength(80),
                Textarea::make("content.cards.{$key}.body")->label('Text')->required()->rows(2)->maxLength(300),
                Grid::make(2)->schema([
                    TextInput::make("content.cards.{$key}.whatsapp")->label('WhatsApp button')->required()->maxLength(60),
                    TextInput::make("content.cards.{$key}.enquire")->label('Enquiry button')->required()->maxLength(60),
                ]),
            ]))->values()->all(),
            TextInput::make('content.office_hours_note')->label('Note after the office hours')->required()->maxLength(200)
                ->helperText('Office hours and email are server settings; ask Claude to change them.'),
            self::whatsappNumbersSection(),
        ];
    }

    /**
     * CHAT-6, VRT-2: the WhatsApp number each section's buttons open. An
     * empty field keeps the number set on the server, shown as the hint.
     */
    private static function whatsappNumbersSection(): Section
    {
        $fields = collect(['general' => 'General pages (home, about, guides…)'] + Offering::sectionOptions())
            ->map(fn (string $label, string $key) => TextInput::make("content.whatsapp_numbers.{$key}")
                ->label($key === 'general' ? $label : $label.' pages')
                ->tel()
                ->maxLength(20)
                ->placeholder(function () use ($key): string {
                    $current = SiteLinks::normaliseWhatsapp((string) config($key === 'general' ? 'site.whatsapp.number' : "site.whatsapp.numbers.{$key}"));

                    return $current === '' ? '' : 'Now: '.$current;
                })
                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                    // Checked with Google's libphonenumber, like every other mobile number on the site.
                    $digits = SiteLinks::normaliseWhatsapp((string) $value);
                    if ($digits !== '' && ! Phone::isValid('+'.$digits)) {
                        $fail('Enter a valid mobile number: 10 digits for India, or the full number with its country code.');
                    }
                })
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? SiteLinks::normaliseWhatsapp($state) : null))
            ->values()->all();

        return Section::make('WhatsApp numbers')
            ->description('The number each WhatsApp button opens a chat with. Type a 10-digit Indian mobile number (91 is added), or the full number with its country code. Leave a field empty to keep the number shown in it; a section left empty without one uses the general number.')
            ->schema($fields);
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
     * CMS-1: the three photos under the highlights. An empty slot keeps the
     * photo that ships with the site; uploads are shrunk to WebP on save.
     */
    private static function photosTab(): Tab
    {
        return Tab::make('Photos')->schema([
            Repeater::make('content.photos')->label('Photos under the highlights')
                ->helperText('Upload a JPG, PNG or WebP photo (up to 8 MB); it is cropped to 4:3 and compressed for you. Leave a slot empty to keep the photo the site came with. Do not caption stock photos as our own students or teachers.')
                ->schema([
                    FileUpload::make('image')->label('Photo')
                        ->image()
                        ->disk(fn (): string => config('site.media_disk'))
                        ->directory(SiteMedia::DIRECTORY.'/originals')
                        ->visibility('private')
                        // The preview comes from our own /media route: the
                        // browser can't fetch a private R2 link (CORS), which
                        // left the slot stuck on "Loading" with no remove button.
                        ->getUploadedFileUsing(fn (string $file): ?array => SiteMedia::preview($file))
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8192),
                    TextInput::make('alt')->label('Describe the photo')->required()->maxLength(160)
                        ->helperText('For Google and for visitors who use a screen reader, e.g. "A child takes notes during an online class".'),
                ])
                ->itemLabel(fn (array $state): ?string => $state['alt'] ?? null)
                ->minItems(3)->maxItems(3)->addable(false)->deletable(false)->reorderable()->collapsible(),
        ]);
    }

    /**
     * A YouTube link per video; only the video id is kept on the page.
     */
    private static function videosTab(): Tab
    {
        return Tab::make('Videos')->schema([
            Repeater::make('content.videos')->label('Videos')
                ->helperText('Upload the video to YouTube (public or unlisted) and paste its link. The section is hidden while this list is empty.')
                ->schema([
                    TextInput::make('title')->required()->maxLength(80),
                    TextInput::make('url')->label('YouTube link')->required()->maxLength(255)
                        ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                            if (YouTube::id((string) $value) === null) {
                                $fail('Paste a YouTube video link, for example https://youtu.be/…');
                            }
                        }),
                    TextInput::make('caption')->label('Short caption (optional)')->maxLength(160),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()->reorderable()->maxItems(6),
        ]);
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
            TextInput::make('content.hero.title')->label('Headline')->required()->maxLength(160)
                ->helperText('Keep it short (about 8 words). Put the services you want to stand out in the highlighted keywords below.'),
            TagsInput::make('content.keywords')->label('Highlighted keywords')
                ->helperText('Shown as coloured badges under the headline. Type one and press Enter.'),
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
