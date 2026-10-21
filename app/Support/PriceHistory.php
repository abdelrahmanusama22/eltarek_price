<?php

namespace App\Support;

use Spatie\Activitylog\Models\Activity;

/**
 * Extracts the individual price-field changes out of a Spatie activity record.
 * Shared by the Price History table and the Excel export so both always agree.
 */
class PriceHistory
{
    /** @var array<string, string> */
    public const FIELDS = [
        'official_price'      => 'Official Price',
        'execution_price'     => 'Execution Price',
        'max_selling_price'   => 'Max Selling Price',
        'protection_3m_price' => '3M Protection Price',
    ];

    /**
     * @return array<int, array{label: string, old: ?float, new: ?float, diff: ?float, pct: ?float}>
     */
    public static function fieldChanges(Activity $activity): array
    {
        $changes = $activity->attribute_changes;
        $changes = is_array($changes) ? $changes : ($changes?->toArray() ?? []);

        $old       = $changes['old'] ?? [];
        $attrs     = $changes['attributes'] ?? [];
        $isCreated = $activity->event === 'created';

        $rows = [];

        foreach (self::FIELDS as $key => $label) {
            if (! array_key_exists($key, $attrs) && ! array_key_exists($key, $old)) {
                continue;
            }

            $oldVal = $isCreated || ! isset($old[$key]) ? null : (float) $old[$key];
            $newVal = isset($attrs[$key]) ? (float) $attrs[$key] : null;

            if (! $isCreated && $oldVal === $newVal) {
                continue;
            }

            $diff = ($oldVal !== null && $newVal !== null) ? $newVal - $oldVal : null;
            $pct  = ($diff !== null && $oldVal > 0) ? ($diff / $oldVal) * 100 : null;

            $rows[] = ['label' => $label, 'old' => $oldVal, 'new' => $newVal, 'diff' => $diff, 'pct' => $pct];
        }

        return $rows;
    }

    /**
     * Snapshot mode: returns only the recorded prices from the `attributes` array (after event).
     *
     * @return array<int, array{label: string, price: ?float}>
     */
    public static function recordedPrices(Activity $activity): array
    {
        $changes = $activity->attribute_changes;
        $changes = is_array($changes) ? $changes : ($changes?->toArray() ?? []);

        // Snapshot of the model state after the event
        $attrs = $changes['attributes'] ?? [];

        $rows = [];

        foreach (self::FIELDS as $key => $label) {
            if (! array_key_exists($key, $attrs)) {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'price' => isset($attrs[$key]) && $attrs[$key] !== '' ? (float) $attrs[$key] : null,
            ];
        }

        return $rows;
    }


    public static function money(?float $value): string
    {
        return $value === null ? '—' : number_format($value, 2);
    }

    public static function formatDelta(?float $diff, ?float $pct): string
    {
        if ($diff === null) {
            return '—';
        }

        $sign = $diff > 0 ? '+' : '';
        $text = ($diff > 0 ? '▲ ' : ($diff < 0 ? '▼ ' : '')) . $sign . number_format($diff, 2);

        return $pct !== null ? $text . ' (' . $sign . number_format($pct, 1) . '%)' : $text;
    }
}
