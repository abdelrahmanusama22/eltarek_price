@extends('layouts.sales')

@section('content')
<!-- Breadcrumbs -->
<nav class="flex mb-8 text-sm font-medium text-gray-500 overflow-x-auto whitespace-nowrap pb-2">
    <ol class="inline-flex items-center space-x-2">
        <li class="inline-flex items-center">
            <a href="{{ route('sales.dashboard') }}" class="hover:text-primary transition-colors">Brands</a>
        </li>
        <li><span class="mx-2 text-gray-400">/</span></li>
        <li class="inline-flex items-center">
            <a href="{{ route('sales.brand', $brand) }}" class="hover:text-primary transition-colors">{{ $brand->name }}</a>
        </li>
        <li><span class="mx-2 text-gray-400">/</span></li>
        <li class="text-gray-900 font-semibold" aria-current="page">
            {{ $model_name }}
        </li>
    </ol>
</nav>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $brand->name }} {{ $model_name }}</h1>
    <p class="text-gray-500 mt-1">Select a specific sales code to view detailed pricing and offers.</p>
</div>

@if($priceEntries->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900">No Inventory Found</h3>
        <p class="mt-2 text-gray-500 max-w-sm mx-auto">There are no price lists or cars available for this model at the moment.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($priceEntries as $entry)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col hover:shadow-md transition-shadow relative">
                <!-- Accent Line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-primary-light"></div>
                
                <div class="p-6 flex-grow mt-1">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">{{ $entry->car->model_sales_code }}</h3>
                            <p class="text-sm text-gray-500">{{ $entry->year }} &bull; {{ $entry->car->category }}</p>
                            @if($entry->warranty_info)
                                <div class="mt-2 flex items-center gap-1 text-xs text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-1 rounded-md w-fit font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.956 11.956 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    {{ $entry->warranty_info }}
                                </div>
                            @endif
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
@endif
@endsection
