<?php

namespace App\Filament\Resources\PriceEntryResource\Pages;

use App\Filament\Resources\PriceEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceEntry extends CreateRecord
{
    protected static string $resource = PriceEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $car = \App\Models\Car::firstOrCreate(
            ['model_sales_code' => $data['model_sales_code']],
            [
                'brand_id' => $data['brand_id'],
                'model_name' => $data['model_name'],
                'year' => $data['year'] ?? null,
            ]
        );

        $data['car_id'] = $car->id;
        
        return $data;
    }
}
