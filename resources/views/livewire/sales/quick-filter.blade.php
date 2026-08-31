<div>
    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8 relative z-20">
        @php
            $currentBrandLabel = '-- View All Brands --';
            if ($selectedBrand) {
                $b = $brands->firstWhere('id', $selectedBrand);
                if ($b) $currentBrandLabel = $b->name;
            }

            $currentModelLabel = $selectedModelName ?: '-- Choose a Model --';
        @endphp
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Brand Selection -->
            <div wire:ignore.self>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Brand</label>
                <div x-data="{
                    open: false,
                    search: '',
                    selectedLabel: '{{ addslashes($currentBrandLabel) }}',
                    options: [
                        { id: '', name: '-- View All Brands --' },
                        @foreach($brands as $brand)
                            { id: '{{ $brand->id }}', name: '{{ addslashes($brand->name) }}' },
                        @endforeach
                    ],
                    get filteredOptions() {
                        if (this.search === '') return this.options;
                        return this.options.filter(option => option.name.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectOption(option) {
                        this.selectedLabel = option.name;
                        this.open = false;
                        this.search = '';
                        $wire.set('selectedBrand', option.id);
                    }
                }" class="relative w-full">
                    
                    <!-- Dropdown Trigger -->
                    <button type="button" @click="open = !open" @click.outside="open = false" 
                        class="flex items-center justify-between w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary bg-gray-50 transition-colors text-left text-gray-900">
                        <span x-text="selectedLabel" class="truncate"></span>
                        <svg class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition.opacity.duration.200ms style="display: none;" 
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                        
                        <!-- Search Box -->
                        <div class="p-2 border-b border-gray-100 bg-gray-50">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" x-model="search" placeholder="Search brands..." @click.stop
                                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>

                        <!-- Options List -->
                        <ul class="max-h-60 overflow-y-auto py-1" wire:ignore>
                            <template x-for="opt in filteredOptions" :key="opt.id">
                                <li @click="selectOption(opt)"
                                    class="px-4 py-2.5 hover:bg-red-50 hover:text-primary cursor-pointer text-gray-700 text-sm transition-colors flex items-center justify-between"
                                    :class="{'bg-red-50 text-primary font-medium': selectedLabel === opt.name}">
                                    <span x-text="opt.name"></span>
                                    <svg x-show="selectedLabel === opt.name" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </li>
                            </template>
                            <li x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
                                No brands found.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Model Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Model</label>
                <div wire:key="model-wrapper-{{ $selectedBrand ?? 'default' }}" x-data="{
                    open: false,
                    search: '',
                    selectedLabel: '{{ addslashes($currentModelLabel) }}',
                    options: [
                        { id: '', name: '-- Choose a Model --' },
                        @foreach($availableModelNames as $model)
                            { id: '{{ addslashes($model) }}', name: '{{ addslashes($model) }}' },
                        @endforeach
                    ],
                    get filteredOptions() {
                        if (this.search === '') return this.options;
                        return this.options.filter(option => option.name.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    selectOption(option) {
                        this.selectedLabel = option.name;
                        this.open = false;
                        this.search = '';
                        $wire.set('selectedModelName', option.id);
                    }
                }" class="relative w-full">
                    
                    <!-- Dropdown Trigger -->
                    <button type="button" @click="open = !open" @click.outside="open = false" 
                        class="flex items-center justify-between w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary bg-gray-50 transition-colors text-left text-gray-900">
                        <span x-text="selectedLabel" class="truncate"></span>
                        <svg class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition.opacity.duration.200ms style="display: none;" 
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                        
                        <!-- Search Box -->
                        <div class="p-2 border-b border-gray-100 bg-gray-50">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" x-model="search" placeholder="Search models..." @click.stop
                                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>

                        <!-- Options List -->
                        <ul class="max-h-60 overflow-y-auto py-1" wire:ignore>
                            <template x-for="opt in filteredOptions" :key="opt.id">
                                <li @click="selectOption(opt)"
                                    class="px-4 py-2.5 hover:bg-red-50 hover:text-primary cursor-pointer text-gray-700 text-sm transition-colors flex items-center justify-between"
                                    :class="{'bg-red-50 text-primary font-medium': selectedLabel === opt.name}">
                                    <span x-text="opt.name"></span>
                                    <svg x-show="selectedLabel === opt.name" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </li>
                            </template>
                            <li x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
                                No models found.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div wire:loading class="mt-4 text-primary font-medium text-sm">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-primary inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        </div>
    </div>

    @if($selectedModelName)
        @if(count($results) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($results as $entry)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col hover:shadow-md transition-shadow relative">
                        <!-- Accent Line -->
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-primary-light"></div>
                        
                        <div class="p-6 flex-grow mt-1">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">{{ $entry->car->model_sales_code }}</h3>
                                    <p class="text-sm text-gray-500">{{ $entry->year }} &bull; {{ $entry->car->category }}</p>
                                </div>
                                @if($entry->hold_status !== 'NO')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $entry->hold_status === 'STOP' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $entry->hold_status }}
                                    </span>
                                @endif
                            </div>
                            
                            <div class="mt-4 pb-4 border-b border-gray-100">
                                <p class="text-sm text-gray-500 mb-1">Execution Price</p>
                                @if($entry->hold_status === 'Wishing List')
                                    <p class="text-xl font-bold italic text-gray-500 mt-1">Wishing List</p>
                                @else
                                    <p class="text-2xl font-bold text-gray-900">{{ number_format($entry->execution_price, 2) }} <span class="text-sm font-normal text-gray-500">EGP</span></p>
                                @endif
                                
                                @if($entry->updated_at)
                                    <div class="flex items-center gap-1 mt-3 text-xs text-gray-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Last updated {{ $entry->updated_at->diffForHumans() }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            @if(!empty($entry->offers))
                                <div class="mt-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                                        </svg>
                                        Special Offers Available
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-right mt-auto">
                            <a href="{{ route('sales.price', $entry->car) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none transition-colors w-full justify-center">
                                View Price List &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No Inventory Found</h3>
                <p class="mt-2 text-gray-500 max-w-sm mx-auto">There are no price lists or cars available for this model at the moment.</p>
            </div>
        @endif
    @elseif($selectedBrand)
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </div>
            <p class="text-gray-500 font-medium">Select a model to see available inventory.</p>
        </div>
    @else
        <!-- Default State: Render standard anchor links to step-by-step flow -->
        @if($displayBrands->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No Brands Assigned</h3>
                <p class="mt-2 text-gray-500 max-w-sm mx-auto">There are no brands available in the system yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($displayBrands as $brand)
                    <a href="{{ route('sales.brand', $brand) }}" class="group block w-full text-left bg-white rounded-xl shadow-sm hover:shadow-lg hover:-translate-y-1 border border-gray-100 transition duration-300 overflow-hidden relative focus:outline-none focus:ring-2 focus:ring-primary">
                        <div class="aspect-[4/3] bg-gray-50/50 flex items-center justify-center p-8 group-hover:bg-primary-light/20 transition-colors">
                            @if($brand->logo)
                                <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="w-full h-full object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-24 h-24 rounded-full flex items-center justify-center shadow-md bg-gradient-to-br from-primary to-primary-dark text-white font-bold text-4xl group-hover:scale-105 transition-transform duration-300">
                                    {{ substr($brand->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="p-6 border-t border-gray-100 flex items-center justify-between bg-white relative z-10">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">{{ $brand->name }}</h3>
                            </div>
                            <div class="flex items-center text-primary text-sm font-semibold group-hover:text-primary-dark transition-colors">
                                View Models 
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 transform group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</div>
