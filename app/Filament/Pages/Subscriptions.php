<?php

namespace App\Filament\Pages;

use App\Enums\EnrolmentStatus;
use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * ADM-3: students per batch by enrolment status, and the next paid-through
 * date to fall due. Open a batch to see each child's paid-through date.
 */
class Subscriptions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static ?string $title = 'Subscriptions';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return Auth::user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        $count = fn (EnrolmentStatus $status) => fn (Builder $q) => $q->where('status', $status);

        return $table
            ->query(Batch::query()->with('teacher.user')->withCount([
                'enrolments as active_count' => $count(EnrolmentStatus::Active),
                'enrolments as pending_count' => $count(EnrolmentStatus::Pending),
                'enrolments as expired_count' => $count(EnrolmentStatus::Expired),
                'enrolments as removed_count' => $count(EnrolmentStatus::Removed),
            ])->withMin(['enrolments as next_due' => $count(EnrolmentStatus::Active)], 'paid_through'))
            ->defaultSort('class_level')
            ->recordUrl(fn (Batch $record): string => BatchResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')->label('Batch')->state(fn (Batch $record): string => $record->name()),
                TextColumn::make('teacher.user.name')->label('Teacher')->placeholder('—'),
                TextColumn::make('seats')->state(fn (Batch $record): string => ((int) $record->getAttribute('active_count') + (int) $record->getAttribute('pending_count')).' / '.$record->max_seats),
                TextColumn::make('active_count')->label('Active')->numeric()->color('success'),
                TextColumn::make('pending_count')->label('Waiting for fee')->numeric()->color('warning'),
                TextColumn::make('expired_count')->label('Expired')->numeric()->color('danger'),
                TextColumn::make('removed_count')->label('Removed')->numeric()->color('gray'),
                TextColumn::make('next_due')->label('Next paid-through')->date('j M Y')->placeholder('—')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Batch open')->default(true),
            ]);
    }
}
