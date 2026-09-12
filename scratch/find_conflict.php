<?php

// Find any PriceEntry that is considered conflicting
$priceEntries = \App\Models\PriceEntry::whereHas('car')->get();
$found = false;

foreach ($priceEntries as $pe) {
    $conflicts = $pe->getConflictsWithCar($pe->car);
    if (!empty($conflicts)) {
        echo "Found Conflict on PriceEntry ID: {$pe->id} (Car ID: {$pe->car->id})\n";
        print_r($conflicts);
        $found = true;
        break; // just show one
    }
}

if (!$found) {
    echo "No conflicts found in the database at all.\n";
}
