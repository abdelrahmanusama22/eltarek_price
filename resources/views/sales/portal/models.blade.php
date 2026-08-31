@extends('layouts.sales')

@section('content')
<!-- Breadcrumbs -->
<nav class="flex mb-8 text-sm font-medium text-gray-500">
    <ol class="inline-flex items-center space-x-2">
        <li class="inline-flex items-center">
            <a href="{{ route('sales.dashboard') }}" class="hover:text-primary transition-colors">Brands</a>
        </li>
        <li>
            <span class="mx-2 text-gray-400">/</span>
        </li>
        <li class="text-gray-900 font-semibold" aria-current="page">
            {{ $brand->name }}
        </li>
    </ol>
</nav>

<div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-200">
    @if($brand->logo)
        <div class="w-16 h-16 bg-white rounded-lg shadow-sm border border-gray-100 p-2 flex items-center justify-center shrink-0">
            <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="max-w-full max-h-full object-contain">
        </div>
    @endif
    <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $brand->name }} Models</h1>
        <p class="text-gray-500 mt-1">Select a car model to view available sales codes.</p>
    </div>
</div>

@if($models->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <p class="text-gray-500">No models currently available for this brand.</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($models as $model_name)
            <a href="{{ route('sales.models', ['brand' => $brand->id, 'model_name' => $model_name]) }}" 
               class="group block bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:border-primary hover:shadow-md transition-all duration-200 relative overflow-hidden">
               
               <!-- Accent strip -->
               <div class="absolute top-0 left-0 w-1 h-full bg-gray-200 group-hover:bg-primary transition-colors"></div>
               
               <div class="pl-2">
                   <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">{{ $model_name }}</h3>
                   <div class="mt-4 flex items-center text-sm font-medium text-primary">
                       <span>View Sales Codes</span>
                       <svg class="ml-1 w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                       </svg>
                   </div>
               </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
