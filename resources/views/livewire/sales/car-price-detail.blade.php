<div wire:poll.5s>
    <!-- Breadcrumbs -->
    <nav class="flex mb-8 text-sm font-medium text-gray-500 overflow-x-auto whitespace-nowrap pb-2">
        <ol class="inline-flex items-center space-x-2">
            <li class="inline-flex items-center">
                <a href="{{ route('sales.dashboard') }}" class="hover:text-primary transition-colors">Brands</a>
            </li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="inline-flex items-center">
                <a href="{{ route('sales.brand', $car->brand) }}" class="hover:text-primary transition-colors">{{ $car->brand->name }}</a>
            </li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="inline-flex items-center">
                <a href="{{ route('sales.models', ['brand' => $car->brand_id, 'model_name' => $car->model_name]) }}" class="hover:text-primary transition-colors">{{ $car->model_name }}</a>
            </li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="text-gray-900 font-semibold" aria-current="page">
                {{ $car->model_sales_code }}
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8 transition-all duration-300">
        <div class="bg-primary px-8 py-8 sm:py-12 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">{{ $car->model_sales_code }}</h1>
                <p class="text-red-100 mt-2 text-lg font-medium">{{ $car->brand->name }} &bull; {{ $car->model_name }} &bull; {{ $car->year }}</p>
                @if($car->priceEntry && $car->priceEntry->updated_at)
                <div class="flex items-center gap-1.5 mt-3 text-sm text-white/80">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Last updated {{ $car->priceEntry->updated_at->diffForHumans() }}</span>
                    
                    @if($car->priceEntry->lastUpdater)
                        <span>by {{ $car->priceEntry->lastUpdater->name }}</span>
                        @if($car->priceEntry->lastUpdater->roles->count() > 0)
                            <span class="bg-white/20 text-white px-2 py-0.5 rounded-full text-xs ml-1">
                                {{ $car->priceEntry->lastUpdater->roles->first()->name }}
                            </span>
                        @endif
                    @endif
                </div>
                @endif
            </div>
            
            @if($car->priceEntry && $car->priceEntry->total_price && $portalSettings['show_total_calculation'])
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 text-center border border-white/20 min-w-[200px] animate-pulse-once">
                    <p class="text-red-100 text-sm font-bold uppercase tracking-wider mb-1">Total Price</p>
                    <div class="text-3xl font-black text-white transition-all duration-500">
                        @if($car->priceEntry->hold_status === 'Wishing List')
                            <span class="text-xl italic">Wishing List</span>
                        @else
                            {{ number_format($car->priceEntry->total_price, 2) }}
                        @endif
                    </div>
                    @if($car->priceEntry->hold_status !== 'Wishing List')
                        <p class="text-red-100 text-xs mt-2">EGP</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if(!$car->priceEntry)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900">No Pricing Data</h3>
            <p class="mt-2 text-gray-500">Pricing has not been configured for this sales code yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Breakdown -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Price Breakdown -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Price Breakdown
                        </h2>
                        <div wire:loading class="text-xs text-primary font-medium animate-pulse">
                            Updating...
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <dl class="space-y-6">
                            @if($car->priceEntry->hold_status === 'Wishing List')
                                <div class="py-10 text-center">
                                    <p class="text-xl font-bold italic text-gray-400">Wishing List</p>
                                </div>
                            @else
                                @if($portalSettings['show_official_price'])
                                <div class="flex justify-between items-center py-3 border-b border-gray-100 border-dashed">
                                    <dt class="text-sm font-medium text-gray-500">Official Price</dt>
                                    <dd class="text-lg font-semibold text-gray-900 transition-all duration-300">{{ number_format($car->priceEntry->official_price, 2) }} EGP</dd>
                                </div>
                                @endif
                                
                                @if($portalSettings['show_max_selling_price'])
                                <div class="flex justify-between items-center py-3 border-b border-gray-100 border-dashed">
                                    <dt class="text-sm font-medium text-gray-500">Max Selling Price</dt>
                                    <dd class="text-lg font-semibold text-gray-900 transition-all duration-300">{{ number_format($car->priceEntry->max_selling_price, 2) }} EGP</dd>
                                </div>
                                @endif

                                @if($portalSettings['show_execution_price'])
                                <div class="flex justify-between items-center py-3 border-b border-gray-100 border-dashed">
                                    <dt class="text-sm font-medium text-gray-500">Execution Price</dt>
                                    <dd class="text-lg font-semibold text-primary transition-all duration-300">{{ number_format($car->priceEntry->execution_price, 2) }} EGP</dd>
                                </div>
                                @endif

                                @if($portalSettings['show_3m_protection_price'])
                                <div class="flex justify-between items-center py-3">
                                    <dt class="text-sm font-medium text-gray-500">Protection (3M) Price</dt>
                                    <dd class="text-lg font-semibold text-gray-900 transition-all duration-300">{{ number_format($car->priceEntry->protection_3m_price, 2) }} EGP</dd>
                                </div>
                                @endif
                            @endif
                        </dl>
                    </div>
                    @if($portalSettings['show_total_calculation'])
                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-100">
                        <span class="font-bold text-gray-900">Total Calculation</span>
                        <span class="font-black text-xl text-gray-900 transition-all duration-300">
                            @if($car->priceEntry->hold_status === 'Wishing List')
                                <span class="italic text-gray-500 text-sm">Wishing List</span>
                            @else
                                {{ number_format($car->priceEntry->total_price, 2) }} EGP
                            @endif
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Available Colors -->
                @if(is_array($car->priceEntry->available_colors) && count($car->priceEntry->available_colors) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            Available Colors
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-wrap gap-4">
                            @foreach($car->priceEntry->available_colors as $color)
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-full py-1.5 px-3 w-fit">
                                        <span class="w-4 h-4 rounded-full border border-gray-300 shadow-sm" style="background-color: {{ $color['color_code'] ?? '#FFFFFF' }}"></span>
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ $color['color_name'] ?? 'Unknown' }}
                                            @if(!empty($color['interior_color']))
                                                <span class="text-xs text-gray-500 font-normal">({{ $color['interior_color'] }})</span>
                                            @endif
                                        </span>
                                        @if(isset($color['additional_price']) && floatval($color['additional_price']) != 0)
                                            @if($car->priceEntry->hold_status !== 'Wishing List')
                                                @if(floatval($color['additional_price']) > 0)
                                                    <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">+ {{ number_format($color['additional_price']) }} EGP</span>
                                                @else
                                                    <span class="text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">{{ number_format($color['additional_price']) }} EGP</span>
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                    
                                    @if(!empty($color['notes']))
                                        <div class="p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-md flex items-start max-w-sm">
                                            <div class="flex-shrink-0">
                                                <!-- Warning Icon -->
                                                <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-yellow-800">ملاحظة هامة (Important Note)</h3>
                                                <div class="mt-1 text-sm text-yellow-700">
                                                    {{ $color['notes'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Additional Information -->
                @if(!empty($car->priceEntry->additional_info))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Additional Information
                        </h2>
                    </div>
                    <div class="p-6 text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $car->priceEntry->additional_info }}</div>
                </div>
                @endif

                <!-- Documentation & Warranty -->
                @if(!empty($car->priceEntry->warranty_info) || !empty($car->priceEntry->brochure_pdf))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Documentation & Warranty
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(!empty($car->priceEntry->warranty_info))
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 font-medium mb-1">Warranty Information</p>
                            <div class="flex items-center gap-2 text-indigo-700 bg-indigo-50 px-4 py-3 rounded-lg border border-indigo-100 font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.956 11.956 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                {{ $car->priceEntry->warranty_info }}
                            </div>
                        </div>
                        @endif
                        
                        @if(!empty($car->priceEntry->brochure_pdf))
                        <div class="mt-4">
                            <a href="{{ Storage::url($car->priceEntry->brochure_pdf) }}" target="_blank" class="inline-flex items-center justify-center w-full sm:w-auto px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Brochure (PDF)
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>

            <!-- Right Column: Status & Offers -->
            <div class="space-y-6">
                
                <!-- Hold Status -->
                @if($portalSettings['show_availability_status'])
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden transition-colors duration-300">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Availability
                        </h2>
                    </div>
                    <div class="p-6">
                        @if($car->priceEntry->hold_status && $car->priceEntry->hold_status !== 'NO')
                            <div class="{{ $car->priceEntry->hold_status === 'STOP' ? 'bg-red-50 text-red-700 border-red-100' : 'bg-yellow-50 text-yellow-700 border-yellow-100' }} px-4 py-3 rounded-lg border flex items-start gap-3 transition-colors duration-300">
                                <svg class="w-5 h-5 mt-0.5 shrink-0 {{ $car->priceEntry->hold_status === 'STOP' ? 'text-red-500' : 'text-yellow-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <div>
                                    <h4 class="font-bold">Currently on Hold</h4>
                                    <p class="text-sm mt-1 {{ $car->priceEntry->hold_status === 'STOP' ? 'text-red-600' : 'text-yellow-600' }}">This vehicle is temporarily on hold and cannot be sold at this time.</p>
                                </div>
                            </div>
                        @else
                            <div class="bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-100 flex items-start gap-3 transition-colors duration-300">
                                <svg class="w-5 h-5 mt-0.5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h4 class="font-bold">Available for Sale</h4>
                                    <p class="text-sm mt-1 text-green-600">No active holds on this sales code.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Offers -->
                @if($portalSettings['show_special_offers'] && is_array($car->priceEntry->offers) && count($car->priceEntry->offers) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            Special Offers
                        </h2>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-4">
                            @foreach($car->priceEntry->offers as $offer)
                                @if(isset($offer['is_active']) && $offer['is_active'])
                                <li class="flex flex-col gap-1 text-sm text-gray-700 pb-3 border-b border-gray-100 last:border-0 last:pb-0 transition-all duration-300">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div class="flex-grow flex justify-between items-center">
                                            <span class="font-bold text-gray-900">{{ $offer['title'] ?? 'Special Offer' }}</span>
                                            @php
                                                $calcValue = $offer['value'] ?? 0;
                                                $finalOfferPrice = $car->priceEntry->execution_price;
                                                if (($offer['offer_type'] ?? 'fixed') === 'percentage') {
                                                    $finalOfferPrice += ($car->priceEntry->execution_price * ($calcValue / 100));
                                                } else {
                                                    $finalOfferPrice += $calcValue;
                                                }
                                            @endphp
                                            <span class="font-bold text-primary">
                                                @if($car->priceEntry->hold_status === 'Wishing List')
                                                    <span class="italic text-gray-500 text-sm">Wishing List</span>
                                                @else
                                                    {{ number_format($finalOfferPrice, 2) }} EGP
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    @if(!empty($offer['note']))
                                        <p class="text-xs text-gray-500 pl-8">{{ $offer['note'] }}</p>
                                    @endif
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

            </div>
        </div>
    @endif
</div>
