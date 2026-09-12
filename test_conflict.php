<?php
// Scratch script to test PriceEntry <-> Car conflict logic
$priceEntry = \App\Models\PriceEntry::whereHas('car')->first();

if (!$priceEntry) {
    echo "No PriceEntry with a matching Car found.\n";
    exit;
}

echo "Found PriceEntry ID: {$priceEntry->id}, Car ID: {$priceEntry->car->id}\n";
echo "Original PriceEntry Official Price: {$priceEntry->official_price}\n";
echo "Original Car Official Price: {$priceEntry->car->official_price}\n";

$originalCarPrice = $priceEntry->car->official_price;
$testPrice = $originalCarPrice + 1000;

// Update Car's official price
$priceEntry->car->update(['official_price' => $testPrice]);
echo "Updated Car Official Price to: {$testPrice} to simulate a conflict.\n";

// Test the ListPriceEntries Requires Review tab query
$columnMap = [
    'official_price'   => 'official_price',
    'model_name'       => 'model_name',
    'model_sales_code' => 'model_sales_code',
    'year'             => 'year',
    'brand_id'         => 'brand_id',
    'crm_hold_status'  => 'hold_status',
];

$query = \App\Models\PriceEntry::query();
$query->whereHas('car', function ($carQuery) use ($columnMap) {
    $carQuery->where(function ($q) use ($columnMap) {
        foreach ($columnMap as $crmField => $priceField) {
            $q->orWhere(function ($subQ) use ($crmField, $priceField) {
                $subQ->whereRaw("NOT (cars.{$crmField} <=> price_entries.{$priceField})")
                     ->whereNull("price_entries.ignored_crm_updates->{$priceField}");
            });
        }
    });
});

$requiresReviewIds = $query->pluck('id')->toArray();
if (in_array($priceEntry->id, $requiresReviewIds)) {
    echo "SUCCESS: PriceEntry ID {$priceEntry->id} correctly appeared in the Requires Review query!\n";
} else {
    echo "FAILED: PriceEntry ID {$priceEntry->id} did NOT appear in the Requires Review query.\n";
}

// Revert the car's official price back
$priceEntry->car->update(['official_price' => $originalCarPrice]);
echo "Reverted Car Official Price back to: {$originalCarPrice}\n";
