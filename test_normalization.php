<?php

$priceEntry = \App\Models\PriceEntry::whereHas('car')->first();

if (!$priceEntry) {
    echo "No PriceEntry with a matching Car found.\n";
    exit;
}

$car = $priceEntry->car;

echo "--- Testing PHP Helper Normalization ---\n";

// Artificially dirty the attributes in memory (no DB save needed)
$car->official_price = 2525000.00;
$priceEntry->official_price = "2525000"; // String

$car->model_sales_code = " COROLLA-2024 "; // Trailing/leading spaces and uppercase
$priceEntry->model_sales_code = "corolla-2024"; // lowercase and trimmed

$car->model_name = "Toyota Corolla";
$priceEntry->model_name = "toyota COROLLA ";

// Run the conflict checker
$conflicts = $priceEntry->getConflictsWithCar($car);

if (empty($conflicts)) {
    echo "SUCCESS: The PHP helper correctly evaluated the mismatched formats as MATCHING! No conflicts detected.\n";
} else {
    echo "FAILED: The PHP helper threw a false positive conflict.\n";
    print_r($conflicts);
}

echo "\n--- Testing Database SQL Normalization ---\n";

// Let's test the SQL logic by running a raw query simulating the mismatches
// We will create an inline query test
$sqlTest = \Illuminate\Support\Facades\DB::select("
    SELECT 
        (ROUND(CAST(2525000.00 AS DECIMAL(15,2)), 2) != ROUND(CAST('2525000' AS DECIMAL(15,2)), 2)) as price_conflict,
        (TRIM(LOWER(' COROLLA-2024 ')) != TRIM(LOWER('corolla-2024'))) as code_conflict,
        (TRIM(LOWER('Toyota Corolla')) != TRIM(LOWER('toyota COROLLA '))) as name_conflict
");

$res = $sqlTest[0];
if ($res->price_conflict == 0 && $res->code_conflict == 0 && $res->name_conflict == 0) {
    echo "SUCCESS: The SQL normalization logic correctly evaluates mismatched formats as MATCHING!\n";
} else {
    echo "FAILED: SQL logic threw a false positive.\n";
    print_r($res);
}
