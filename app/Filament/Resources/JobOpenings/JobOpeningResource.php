<?php

namespace App\Filament\Resources\JobOpenings;

use App\Filament\Resources\JobOpenings\Pages\CreateJobOpening;
use App\Filament\Resources\JobOpenings\Pages\EditJobOpening;
use App\Filament\Resources\JobOpenings\Pages\ListJobOpenings;
use App\Filament\Support\OfferingOptions;
use App\Models\JobOpening;
use BackedEnum;
use Filament\Actions\Action;
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
 * HIR-1: the jobs on the Careers pages. The admin adds, edits and closes
 * them; a closed job disappears from the site.
 */
class JobOpeningResource extends Resource
{
    protected static ?string $model = JobOpening::class;

    protected static ?string $modelLabel = 'job opening';

    protected static string|\UnitEnum|null $navigationGroup = 'Hiring';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')->label('Job title')->required()->maxLength(255)->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            TextInput::make('slug')->label('Web address')->prefix(url('careers').'/')->required()->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')->maxLength(120)->unique(ignoreRecord: true)
                ->helperText('Small letters, numbers and dashes. Changing it breaks links you have already shared.'),
            Textarea::make('summary')->label('Summary (shown in the job list and Google)')->required()->rows(2)->maxLength(300),
            Grid::make(3)->schema([
                Select::make('offering')->label('Section')->options(OfferingOptions::sections())->required(),
                TextInput::make('openings')->label('Places')->integer()->minValue(1)->maxValue(100)->default(1)->required(),
                Toggle::make('is_open')->label('Open for applications')->default(true)->inline(false),
            ]),
            Grid::make(2)->schema([
                TextInput::make('location')->label('Where')->required()->maxLength(160)->placeholder('Online, from anywhere in Assam'),
                Select::make('region')->label('Who can apply (for Google)')->options(self::regions())->default('India')->required(),
                TextInput::make('languages')->required()->maxLength(160)->placeholder('English, Hindi and Assamese'),
                TextInput::make('pay')->required()->maxLength(300)->placeholder('About 50% of each batch\'s monthly fees, paid monthly'),
                DatePicker::make('posted_on')->label('Show from')->required()->default(now()),
                DatePicker::make('closes_on')->label('Apply by (optional)')->afterOrEqual('posted_on'),
            ]),
            MarkdownEditor::make('body')->label('Job description')->required()->toolbarButtons([
                ['bold', 'italic', 'link'], ['heading'], ['bulletList', 'orderedList'], ['undo', 'redo'],
            ])->helperText('What they will do, who you are looking for, and how hiring works. The apply form is added below it on the website.'),
            TextInput::make('sort_order')->label('Order in the list')->integer()->default(0)->helperText('Smaller numbers show first.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('offering')->label('Section')->badge()->formatStateUsing(fn (string $state): string => OfferingOptions::sections()[$state] ?? $state),
                TextColumn::make('openings')->label('Places'),
                TextColumn::make('applications_count')->label('Applications')->counts('applications')->sortable(),
                IconColumn::make('is_open')->label('Open')->boolean(),
                TextColumn::make('closes_on')->label('Apply by')->date('j M Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('offering')->label('Section')->options(OfferingOptions::sections()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view_on_site')->label('View on site')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (JobOpening $record): string => route('careers.show', $record), shouldOpenInNewTab: true)
                    ->visible(fn (JobOpening $record): bool => $record->isAcceptingApplications()),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function regions(): array
    {
        return [
            'India' => 'Anywhere in India',
            'Assam' => 'Assam',
            'Delhi' => 'Delhi',
            'Uttar Pradesh' => 'Uttar Pradesh',
            'West Bengal' => 'West Bengal',
            'Meghalaya' => 'Meghalaya',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobOpenings::route('/'),
            'create' => CreateJobOpening::route('/create'),
            'edit' => EditJobOpening::route('/{record}/edit'),
        ];
    }
}
