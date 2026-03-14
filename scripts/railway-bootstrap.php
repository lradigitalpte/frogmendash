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

function isUnresolvedTemplate(?string $value): bool
{
    if ($value === null) {
        return false;
    }

    return str_contains($value, '${{') || str_contains($value, '}}');
}

function requireEnv(string $key): string
{
    $value = envOrNull($key);

    if (! $value || isUnresolvedTemplate($value)) {
        fwrite(STDERR, "[railway-bootstrap] Error: {$key} is missing or unresolved. Check Railway Variables references.\n");
        exit(1);
    }

    return $value;
}

// Fail fast with a clear error if DB envs are not wired correctly.
requireEnv('DB_CONNECTION');
requireEnv('DB_HOST');
requireEnv('DB_PORT');
requireEnv('DB_DATABASE');
requireEnv('DB_USERNAME');
requireEnv('DB_PASSWORD');

echo "[railway-bootstrap] Running migrations...\n";
try {
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
} catch (Throwable $e) {
    fwrite(STDERR, '[railway-bootstrap] Migration failed: '.$e->getMessage()."\n");
    exit(1);
}

try {
    if (! Schema::hasTable('users')) {
        fwrite(STDERR, "[railway-bootstrap] Error: users table does not exist after migrate.\n");
        exit(1);
    }

    $userCount = (int) DB::table('users')->count();
} catch (Throwable $e) {
    fwrite(STDERR, '[railway-bootstrap] Post-migrate DB check failed: '.$e->getMessage()."\n");
    exit(1);
}

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
