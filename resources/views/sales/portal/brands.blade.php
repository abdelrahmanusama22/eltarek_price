@extends('layouts.sales')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Assigned Brands</h1>
    <p class="text-gray-500 mt-2">Select a brand to view available models and price lists.</p>
</div>

    <livewire:sales.quick-filter :brands="$brands" />
@endsection
