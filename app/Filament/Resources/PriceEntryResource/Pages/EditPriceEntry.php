<?php

namespace App\Filament\Resources\PriceEntryResource\Pages;

use App\Filament\Resources\PriceEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriceEntry extends EditRecord
{
    protected static string $resource = PriceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
