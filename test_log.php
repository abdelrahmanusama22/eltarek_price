<?php
// Update a PriceEntry to trigger activity log
$price = App\Models\PriceEntry::first();
if ($price) {
    $oldPrice = $price->execution_price;
    $price->execution_price = $oldPrice + 10;
    $price->save();

    $log = Spatie\Activitylog\Models\Activity::latest()->first();
    echo "Activity Logged: " . $log->description . "\n";
    echo "Subject: " . $log->subject_type . " ID: " . $log->subject_id . "\n";
    echo "Old Price: " . $log->properties['old']['execution_price'] . "\n";
    echo "New Price: " . $log->properties['attributes']['execution_price'] . "\n";

    // Revert
    $price->execution_price = $oldPrice;
    $price->save();
} else {
    echo "No price entries found.";
}
