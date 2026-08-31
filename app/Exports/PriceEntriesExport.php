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

    public function __construct(Builder $query = null)
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
            'Hold Status',
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
            $record->hold_status,
        ];
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