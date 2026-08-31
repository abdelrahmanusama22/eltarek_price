<?php

namespace App\Filament\Exports;

use App\Models\PriceEntry;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PriceEntryExporter extends Exporter
{
    protected static ?string $model = PriceEntry::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('actual_crm_id')
                ->label('CRM ID')
                ->state(fn (PriceEntry $record) => $record->car?->crm_id),
            ExportColumn::make('car.id')->label('Internal Car ID'),
            ExportColumn::make('official_price')->label('Official Price'),
            ExportColumn::make('execution_price')->label('Execution Price'),
            ExportColumn::make('hold_status')->label('Hold Status'),
            ExportColumn::make('additional_info')->label('Additional Info'),
            ExportColumn::make('brand.name')->label('Brand'),
            ExportColumn::make('available_colors')
                ->label('Available Colors')
                ->state(function ($record) {
                    $colors = $record->available_colors ?? [];
                    if (!is_array($colors) || empty($colors)) {
                        return '';
                    }

                    $formatted = array_map(function ($color) {
                        $name = $color['color_name'] ?? '';
                        $parts = [];
                        if (!empty($color['interior_color'])) {
                            $parts[] = 'Interior: ' . $color['interior_color'];
                        }
                        if (!empty($color['additional_price'])) {
                            // Ensure no trailing spaces mess up the number formatting
                            $parts[] = 'Price Adj: ' . trim($color['additional_price']);
                        }
                        if (!empty($color['notes'])) {
                            $parts[] = 'Notes: ' . trim($color['notes']);
                        }

                        if (count($parts) > 0) {
                            // Added a space before the closing parenthesis to fix RTL rendering in Excel
                            return $name . ' (' . implode(' , ', $parts) . ' )';
                        }
                        
                        return $name;
                    }, $colors);

                    return implode(' | ', $formatted);
                }),
            ExportColumn::make('max_selling_price')->label('Max Selling Price'),
            ExportColumn::make('protection_3m_price')->label('3M Protection Price'),
            ExportColumn::make('model_name')->label('Model Name'),
            ExportColumn::make('model_sales_code')->label('Model Sales Code'),
            ExportColumn::make('year')->label('Year'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your price entry export has completed. '
              . number_format($export->successful_rows)
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
