<?php

namespace App\Filament\Imports;

use App\Models\Car;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CarImporter extends Importer
{
    protected static ?string $model = Car::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('crm_id')
                ->label('CRM ID')
                ->rules(['nullable', 'string'])
                ->castStateUsing(function (mixed $state) {
                    $raw = strtolower(trim((string)$state));
                    return (empty($raw) || $raw === 'new') ? null : $state;
                }),

            ImportColumn::make('brand')
                ->label('Brand')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): string => trim((string) $state))
                ->relationship(resolveUsing: 'name'),

            ImportColumn::make('model_name')
                ->label('Model Name')
                ->requiredMapping()
                ->rules(['required', 'string']),

            ImportColumn::make('model_sales_code')
                ->label('Model Sales Code')
                ->rules(['nullable', 'string']),

            ImportColumn::make('category')
                ->label('Category')
                ->rules(['nullable', 'string']),

            ImportColumn::make('year')
                ->label('Year')
                ->castStateUsing(function (mixed $state): ?int {
                    if (blank($state)) return null;
                    return (int) \App\Services\NumericValueNormalizer::normalize($state);
                })
                ->rules(['nullable', 'integer']),

            ImportColumn::make('official_price')
                ->label('Official Price')
                ->castStateUsing(function (mixed $state): float {
                    if (blank($state)) return 0.00;
                    return (float) \App\Services\NumericValueNormalizer::normalize($state);
                })
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('execution_price')
                ->label('Execution Price')
                ->numeric()
                ->castStateUsing(function (mixed $state): float {
                    if (blank($state)) return 0.00;
                    return (float) \App\Services\NumericValueNormalizer::normalize($state);
                })
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('crm_hold_status')
                ->label('CRM Hold Status')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Car
    {
        $salesCode = $this->data['model_sales_code'] ?? null;
        $crmId = $this->data['crm_id'] ?? null;

        // 1. First, try to find the car by its reliable internal model_sales_code
        if (!empty($salesCode)) {
            $car = Car::where('model_sales_code', $salesCode)->first();
            if ($car) {
                return $car;
            }
        }

        // 2. If not found by sales code, try to find by CRM ID 
        // (Ensure it's not empty or the word 'new')
        $cleanCrmId = strtolower(trim((string)$crmId));
        if (!empty($cleanCrmId) && $cleanCrmId !== 'new') {
            $car = Car::where('crm_id', $crmId)->first();
            if ($car) {
                return $car;
            }
        }

        // 3. If neither matched, it's genuinely a new car
        return new Car();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your car import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
