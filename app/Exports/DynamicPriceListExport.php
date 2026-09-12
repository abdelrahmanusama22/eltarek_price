<?php

namespace App\Exports;

use App\Models\PriceEntry;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DynamicPriceListExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected array $uniqueOffers;
    protected int $currentRow = 3; // Data starts at row 3 (after 2 heading rows)
    protected array $cellsToHighlight = [];

    protected function getConflictQuery()
    {
        $query = PriceEntry::query()->with(['brand', 'car']);

        $columnMap = [
            'official_price'   => 'official_price',
            'model_name'       => 'model_name',
            'model_sales_code' => 'model_sales_code',
            'year'             => 'year',
            'brand_id'         => 'brand_id',
            'crm_hold_status'  => 'hold_status',
        ];

        return $query->whereHas('car', function ($carQuery) use ($columnMap) {
            $carQuery->where(function ($q) use ($columnMap) {
                foreach ($columnMap as $crmField => $priceField) {
                    $q->orWhere(function ($subQ) use ($crmField, $priceField) {
                        if ($priceField === 'official_price') {
                            $subQ->whereRaw("ROUND(CAST(COALESCE(cars.{$crmField}, 0) AS DECIMAL(15,2)), 2) != ROUND(CAST(COALESCE(price_entries.{$priceField}, 0) AS DECIMAL(15,2)), 2)");
                        } elseif (in_array($priceField, ['model_name', 'model_sales_code', 'hold_status'])) {
                            $subQ->whereRaw("TRIM(LOWER(COALESCE(cars.{$crmField}, ''))) != TRIM(LOWER(COALESCE(price_entries.{$priceField}, '')))");
                        } else {
                            $subQ->whereRaw("COALESCE(cars.{$crmField}, 0) != COALESCE(price_entries.{$priceField}, 0)");
                        }
                        
                        // Ensure this specific field hasn't been ignored
                        $subQ->whereNull("price_entries.ignored_crm_updates->{$priceField}");
                    });
                }
            });
        });
    }

    public function __construct()
    {
        // Extract all unique offer titles strictly across conflicting price entries
        $this->uniqueOffers = $this->getConflictQuery()
            ->whereNotNull('offers')
            ->get()
            ->flatMap(function ($entry) {
                return collect($entry->offers)->pluck('title');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function query()
    {
        // This strictly returns ONLY the conflicted rows for export
        return $this->getConflictQuery();
    }

    public function headings(): array
    {
        $mainHeaders = array_merge([
            'Brand Name',
            'Model',
            'Sales Code',
            'السعر الرسمي',
            'السعر + 5%',
            '3M'
        ], $this->uniqueOffers);

        return [
            ['Export Date: ' . now()->format('d-m-Y') . ' (Conflicting Records Only)'],
            $mainHeaders
        ];
    }

    public function map($row): array
    {
        $conflicts = $row->getConflictsWithCar($row->car);

        // Track cells to highlight based on conflicts
        if (isset($conflicts['brand_id'])) {
            $this->cellsToHighlight[] = 'A' . $this->currentRow;
        }
        if (isset($conflicts['model_name'])) {
            $this->cellsToHighlight[] = 'B' . $this->currentRow;
        }
        if (isset($conflicts['model_sales_code'])) {
            $this->cellsToHighlight[] = 'C' . $this->currentRow;
        }
        if (isset($conflicts['official_price'])) {
            $this->cellsToHighlight[] = 'D' . $this->currentRow;
        }

        $mapped = [
            $row->brand?->name ?? 'N/A',
            $row->model_name ?? 'N/A',
            $row->model_sales_code ?? 'N/A',
            $row->official_price,
            $row->max_selling_price,
            $row->protection_3m_price,
        ];

        $executionPrice = (float) ($row->execution_price ?? 0);
        $rowOffers = collect($row->offers ?? []);

        foreach ($this->uniqueOffers as $offerTitle) {
            // Find the offer with matching title and check if active
            $offer = $rowOffers->first(function ($o) use ($offerTitle) {
                return isset($o['title']) && $o['title'] === $offerTitle && (isset($o['is_active']) ? $o['is_active'] : true);
            });

            if ($offer) {
                $offerType = $offer['offer_type'] ?? 'fixed';
                // Fallback to 'price' if 'value' isn't set, depending on how data is stored
                $value = (float) ($offer['value'] ?? $offer['price'] ?? 0);

                if ($offerType === 'percentage') {
                    $final = $executionPrice + ($executionPrice * ($value / 100));
                } else {
                    $final = $executionPrice + $value;
                }
                
                $mapped[] = number_format($final, 2, '.', '');
            } else {
                $mapped[] = 'غير متاح';
            }
        }

        $this->currentRow++;

        return $mapped;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Style headings
                $sheet->getStyle('A2:' . $sheet->getHighestColumn() . '2')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA']
                    ]
                ]);

                // Highlight conflicting cells
                foreach ($this->cellsToHighlight as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFF00'] // Warning Yellow
                        ]
                    ]);
                }
            },
        ];
    }
}
