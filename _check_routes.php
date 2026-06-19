<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$tables = [
    'inventories_warehouses',
    'inventories_routes',
    'inventories_rules',
    'inventories_operation_types',
    'inventories_locations',
];
foreach ($tables as $t) {
    try { echo str_pad($t, 34).DB::table($t)->count().PHP_EOL; }
    catch (\Throwable $e) { echo str_pad($t, 34).'ERR: '.$e->getMessage().PHP_EOL; }
}

echo PHP_EOL."Routes:".PHP_EOL;
try {
    foreach (DB::table('inventories_routes')->get() as $r) {
        echo "  #{$r->id}  ".($r->name ?? '(no name)').PHP_EOL;
    }
} catch (\Throwable $e) { echo '  ERR: '.$e->getMessage().PHP_EOL; }
