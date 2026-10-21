<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

auth()->login(\App\Models\User::first());
\Filament\Facades\Filament::setCurrentPanel('admin');

$entry = \App\Models\PriceEntry::first();


$component = \Livewire\Livewire::test(\App\Livewire\PriceHistoryTable::class, ['recordId' => $entry->id]);
$html = $component->html();

echo "Rendered successfully! Length: " . strlen($html) . PHP_EOL;
echo "Contains 'Recorded Price': " . (str_contains($html, 'Recorded Price') ? 'YES' : 'NO') . PHP_EOL;
echo "Does NOT contain 'Old Price': " . (!str_contains($html, 'Old Price') ? 'YES' : 'NO') . PHP_EOL;
echo "Does NOT contain 'Delta': " . (!str_contains($html, 'Delta') ? 'YES' : 'NO') . PHP_EOL;

$export = new \App\Exports\PriceHistoryExport($component->instance()->getFilteredTableQuery());
echo "Export headings: " . json_encode($export->headings()) . PHP_EOL;
echo "Export sample row: " . json_encode($export->collection()->first()) . PHP_EOL;
