<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Accounts\InviteTeacher;
use App\Enums\Offering;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
