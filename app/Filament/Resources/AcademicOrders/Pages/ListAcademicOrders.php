<?php

namespace App\Filament\Resources\AcademicOrders\Pages;

use App\Filament\Resources\AcademicOrders\AcademicOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListAcademicOrders extends ListRecords
{
    protected static string $resource = AcademicOrderResource::class;
}
