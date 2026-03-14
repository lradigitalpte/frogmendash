<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

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

// Ensure APP_URL is set with HTTPS for Railway deployment
if (! getenv('APP_URL')) {
    $railwayPublicDomain = getenv('RAILWAY_PUBLIC_DOMAIN');
    if ($railwayPublicDomain) {
        $appUrl = 'https://' . $railwayPublicDomain;
        putenv("APP_URL={$appUrl}");
        echo "[railway-bootstrap] APP_URL not set, configured as: {$appUrl}\n";
    } else {
        fwrite(STDERR, "[railway-bootstrap] Warning: APP_URL and RAILWAY_PUBLIC_DOMAIN not set. CSS/JS may not load correctly.\n");
    }
}

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

echo "[railway-bootstrap] Optimizing framework caches...\n";
try {
    Artisan::call('optimize');
    echo Artisan::output();
} catch (Throwable $e) {
    fwrite(STDERR, '[railway-bootstrap] Warning: optimize failed: '.$e->getMessage()."\n");
}

echo "[railway-bootstrap] Refreshing panel permissions...\n";
try {
    if (
        Schema::hasTable('roles')
        && Schema::hasTable('permissions')
        && Schema::hasTable('model_has_roles')
        && Schema::hasTable('role_has_permissions')
    ) {
        $forceRefresh = filter_var((string) (envOrNull('FORCE_PERMISSION_REFRESH_ON_BOOT') ?? 'false'), FILTER_VALIDATE_BOOLEAN);
        $hasRoleAssignments = DB::table('model_has_roles')->exists();
        $permissionCount = (int) DB::table('permissions')->count();
        $rolePermissionCount = (int) DB::table('role_has_permissions')->count();

        // Refresh if explicitly forced, if role assignments exist but no role permissions,
        // or when permission coverage looks incomplete.
        $needsRefresh = $forceRefresh
            || ($hasRoleAssignments && $rolePermissionCount === 0)
            || ($permissionCount > 0 && $rolePermissionCount < $permissionCount);

        if ($needsRefresh) {
            Artisan::call('shield:generate', [
                '--all' => true,
                '--option' => 'permissions',
                '--panel' => 'admin',
            ]);

            $roleModel = config('permission.models.role', \Spatie\Permission\Models\Role::class);
            $permissionModel = config('permission.models.permission', \Spatie\Permission\Models\Permission::class);

            if (class_exists($roleModel) && class_exists($permissionModel)) {
                $allPermissions = $permissionModel::query()->get();
                $roleModel::query()->get()->each(function ($role) use ($allPermissions): void {
                    $role->syncPermissions($allPermissions);
                });
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            echo "[railway-bootstrap] Permission refresh completed.\n";
        } else {
            echo "[railway-bootstrap] Permission refresh not required.\n";
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[railway-bootstrap] Warning: permission refresh failed: '.$e->getMessage()."\n");
}
