<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = \App\Models\PriceEntry::where('brand_id', 1)->whereHas('car', function($q) { $q->where('model_name', 'G2'); })->with(['car', 'brand'])->get();

$html = view('livewire.sales.car-browser', [
    'availableBrands' => \App\Models\Brand::all(),
    'availableModelNames' => ['G2'],
    'results' => $entries,
    'selectedBrand' => 1,
    'selectedModelName' => 'G2'
])->render();

try {
    json_encode($html, JSON_THROW_ON_ERROR);
    echo "SUCCESS HTML\n";
} catch (Exception $e) {
    echo "FAILED ON HTML: " . $e->getMessage() . "\n";
}
