<?php
$r = new ReflectionClass(\Spatie\Activitylog\Support\LogOptions::class);
$methods = $r->getMethods(ReflectionMethod::IS_PUBLIC);
foreach ($methods as $m) {
    echo $m->name . "\n";
}
