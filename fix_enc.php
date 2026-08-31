<?php
function fix_encoding($file) {
    $data = file_get_contents($file);
    if (!mb_check_encoding($data, 'UTF-8')) {
        $data = mb_convert_encoding($data, 'UTF-8', 'Windows-1252');
        file_put_contents($file, $data);
        echo "Fixed $file\n";
    } else {
        echo "$file is valid UTF-8\n";
    }
}
fix_encoding('resources/views/livewire/sales/car-browser.blade.php');
fix_encoding('resources/views/sales/portal/offers.blade.php');
