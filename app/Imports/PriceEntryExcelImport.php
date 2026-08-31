<?php

namespace App\Imports;

use App\Models\Car;
use App\Models\PriceEntry;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PriceEntryExcelImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $carId = \App\Services\NumericValueNormalizer::normalize($row['car_id'] ?? null);
        
        if (!$carId) return null;

        $car = Car::find($carId);
        
        if (!$car) return null;

        // التحقق من صلاحيات مدير البراند
        if (auth()->user()?->isBrandManager()) {
            $authorizedBrandIds = auth()->user()->brands->pluck('id');
            if (!$authorizedBrandIds->contains($car->brand_id)) {
                return null; // تجاهل السطر لو مش تبعه
            }
        }

        // فك شفرة عمود الألوان من الإكسيل وتحويله لـ Array
        $colorsArray = null;
        if (!empty($row['available_colors'])) {
            $colorsArray = [];
            $items = explode(',', $row['available_colors']);
            
            foreach ($items as $item) {
                $item = trim($item);
                if (preg_match('/^(.*?)\s*(?:\((#[a-fA-F0-9]{3,6})\))?$/', $item, $matches)) {
                    $name = trim($matches[1]);
                    $code = trim($matches[2] ?? '#FFFFFF');
                    if ($name) {
                        $colorsArray[] = [
                            'color_name' => $name,
                            'color_code' => $code,
                        ];
                    }
                }
            }
        }

        // إنشاء أو تحديث السجل
        return PriceEntry::updateOrCreate(
            ['car_id' => $carId],
            [
                'brand_id'         => $car->brand_id,
                'official_price'   => \App\Services\NumericValueNormalizer::normalize($row['official_price'] ?? 0),
                'execution_price'  => \App\Services\NumericValueNormalizer::normalize($row['execution_price'] ?? 0),
                'hold_status'      => $row['hold_status'] ?? 'Available (NO Hold)',
                'additional_info'  => $row['additional_info'] ?? null,
                'available_colors' => $colorsArray,
            ]
        );
    }
}