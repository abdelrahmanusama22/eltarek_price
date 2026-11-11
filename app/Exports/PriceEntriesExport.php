<?php

namespace App\Exports;

use App\Models\PriceEntry;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PriceEntriesExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    protected $query;
    protected $currentRow = 1;
    protected $mismatchedCells = [];

    protected $columnMap = [
        'official_price'   => ['crm' => 'official_price',   'col' => 'G'],
        'execution_price'  => ['crm' => 'official_price',   'col' => 'H'],
        'hold_status'      => ['crm' => 'crm_hold_status',  'col' => 'I'],
        'model_name'       => ['crm' => 'model_name',       'col' => 'D'],
        'model_sales_code' => ['crm' => 'model_sales_code', 'col' => 'E'],
        'year'             => ['crm' => 'year',             'col' => 'F'],
    ];

    public function __construct(?Builder $query = null)
    {
        $this->query = $query ?: PriceEntry::query()->with(['car', 'brand']);
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'CRM ID',
            'Brand',
            'Model Name',
            'Sales Code',
            'Year',
            'Official Price',
            'Execution Price',
            'Manual Override',
            'Hold Status',
            'Available Colors',
        ];
    }

    public function map($record): array
    {
        $this->currentRow++;

        foreach ($this->columnMap as $priceField => $info) {
            $crmField = $info['crm'];
            $colLetter = $info['col'];

            if ($record->car && $record->{$priceField} != $record->car->{$crmField}) {
                $this->mismatchedCells[] = $colLetter . $this->currentRow;
            }
        }

        return [
            $record->id,
            $record->car?->crm_id, // تم التعديل هنا لجلب الـ CRM ID الخارجي الصحيح
            $record->brand?->name,
            $record->model_name,
            $record->model_sales_code,
            $record->year,
            $record->official_price,
            $record->execution_price,
            $record->is_manual_override ? 'Yes' : 'No',
            $record->hold_status,
            $this->serializeColors($record->available_colors),
        ];
    }

    protected function serializeColors(?array $colors): string
    {
        if (empty($colors) || !is_array($colors)) {
            return '';
        }

        $formatted = array_map(function ($color) {
            $name = $color['color_name'] ?? '';
            $parts = [];
            if (!empty($color['interior_color'])) {
                $parts[] = 'Interior: ' . trim($color['interior_color']);
            }
            if (!empty($color['roof_color'])) {
                $parts[] = 'Roof: ' . trim($color['roof_color']);
            }
            if (isset($color['stock_count']) && $color['stock_count'] !== '' && $color['stock_count'] !== null) {
                $parts[] = 'Stock: ' . trim($color['stock_count']);
            }
            if (!empty($color['additional_price'])) {
                $parts[] = 'Price Adj: ' . trim($color['additional_price']);
            }
            if (!empty($color['notes'])) {
                $parts[] = 'Notes: ' . trim($color['notes']);
            }

            if (count($parts) > 0) {
                return $name . ' (' . implode(' , ', $parts) . ' )';
            }

            return $name;
        }, $colors);

        return implode(' | ', $formatted);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                foreach ($this->mismatchedCells as $coordinate) {
                    $sheet->getStyle($coordinate)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFFFFF00');
                }
            },
        ];
    }
}