<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-fill country_id from region
        $district = $this->record;
        if ($district->region) {
            $data['country_id'] = $district->region->country_id;
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove country_id as it's not a real field on District
        unset($data['country_id']);
        return $data;
    }
}
