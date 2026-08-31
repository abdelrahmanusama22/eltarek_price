<?php

namespace App\Exports;

use App\Models\Car;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CarExcelExport implements FromQuery, WithMapping, WithHeadings
{
    public function query()
    {
        return Car::query()->with('brand');
    }

    public function map($car): array
    {
        return [
            $car->id,
            $car->brand_id,
            $car->brand?->name,
            $car->model_name,
            $car->model_sales_code,
            $car->category,
            $car->year,
            $car->official_price,
            $car->crm_hold_status,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Brand ID',
            'Brand Name',
            'Model Name',
            'Model Sales Code',
            'Category',
            'Year',
            'Official Price',
            'CRM Hold Status',
        ];
    }
}
