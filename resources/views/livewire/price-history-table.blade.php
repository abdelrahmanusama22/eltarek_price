<div>
    @php($money = fn ($v) => $v === null || $v === '' ? '—' : number_format((float) $v, 2))

    <x-filament::section compact>
        <x-slot name="heading">
            {{ trim(($record->car?->brand?->name ?? '') . ' ' . ($record->car?->model_name ?? '')) ?: 'Vehicle' }}
        </x-slot>
        <x-slot name="description">{{ $record->model_sales_code }}</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.75rem">
            @foreach ([
                'Official'      => $record->official_price,
                'Execution'     => $record->execution_price,
                'Max Selling'   => $record->max_selling_price,
                '3M Protection' => $record->protection_3m_price,
                'Total'         => $record->total_price,
            ] as $label => $value)
                <div>
                    <div style="font-size:.6875rem;text-transform:uppercase;letter-spacing:.04em;opacity:.6">{{ $label }}</div>
                    <div style="font-weight:600;font-variant-numeric:tabular-nums">{{ $money($value) }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <div style="margin-top:1rem">
        {{ $this->table }}
    </div>

    <x-filament-actions::modals />
</div>
