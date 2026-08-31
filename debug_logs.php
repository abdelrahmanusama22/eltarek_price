<?php
// Check what's in the activity_log table
echo DB::table('activity_log')->count() . " total logs\n";

$logs = DB::table('activity_log')->get();
foreach ($logs as $log) {
    echo "ID: {$log->id} | event: {$log->event} | subject_type: {$log->subject_type} | subject_id: {$log->subject_id}\n";
    echo "  properties: " . substr($log->properties, 0, 80) . "\n";
    echo "  attribute_changes: " . substr($log->attribute_changes ?? 'NULL', 0, 80) . "\n";
}

// Check which columns exist
echo "\n-- COLUMNS --\n";
$cols = DB::select('SHOW COLUMNS FROM activity_log');
foreach ($cols as $col) {
    echo "  {$col->Field} ({$col->Type})\n";
}
