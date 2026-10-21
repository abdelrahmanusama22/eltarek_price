<?php

namespace App\Exports;

use App\Filament\Resources\PriceEntryResource;
use App\Models\PriceEntry;
use App\Support\PriceHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;

/**
 * End-of-day export: price change history for ALL price entries the
 * current user is allowed to see, within a date range.
 * One Excel row per activity (per save).
 */
class GlobalPriceHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Carbon $start;
    protected Carbon $end;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $start = $startDate ? Carbon::parse($startDate) : now();
        $end   = $endDate ? Carbon::parse($endDate) : $start->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $this->start = $start->startOfDay();
        $this->end   = $end->endOfDay();
    }

    public function query(): Builder
    {
        return Activity::query()
            ->where('subject_type', (new PriceEntry)->getMorphClass())
            // BRAND SCOPING: only activities whose PriceEntry the current user may access.
            // Reuses the resource's own scope (assigned brands, hidden STOP entries,
            // super_admin bypass) so the export can never leak more than the UI shows.
            ->whereHasMorph('subject', [PriceEntry::class], function (Builder $q) {
                $q->whereIn(
                    $q->getModel()->qualifyColumn('id'),
                    PriceEntryResource::getEloquentQuery()->select('price_entries.id')
                );
            })
            // Only saves that touched at least one price field.
            ->where(function (Builder $q) {
                foreach (array_keys(PriceHistory::FIELDS) as $field) {
                    $q->orWhere('attribute_changes', 'like', '%"' . $field . '"%');
                }
            })
            ->whereBetween('created_at', [$this->start, $this->end])
            ->with(['causer', 'subject.brand'])
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function headings(): array
    {
        $labels = array_values(PriceHistory::FIELDS);

        return array_merge(
            ['Date', 'Time', 'User', 'Event', 'Brand', 'Model', 'Sales Code'],
            array_map(fn ($l) => "Old {$l}", $labels),
            array_map(fn ($l) => "New {$l}", $labels),
        );
    }

    /**
     * @param  Activity  $activity
     */
    public function map($activity): array
    {
        /** @var PriceEntry|null $entry */
        $entry = $activity->subject;

        $byLabel = collect(PriceHistory::fieldChanges($activity))->keyBy('label');

        $old = [];
        $new = [];
        foreach (PriceHistory::FIELDS as $label) {
            $change = $byLabel->get($label);
            // Blank cell = that price was not changed in this save.
            $old[] = $change['old'] ?? null;
            $new[] = $change['new'] ?? null;
        }

        return array_merge([
            $activity->created_at->format('Y-m-d'),
            $activity->created_at->format('H:i:s'),
            $activity->causer?->name ?? 'System',
            ucfirst((string) $activity->event),
            $entry?->brand?->name,
            $entry?->model_name,
            $entry?->model_sales_code,
        ], $old, $new);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
