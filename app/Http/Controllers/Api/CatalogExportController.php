<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\JsonResponse;

class CatalogExportController extends Controller
{
    public function export(): JsonResponse
    {
        $cars = Car::with(['brand', 'priceEntry'])->get();

        // Group by brand_id, model_name, year, category
        $grouped = $cars->groupBy(function ($car) {
            return $car->brand_id . '_' . $car->model_name . '_' . $car->year . '_' . $car->category;
        });

        $data = [];

        foreach ($grouped as $groupCars) {
            $firstCar = $groupCars->first();

            $trims = $groupCars->map(function ($car) {
                // Trim price_egp should be mapped from price_entries.official_price (fallback to cars.official_price if null).
                $priceEgp = $car->priceEntry?->official_price ?? $car->official_price;

                // Trim active (boolean) should be mapped from price_entries.hold_status (set to true if 'NO', otherwise false. Fallback to cars.crm_hold_status).
                if ($car->priceEntry) {
                    $active = strtoupper((string) $car->priceEntry->hold_status) === 'NO';
                } else {
                    $active = strtoupper((string) $car->crm_hold_status) === 'NO';
                }

                return [
                    'name' => $car->model_sales_code,
                    'price_egp' => (float) $priceEgp,
                    'active' => $active,
                ];
            })->values()->toArray();

            $data[] = [
                'brand_id' => $firstCar->brand_id,
                'brand_name' => $firstCar->brand?->name ?? '',
                'model' => $firstCar->model_name,
                'model_ar' => $firstCar->model_name, // model_ar is not available, falling back to model_name
                'year' => $firstCar->year,
                'category' => $firstCar->category,
                'trims' => $trims,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
