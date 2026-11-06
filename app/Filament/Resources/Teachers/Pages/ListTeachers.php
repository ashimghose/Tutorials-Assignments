<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Enums\Offering;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

    /**
     * HIR-7: the teachers and experts in each section, with how many, and
     * HIR-8: those waiting for approval.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('All')->badge(Teacher::query()->count())];

        foreach (Offering::sections() as $offering) {
            $tabs[$offering->value] = Tab::make($offering->label())
                ->badge(Teacher::query()->worksInOffering($offering)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereJsonContains('offerings', $offering->value));
        }

        // HIR-8: teachers who signed up themselves and wait for approval.
        $tabs['pending'] = Tab::make('Waiting for approval')
            ->badge(Teacher::query()->whereNull('approved_at')->count())
            ->badgeColor('warning')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('approved_at'));

        return $tabs;
    }
}
