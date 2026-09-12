<?php

$priceEntries = \App\Models\PriceEntry::whereHas('car')->get();
$totalConflicts = 0;
$mismatchCounts = [];

foreach ($priceEntries as $pe) {
    $conflicts = $pe->getConflictsWithCar($pe->car);
    if (!empty($conflicts)) {
        $totalConflicts++;
        foreach ($conflicts as $field => $data) {
            $mismatchCounts[$field] = ($mismatchCounts[$field] ?? 0) + 1;
        }
    }
}

echo "Total PriceEntries with Car: " . $priceEntries->count() . "\n";
echo "Total Conflicts found: {$totalConflicts}\n";
echo "Mismatch breakdown:\n";
print_r($mismatchCounts);

// Let's dump ONE example for each field
$dumpedFields = [];
foreach ($priceEntries as $pe) {
    $conflicts = $pe->getConflictsWithCar($pe->car);
    foreach ($conflicts as $field => $data) {
        if (!isset($dumpedFields[$field])) {
            echo "\nExample of '{$field}' mismatch (PriceEntry ID: {$pe->id}, Car ID: {$pe->car->id}):\n";
            echo "Car value: " . var_export($data['original_car_value'], true) . "\n";
            echo "PriceEntry value: " . var_export($data['original_entry_value'], true) . "\n";
            $dumpedFields[$field] = true;
        }
    }
}
