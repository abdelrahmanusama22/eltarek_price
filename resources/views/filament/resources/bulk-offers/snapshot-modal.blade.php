<div class="space-y-4">
    {{-- Header Overview Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Offer Type</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white capitalize">
                {{ $bulkOffer->offer_type === 'percentage' ? 'Percentage (%)' : 'Fixed Amount (EGP)' }}
            </span>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Offer Value</span>
            <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                {{ $bulkOffer->offer_type === 'percentage' ? $bulkOffer->value . '%' : number_format($bulkOffer->value) . ' EGP' }}
            </span>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Price Basis</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ $bulkOffer->price_basis === 'max_selling_price' ? 'Retail Price "5%"' : 'Total Retail Price' }}
            </span>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Bound Vehicles</span>
            <span class="text-sm font-semibold text-success-600 dark:text-success-400">
                {{ $vehicles->count() }} Vehicles
            </span>
        </div>
    </div>

    @if (!empty($bulkOffer->note))
        <div class="p-3 bg-blue-50 dark:bg-blue-950/40 border-l-4 border-blue-500 text-xs text-blue-800 dark:text-blue-300 rounded-r">
            <strong>Terms / Note:</strong> {{ $bulkOffer->note }}
        </div>
    @endif

    {{-- Vehicles Table --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden max-h-[60vh] overflow-y-auto">
        <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10 text-gray-700 dark:text-gray-300 uppercase font-semibold text-[11px]">
                <tr>
                    <th class="px-3 py-2.5">Brand</th>
                    <th class="px-3 py-2.5">Model</th>
                    <th class="px-3 py-2.5">Sales Code</th>
                    <th class="px-3 py-2.5 text-right">Base Price</th>
                    <th class="px-3 py-2.5 text-right">Calculated Offer Price</th>
                    <th class="px-3 py-2.5 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @forelse ($vehicles as $entry)
                    @php
                        $base = (float) ($entry->{$bulkOffer->price_basis} ?? 0);
                        $calc = $bulkOffer->offer_type === 'percentage'
                            ? $base + ($base * ($bulkOffer->value / 100))
                            : $base + (float) $bulkOffer->value;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">
                            {{ $entry->brand?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                            {{ $entry->model_name ?? $entry->car?->model_name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 font-mono text-gray-500 dark:text-gray-400">
                            {{ $entry->model_sales_code ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-300">
                            {{ number_format($base) }} EGP
                        </td>
                        <td class="px-3 py-2 text-right font-bold text-success-600 dark:text-success-400">
                            {{ number_format($calc) }} EGP
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a
                                href="{{ \App\Filament\Resources\PriceEntryResource::getUrl('edit', ['record' => $entry->id]) }}"
                                target="_blank"
                                class="inline-flex items-center text-primary-600 hover:text-primary-500 font-semibold text-xs"
                            >
                                Edit Vehicle &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No vehicles are currently bound to this offer. Click <strong>"Re-Sync"</strong> or edit the scope rules to apply it.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
