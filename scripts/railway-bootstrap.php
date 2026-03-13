<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function envOrNull(string $key): ?string
{
    $value = getenv($key);

    if ($value === false) {
        return null;
    }

    $value = trim($value);

    return $value === '' ? null : $value;
}

echo "[railway-bootstrap] Running migrations...\n";
Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();

if (! Schema::hasTable('users')) {
    fwrite(STDERR, "[railway-bootstrap] Error: users table does not exist after migrate.\n");
    exit(1);
}

$userCount = (int) DB::table('users')->count();

if ($userCount === 0) {
    $adminName = envOrNull('APP_ADMIN_NAME') ?? envOrNull('ADMIN_NAME') ?? 'Admin';
    $adminEmail = envOrNull('APP_ADMIN_EMAIL') ?? envOrNull('ADMIN_EMAIL');
    $adminPassword = envOrNull('APP_ADMIN_PASSWORD') ?? envOrNull('ADMIN_PASSWORD');

    if (! $adminEmail || ! $adminPassword) {
        fwrite(STDERR, "[railway-bootstrap] Error: APP_ADMIN_EMAIL and APP_ADMIN_PASSWORD (or ADMIN_EMAIL/ADMIN_PASSWORD) are required for first install.\n");
        exit(1);
    }

    echo "[railway-bootstrap] Fresh database detected, running ERP installer...\n";

    Artisan::call('erp:install', [
        '--admin-name'     => $adminName,
        '--admin-email'    => $adminEmail,
        '--admin-password' => $adminPassword,
    ]);

    echo Artisan::output();
    echo "[railway-bootstrap] ERP installation finished.\n";
} else {
    echo "[railway-bootstrap] Existing database detected ({$userCount} users), skipping installer.\n";
}
