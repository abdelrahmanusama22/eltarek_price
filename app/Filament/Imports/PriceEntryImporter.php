<?php

namespace App\Filament\Imports;

use App\Models\Car;
use App\Models\PriceEntry;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PriceEntryImporter extends Importer
{
    protected static ?string $model = PriceEntry::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('model_sales_code')
                ->label('Model Sales Code')
                ->requiredMapping()
                ->rules(['required', 'string']),

            ImportColumn::make('official_price')
                ->label('Official Price')
                ->castStateUsing(function (mixed $state): float {
                    $val = \App\Services\NumericValueNormalizer::normalize($state);
                    return blank($val) ? 0.00 : (float)$val;
                })
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('execution_price')
                ->label('Execution Price')
                ->castStateUsing(function (mixed $state): float {
                    $val = \App\Services\NumericValueNormalizer::normalize($state);
                    return blank($val) ? 0.00 : (float)$val;
                })
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('hold_status')
                ->label('Hold Status')
                ->rules(['nullable', 'in:NO,YES,Wishing List,STOP']),

            ImportColumn::make('additional_info')
                ->label('Additional Info')
                ->rules(['nullable', 'string']),

            ImportColumn::make('available_colors')
                ->label('Available Colors')
                ->castStateUsing(function (?string $state): ?array {
                    if (empty($state)) return null;

                    $colorMap = [
                        'ابيض' => '#FFFFFF',
                        'ابيض لؤلؤى' => '#F3E5AB',
                        'احمر' => '#FF0000',
                        'احمر دارك' => '#8B0000',
                        'اخضر' => '#008000',
                        'ازرق' => '#0000FF',
                        'ازرق بيبسى' => '#004B93',
                        'ازرق زهرى' => '#6495ED',
                        'اسود' => '#000000',
                        'اسود كاربون' => '#040404',
                        'اصفر' => '#FFFF00',
                        'برتقالى' => '#FFA500',
                        'باذنجانى' => '#4B0082',
                        'بترولى' => '#005F69',
                        'بترولى غامق' => '#003F46',
                        'بترولى فاتح' => '#00808C',
                        'بنى' => '#A52A2A',
                        'بنى غامق' => '#5C4033',
                        'بنى فاتح' => '#D2B48C',
                        'برونزى' => '#CD7F32',
                        'موكا' => '#493D26',
                        'بلاتينيوم' => '#E5E4E2',
                        'بيج' => '#F5F5DC',
                        'جرافيتى بلو' => '#2B3542',
                        'جرانيتى' => '#676767',
                        'ذهبى' => '#FFD700',
                        'ذهبى غامق' => '#B8860B',
                        'ذهبى فاتح' => '#EEE8AA',
                        'رصاصى' => '#808080',
                        'رمادى' => '#A9A9A9',
                        'زهرى' => '#FFC0CB',
                        'زيتونى' => '#808000',
                        'زيتى' => '#556B2F',
                        'زيتى فاتح' => '#8FBC8F',
                        'سماوى' => '#87CEEB',
                        'شامبين' => '#F7E7CE',
                        'فانتوم جراى' => '#444C5C',
                        'فضى' => '#C0C0C0',
                        'فضى فاتح' => '#D3D3D3',
                        'فضى مدخن' => '#708090',
                        'فضى ميتالك' => '#AAA9AD',
                        'فيرانى' => '#4F4F4F',
                        'فيرانى غامق' => '#2F4F4F',
                        'فيرانى فاتح' => '#778899',
                        'كحلى' => '#000080',
                        'كحلى غامق' => '#000033',
                        'كحلى فاتح' => '#4169E1',
                        'موف' => '#E0B0FF',
                        'مون ستون جراى' => '#73777B',
                        'نبيتى' => '#800000',
                        'نبيتى غامق' => '#4A0404',
                        'نحاسى' => '#B87333',
                        'بستاج' => '#93C572',
                        'بكينى' => '#F4C2C2',
                        'تركواز' => '#40E0D0',
                        'اسمنتى' => '#A2A2A2',
                        'فستقى' => '#93C572',
                        'رمادى فاتح' => '#D3D3D3',
                        'رمادى غامق' => '#696969',
                        'فضى غامق' => '#8C92AC',
                        'رملى' => '#F4A460',
                        'فيرانى مط' => '#4F4F4F',
                        'كريمى' => '#FFFDD0',
                        'فسدقى مط' => '#93C572',
                    ];

                    $parsedColors = [];
                    $colorBlocks = array_map('trim', explode('|', $state));

                    foreach ($colorBlocks as $block) {
                        if (preg_match('/^(.*?)(?:\s*\((.*?)\))?$/', $block, $matches)) {
                            $colorName = trim($matches[1]);
                            $detailsStr = $matches[2] ?? '';

                            if (empty($colorName)) continue;

                            $interior = null;
                            $adj = null;
                            $notes = null;

                            if (!empty($detailsStr)) {
                                $detailParts = array_map('trim', explode(',', $detailsStr));
                                foreach ($detailParts as $part) {
                                    if (str_starts_with($part, 'Interior:')) {
                                        $interior = trim(str_replace('Interior:', '', $part));
                                    } elseif (str_starts_with($part, 'Price Adj:')) {
                                        $adj = trim(str_replace('Price Adj:', '', $part));
                                    } elseif (str_starts_with($part, 'Notes:')) {
                                        $notes = trim(str_replace('Notes:', '', $part));
                                        $notes = rtrim($notes, ') ');
                                    }
                                }
                            }

                            $colorHex = $colorMap[$colorName] ?? null;

                            $parsedColors[] = [
                                'color_name' => $colorName,
                                'color_code' => $colorHex,
                                'interior_color' => $interior,
                                'additional_price' => \App\Services\NumericValueNormalizer::normalize($adj),
                                'notes' => $notes,
                            ];
                        }
                    }
                    
                    return empty($parsedColors) ? null : $parsedColors;
                }),

            ImportColumn::make('max_selling_price')
                ->label('Max Selling Price')
                ->castStateUsing(fn (mixed $state): mixed => \App\Services\NumericValueNormalizer::normalize($state))
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('protection_3m_price')
                ->label('3M Protection Price')
                ->castStateUsing(fn (mixed $state): mixed => \App\Services\NumericValueNormalizer::normalize($state))
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('model_name')
                ->label('Model Name')
                ->rules(['nullable', 'string']),

            ImportColumn::make('year')
                ->label('Year')
                ->castStateUsing(fn (mixed $state): mixed => \App\Services\NumericValueNormalizer::normalize($state))
                ->rules(['nullable', 'integer']),
        ];
    }

    /**
     * Resolve the record to create or update for each imported row.
     * This is where multi-tenancy validation happens (PRD Section 4.2).
     */
    public function resolveRecord(): ?PriceEntry
    {
        $salesCode = $this->data['model_sales_code'] ?? null;
        
        if (empty($salesCode)) {
            $this->addError('model_sales_code', 'Model Sales Code is required to link the price entry.');
            return null;
        }

        $car = Car::where('model_sales_code', $salesCode)->first();

        // Validation 1: Car must exist in the database.
        if (! $car) {
            $this->addError('model_sales_code', "Car with Sales Code [{$salesCode}] does not exist in the system.");
            return null;
        }
        
        // Save the resolved car_id so beforeCreate/beforeSave can use it
        $this->data['car_id'] = $car->id;

        // Validation 2: The car's brand must be within the uploading
        // Brand Manager's authorized brands (multi-tenancy enforcement).
        if (auth()->user()?->isBrandManager()) {
            $authorizedBrandIds = auth()->user()->brands->pluck('id');

            if (! $authorizedBrandIds->contains($car->brand_id)) {
                $this->addError(
                    'model_sales_code',
                    "Unauthorized: Car with Sales Code [{$salesCode}] belongs to a brand you are not authorized to manage."
                );
                return null;
            }
        }

        // Upsert: find existing entry or instantiate a new one.
        // The PriceEntryObserver `saving` event will automatically
        // compute max_selling_price and protection_3m_price.
        return PriceEntry::firstOrNew(['car_id' => $car->id]);
    }

    /**
     * After successful row resolution, fill in computed/denormalized fields.
     */
    protected function beforeCreate(): void
    {
        /** @var PriceEntry $record */
        $record = $this->record;

        // Denormalize brand_id from the car for query-scoping performance
        if (empty($record->brand_id)) {
            $car = Car::find($this->data['car_id']);
            if ($car) {
                $record->brand_id = $car->brand_id;
            }
        }
    }

    protected function beforeSave(): void
    {
        // Same brand_id sync logic on updates
        $record = $this->record;
        if (empty($record->brand_id) && isset($this->data['car_id'])) {
            $car = Car::find($this->data['car_id']);
            if ($car) {
                $record->brand_id = $car->brand_id;
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your price entry import has completed. '
              . number_format($import->successful_rows)
              . ' '
              . str('row')->plural($import->successful_rows)
              . ' imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' '
                   . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
