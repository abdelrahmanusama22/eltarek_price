<?php

namespace App\Exports;

use App\Models\PriceEntry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DynamicPriceListExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $uniqueOffers;

    public function __construct()
    {
        // Extract all unique offer titles across all price entries
        $this->uniqueOffers = PriceEntry::query()
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

    public function collection()
    {
        return PriceEntry::with('brand')->get();
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
            ['Export Date: ' . now()->format('d-m-Y')],
            $mainHeaders
        ];
    }

    public function map($row): array
    {
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

        return $mapped;
    }
}
