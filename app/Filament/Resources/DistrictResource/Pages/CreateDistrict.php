<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove country_id as it's not a real field on District
        unset($data['country_id']);
        return $data;
    }
}
