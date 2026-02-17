<?php

namespace App\Filament\Resources\BusinessContextResource\Pages;

use App\Filament\Resources\BusinessContextResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessContexts extends ListRecords
{
    protected static string $resource = BusinessContextResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
