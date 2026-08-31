<?php

namespace App\Filament\Exports;

use App\Models\Car;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CarExporter extends Exporter
{
    protected static ?string $model = Car::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Internal ID'),
            ExportColumn::make('actual_crm_id')
                ->label('CRM ID')
                ->state(fn (Car $record) => $record->crm_id),
            ExportColumn::make('brand_id')
                ->label('Brand ID'),
            ExportColumn::make('brand.name')
                ->label('Brand Name'),
            ExportColumn::make('model_name')
                ->label('Model Name'),
            ExportColumn::make('model_sales_code')
                ->label('Model Sales Code'),
            ExportColumn::make('category')
                ->label('Category'),
            ExportColumn::make('year')
                ->label('Year'),
            ExportColumn::make('official_price')
                ->label('Official Price'),
            ExportColumn::make('execution_price')
                ->label('Execution Price'),
            ExportColumn::make('crm_hold_status')
                ->label('CRM Hold Status'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your car export has completed. ' . number_format($export->successful_rows)
              . ' '
              . str('row')->plural($export->successful_rows)
              . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' '
                   . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    
    }
     public function getJobConnection(): ?string
    {
        return 'sync';
    }
}
