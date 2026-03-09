<?php

namespace Webkul\PluginManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Webkul\Support\Models\Company;

class CompanyPlugin extends Model
{
    protected $table = 'company_plugins';

    protected $fillable = [
        'company_id',
        'plugin_name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if a plugin is enabled for the given company (or current user's company).
     * Accepts both config-style names (e.g. webkul.contacts) and DB-style names (e.g. contacts).
     */
    public static function isEnabledForCompany(?string $pluginName, ?int $companyId = null): bool
    {
        if (! $pluginName) {
            return false;
        }
        $companyId = $companyId ?? Auth::user()?->default_company_id;
        if (! $companyId) {
            return false;
        }

        $names = [$pluginName];
        if (str_contains($pluginName, '.')) {
            $names[] = \Illuminate\Support\Str::afterLast($pluginName, '.');
        }

        return self::where('company_id', $companyId)
            ->whereIn('plugin_name', $names)
            ->exists();
    }

    /**
     * Get plugin names enabled for the given company (or current user's company).
     *
     * @return array<int, string>
     */
    public static function enabledPluginNamesForCompany(?int $companyId = null): array
    {
        $companyId = $companyId ?? Auth::user()?->default_company_id;
        if (! $companyId) {
            return [];
        }

        return self::where('company_id', $companyId)->pluck('plugin_name')->toArray();
    }
}
