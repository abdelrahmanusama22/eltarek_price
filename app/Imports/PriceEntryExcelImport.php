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
            $rawColors = trim($row['available_colors']);
            if (str_starts_with($rawColors, '[') && is_array($json = json_decode($rawColors, true))) {
                $colorsArray = array_map(function ($c) {
                    return [
                        'color_name'       => $c['color_name'] ?? '',
                        'color_code'       => $c['color_code'] ?? '#FFFFFF',
                        'interior_color'   => $c['interior_color'] ?? null,
                        'roof_color'       => $c['roof_color'] ?? null,
                        'stock_count'      => isset($c['stock_count']) && $c['stock_count'] !== '' ? (int)$c['stock_count'] : 0,
                        'additional_price' => isset($c['additional_price']) ? \App\Services\NumericValueNormalizer::normalize($c['additional_price']) : null,
                        'notes'            => $c['notes'] ?? null,
                    ];
                }, $json);
            } else {
                $colorsArray = [];
                $delimiter = str_contains($rawColors, '|') ? '|' : ',';
                $items = explode($delimiter, $rawColors);
                
                foreach ($items as $item) {
                    $item = trim($item);
                    if (preg_match('/^(.*?)\s*(?:\((.*?)\))?$/', $item, $matches)) {
                        $name = trim($matches[1]);
                        $detailsStr = trim($matches[2] ?? '');
                        if ($name) {
                            $code = '#FFFFFF';
                            $interior = null;
                            $roof = null;
                            $stock = 0;
                            $adj = null;
                            $notes = null;

                            if (str_starts_with($detailsStr, '#')) {
                                $code = $detailsStr;
                            } elseif (!empty($detailsStr)) {
                                $parts = array_map('trim', explode(',', $detailsStr));
                                foreach ($parts as $part) {
                                    if (str_starts_with($part, 'Interior:')) {
                                        $interior = trim(str_replace('Interior:', '', $part));
                                    } elseif (str_starts_with($part, 'Roof:')) {
                                        $roof = trim(str_replace('Roof:', '', $part));
                                    } elseif (str_starts_with($part, 'Stock:')) {
                                        $stock = (int) trim(str_replace('Stock:', '', $part));
                                    } elseif (str_starts_with($part, 'Price Adj:')) {
                                        $adj = \App\Services\NumericValueNormalizer::normalize(trim(str_replace('Price Adj:', '', $part)));
                                    } elseif (str_starts_with($part, 'Notes:')) {
                                        $notes = rtrim(trim(str_replace('Notes:', '', $part)), ') ');
                                    }
                                }
                            }

                            $colorsArray[] = [
                                'color_name'       => $name,
                                'color_code'       => $code,
                                'interior_color'   => $interior,
                                'roof_color'       => $roof,
                                'stock_count'      => $stock,
                                'additional_price' => $adj,
                                'notes'            => $notes,
                            ];
                        }
                    }
                }
            }
        } elseif (!empty($row['color_name']) || !empty($row['roof_color'])) {
            $colorsArray = [[
                'color_name'       => $row['color_name'] ?? 'Default',
                'color_code'       => $row['color_code'] ?? '#FFFFFF',
                'interior_color'   => $row['interior_color'] ?? null,
                'roof_color'       => $row['roof_color'] ?? null,
                'stock_count'      => isset($row['stock_count']) && $row['stock_count'] !== '' ? (int)$row['stock_count'] : 0,
                'additional_price' => isset($row['additional_price']) ? \App\Services\NumericValueNormalizer::normalize($row['additional_price']) : null,
                'notes'            => $row['notes'] ?? null,
            ]];
        }

        $isManualOverride = false;
        if (isset($row['is_manual_override']) || isset($row['manual_override'])) {
            $val = strtolower(trim((string) ($row['is_manual_override'] ?? $row['manual_override'])));
            $isManualOverride = in_array($val, ['1', 'true', 'yes', 'y', 'نعم', 'on'], true);
        }

        $attributes = [
            'brand_id'           => $car->brand_id,
            'official_price'     => \App\Services\NumericValueNormalizer::normalize($row['official_price'] ?? 0),
            'execution_price'    => \App\Services\NumericValueNormalizer::normalize($row['execution_price'] ?? 0),
            'hold_status'        => $row['hold_status'] ?? 'Available (NO Hold)',
            'additional_info'    => $row['additional_info'] ?? null,
            'available_colors'   => $colorsArray,
            'is_manual_override' => $isManualOverride,
        ];

        if ($isManualOverride) {
            if (isset($row['max_selling_price'])) {
                $attributes['max_selling_price'] = \App\Services\NumericValueNormalizer::normalize($row['max_selling_price']);
            }
            if (isset($row['protection_3m_price'])) {
                $attributes['protection_3m_price'] = \App\Services\NumericValueNormalizer::normalize($row['protection_3m_price']);
            }
        }

        // إنشاء أو تحديث السجل
        return PriceEntry::updateOrCreate(
            ['car_id' => $carId],
            $attributes
        );
    }
}