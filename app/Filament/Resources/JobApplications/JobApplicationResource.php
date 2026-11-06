<?php

namespace App\Filament\Resources\JobApplications;

use App\Actions\Accounts\InviteTeacher;
use App\Enums\JobApplicationStatus;
use App\Enums\Offering;
use App\Filament\Resources\JobApplications\Pages\ListJobApplications;
use App\Filament\Resources\JobApplications\Pages\ViewJobApplication;
use App\Filament\Support\OfferingOptions;
use App\Models\AuditLog;
use App\Models\JobApplication;
use App\Models\User;
use App\Support\Phone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

/**
 * HIR-2 to HIR-4: applications from the Careers pages. The admin moves
 * each one through the hiring steps and, once hired, invites the person
 * as a teacher or expert in one step (AUTH-4).
 */
class JobApplicationResource extends Resource
{
    protected static ?string $model = JobApplication::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Hiring';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = JobApplication::query()->where('status', JobApplicationStatus::New)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('app.display_timezone');

        return $schema->components([
            Section::make('Applicant')->columns(2)->schema([
                TextEntry::make('name'),
                TextEntry::make('mobile')->formatStateUsing(fn (?string $state): string => Phone::display($state))
                    ->url(fn (JobApplication $record): string => Phone::whatsappUrl($record->mobile), shouldOpenInNewTab: true),
                TextEntry::make('email')->copyable(),
                TextEntry::make('city'),
                TextEntry::make('languages')->badge(),
                TextEntry::make('created_at')->label('Applied')->dateTime('j M Y, g:i a', $tz),
            ]),
            Section::make('Job')->columns(2)->schema([
                TextEntry::make('job_title')->label('Job'),
                TextEntry::make('offering')->label('Section')->formatStateUsing(fn (string $state): string => OfferingOptions::sections()[$state] ?? $state),
                TextEntry::make('status')->badge()
                    ->formatStateUsing(fn (JobApplicationStatus $state): string => $state->label())
                    ->color(fn (JobApplicationStatus $state): string => $state->color()),
            ]),
            Section::make('About them')->columns(1)->schema([
                TextEntry::make('subjects')->label('Subjects, instruments or areas'),
                TextEntry::make('qualifications'),
                TextEntry::make('experience'),
                TextEntry::make('availability')->label('When they can work'),
                TextEntry::make('link')->label('Video, CV or profile link')->placeholder('—')
                    ->url(fn (JobApplication $record): ?string => $record->link, shouldOpenInNewTab: true),
                TextEntry::make('message')->placeholder('—'),
                TextEntry::make('declared_at')->label('Agreed to the checks and rules')->dateTime('j M Y, g:i a', $tz),
            ]),
            Section::make('Where it came from')->columns(3)->schema([
                TextEntry::make('utm_source')->label('Source')->placeholder('—'),
                TextEntry::make('utm_medium')->label('Medium')->placeholder('—'),
                TextEntry::make('utm_campaign')->label('Campaign')->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Applied')->dateTime('j M, g:i a', timezone: config('app.display_timezone'))->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('job_title')->label('Job')->wrap()->searchable(),
                TextColumn::make('offering')->label('Section')->badge()->color(fn (string $state): string => Offering::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => OfferingOptions::sections()[$state] ?? $state),
                TextColumn::make('city')->toggleable(),
                TextColumn::make('utm_source')->label('Source')->toggleable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (JobApplicationStatus $state): string => $state->label())
                    ->color(fn (JobApplicationStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('offering')->label('Section')->options(OfferingOptions::sections()),
                SelectFilter::make('status')->options(JobApplicationStatus::options()),
                SelectFilter::make('job_opening_id')->label('Job')->relationship('jobOpening', 'title'),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    ...collect([JobApplicationStatus::Shortlisted, JobApplicationStatus::Hired, JobApplicationStatus::NotSelected])
                        ->map(fn (JobApplicationStatus $status) => self::statusAction($status))->all(),
                ])->label('Mark as')->icon(Heroicon::OutlinedCheck),
            ]);
    }

    public static function statusAction(JobApplicationStatus $status): Action
    {
        return Action::make('mark_'.$status->value)
            ->label($status->label())
            ->visible(fn (JobApplication $record): bool => $record->status !== $status)
            ->action(function (JobApplication $record) use ($status): void {
                $record->forceFill(['status' => $status])->save();
                AuditLog::record('job_application.status', $record, ['status' => $status->value]);
            });
    }

    /**
     * HIR-4, AUTH-4: invite a hired applicant as a teacher or expert. They
     * get the usual invitation email and set their own password.
     */
    public static function inviteAction(): Action
    {
        return Action::make('invite')
            ->label('Invite as teacher or expert')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('success')
            ->visible(fn (JobApplication $record): bool => ! User::query()->where('email', $record->email)->exists())
            ->modalDescription('Do this after the checks (ID, references and, for work with children, police verification) are done. They get an email to set a password and then see only the batches and orders you give them.')
            ->fillForm(fn (JobApplication $record): array => [
                'name' => $record->name,
                'email' => $record->email,
                'offerings' => [$record->offering],
                'subjects' => [],
            ])
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                // A plain rule: Filament's unique() would skip this application's id in the users table.
                TextInput::make('email')->email()->required()->maxLength(255)->rules([Rule::unique(User::class, 'email')]),
                CheckboxList::make('offerings')->label('Works in')->options(Offering::sectionOptions())->required(),
                TagsInput::make('subjects')->placeholder('Maths, Physics, Guitar, Thesis editing…'),
            ])
            ->action(function (JobApplication $record, array $data, InviteTeacher $invite): void {
                $invite($data['name'], $data['email'], array_values(array_map(Offering::from(...), $data['offerings'])), $data['subjects'] ?? []);
                $record->forceFill(['status' => JobApplicationStatus::Hired])->save();
                AuditLog::record('job_application.invited', $record);

                Notification::make()
                    ->title('Invitation sent')
                    ->body("{$data['name']} can set a password from the email for the next 7 days.")
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobApplications::route('/'),
            'view' => ViewJobApplication::route('/{record}'),
        ];
    }
}
