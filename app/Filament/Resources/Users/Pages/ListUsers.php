<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Accounts\InviteTeacher;
use App\Enums\Offering;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ADM-5: the list as filtered on screen, as a CSV file.
            Action::make('exportCsv')
                ->label('Download CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): StreamedResponse {
                    /** @var Builder<User> $query */
                    $query = $this->getFilteredSortedTableQuery();
                    AuditLog::record('users.export', null, ['filters' => array_keys(array_filter($this->tableFilters ?? [], fn ($f) => filled($f['value'] ?? null)))]);
                    $tz = config('app.display_timezone');

                    return response()->streamDownload(function () use ($query, $tz): void {
                        $out = fopen('php://output', 'w');
                        if ($out === false) {
                            return;
                        }
                        fputcsv($out, ['name', 'email_or_username', 'role', 'account', 'joined', 'last_login']);
                        $query->clone()->with([])->each(fn (User $user) => fputcsv($out, [
                            $user->name,
                            $user->email ?? $user->username,
                            $user->role?->label(),
                            $user->status->value,
                            $user->created_at?->setTimezone($tz)->toDateString(),
                            $user->last_login_at?->setTimezone($tz)->toDateString(),
                        ]));
                        fclose($out);
                    }, 'people-'.now($tz)->toDateString().'.csv', ['Content-Type' => 'text/csv']);
                }),
            Action::make('inviteTeacher')
                ->label('Invite teacher or expert')
                ->icon(Heroicon::OutlinedEnvelope)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->required()->maxLength(255)->unique(User::class, 'email'),
                    Select::make('offering')
                        ->label('Works in')
                        ->options(collect(Offering::cases())->mapWithKeys(fn (Offering $offering) => [$offering->value => $offering->label()]))
                        ->required(),
                    TagsInput::make('subjects')->placeholder('Maths, Physics, Thesis editing…'),
                ])
                ->action(function (array $data, InviteTeacher $invite): void {
                    $invite($data['name'], $data['email'], Offering::from($data['offering']), $data['subjects'] ?? []);

                    Notification::make()
                        ->title('Invitation sent')
                        ->body("{$data['name']} can set a password from the email for the next 7 days.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
