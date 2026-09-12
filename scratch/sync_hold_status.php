<?php
$priceEntries = \App\Models\PriceEntry::whereHas('car')->get();
$synced = 0;
foreach ($priceEntries as $pe) {
    if ($pe->car->crm_hold_status !== $pe->hold_status) {
        $pe->hold_status = $pe->car->crm_hold_status;
        $pe->saveQuietly();
        $synced++;
    }
}
echo "Synced {$synced} hold_status values from cars!\n";
