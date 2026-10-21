<?php

namespace App\Exports;

use App\Support\PriceHistory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports the (already date-filtered) price history query.
 * One Excel row per changed price field, so the sheet is easy to pivot/filter.
 */
class PriceHistoryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(protected Builder $query) {}

    public function headings(): array
    {
        return ['Date', 'Time', 'User', 'Field', 'Recorded Price'];
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->query->with('causer')->reorder()->orderByDesc('created_at')->cursor() as $activity) {
            foreach (PriceHistory::recordedPrices($activity) as $change) {
                $rows->push([
                    $activity->created_at->format('Y-m-d'),
                    $activity->created_at->format('H:i:s'),
                    $activity->causer?->name ?? 'System',
                    $change['label'],
                    $change['price'],
                ]);
            }
        }

        return $rows;
    }


    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
