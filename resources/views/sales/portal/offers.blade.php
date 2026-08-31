@extends('layouts.sales')

@section('content')
<div class="space-y-8">
    <div class="mb-4">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
            </svg>
            Special Offers
        </h1>
        <p class="text-gray-500 mt-2">Discover exclusive hot deals, installments, and promotional pricing.</p>
    </div>

    @if($priceEntries->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No Active Offers</h3>
            <p class="mt-2 text-gray-500 max-w-sm mx-auto">There are currently no active special offers for your assigned brands. Check back later!</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($priceEntries as $entry)
                @php
                    // Get only active offers for this entry
                    $activeOffers = collect($entry->offers)->filter(fn($offer) => isset($offer['is_active']) && $offer['is_active'] == true);
                @endphp
                
                @foreach($activeOffers as $offer)
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-red-100 overflow-hidden flex flex-col relative group">
                        <!-- Hot Badge -->
                        <div class="absolute top-0 right-0 bg-primary text-white text-xs font-bold px-3 py-1 rounded-bl-lg z-10 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
                            </svg>
                            HOT DEAL
                        </div>

                        <!-- Brand Logo bg -->
                        <div class="h-32 bg-gradient-to-br from-red-50 to-white flex items-center justify-center p-6 border-b border-red-50 relative">
                            @if($entry->brand->logo)
                                <img src="{{ Storage::url($entry->brand->logo) }}" alt="{{ $entry->brand->name }}" class="h-full object-contain opacity-20 group-hover:opacity-40 transition-opacity duration-300 absolute inset-0 m-auto">
                            @endif
                            <div class="relative z-10 text-center">
                                <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $entry->car->model_sales_code }}</h3>
                                <p class="text-xs font-semibold text-primary mt-1 uppercase tracking-wider">{{ $entry->brand->name }} • {{ $entry->year }}</p>
                            </div>
                        </div>
                        
                        <div class="p-6 flex-grow flex flex-col">
                            <!-- Offer Title -->
                            <h4 class="text-lg font-bold text-gray-900 mb-3 leading-snug">{{ $offer['title'] ?? 'Special Promotion' }}</h4>
                            
                            <!-- Prices -->
                            <div class="mb-4">
                                @if($entry->hold_status === 'Wishing List')
                                    <div class="flex items-end gap-2 mb-1">
                                        <span class="text-xl font-bold italic text-gray-500 mt-1">Wishing List</span>
                                    </div>
                                @else
                                    <div class="flex items-end gap-2 mb-1">
                                        <span class="text-3xl font-extrabold text-primary">{{ number_format($offer['price'], 2) }}</span>
                                        <span class="text-sm font-bold text-gray-500 mb-1">EGP</span>
                                    </div>
                                    <div class="text-sm text-gray-400 line-through">
                                        Was: {{ number_format($entry->execution_price, 2) }} EGP
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Note -->
                            @if(!empty($offer['note']))
                                <div class="bg-red-50 text-red-800 text-xs p-3 rounded-lg border border-red-100 mt-2 mb-4">
                                    {{ $offer['note'] }}
                                </div>
                            @endif

                            <!-- Available Colors -->
                            @if(!empty($entry->available_colors) && count($entry->available_colors) > 0)
                                <div class="mt-auto pt-4 border-t border-gray-100">
                                    <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wider">Available Colors</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($entry->available_colors as $color)
                                            <div class="group/color relative flex items-center">
                                                <div class="w-6 h-6 rounded-full border-2 border-white shadow-sm ring-1 ring-gray-200" 
                                                     style="background-color: {{ $color['hex_code'] ?? '#ccc' }}"
                                                     title="{{ $color['color_name'] ?? 'Color' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Action Button -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <a href="{{ route('sales.price', $entry->car) }}" class="w-full flex justify-center items-center px-4 py-2.5 border border-transparent text-sm font-bold rounded-lg shadow-sm text-white bg-gray-900 hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                                View Vehicle Details
                            </a>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @endif
</div>
@endsection
