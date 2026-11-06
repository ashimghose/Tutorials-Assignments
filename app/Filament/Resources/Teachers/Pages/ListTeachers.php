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
     * HIR-7: the teachers and experts in each section, with how many.
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

        return $tabs;
    }
}
