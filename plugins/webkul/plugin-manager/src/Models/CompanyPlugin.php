<?php

namespace Webkul\PluginManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

        $normalized = static::normalizePluginNameVariants($pluginName);
        $names = array_values(array_unique(array_filter($normalized)));

        return self::where('company_id', $companyId)
            ->whereIn('plugin_name', $names)
            ->exists();
    }

    /**
     * Build accepted plugin-name variants to support legacy or mixed naming formats.
     *
     * @return array<int, string>
     */
    protected static function normalizePluginNameVariants(string $pluginName): array
    {
        $name = trim($pluginName);

        $variants = [
            $name,
            Str::lower($name),
            Str::replace('/', '.', Str::lower($name)),
            Str::replace('-', '.', Str::lower($name)),
        ];

        $dotTail = Str::afterLast($name, '.');
        $slashTail = Str::afterLast($name, '/');

        foreach ([$dotTail, $slashTail] as $tail) {
            if ($tail === $name || $tail === '') {
                continue;
            }

            $variants[] = $tail;
            $variants[] = Str::lower($tail);
            $variants[] = Str::kebab($tail);
            $variants[] = Str::snake($tail, '-');
            $variants[] = Str::singular(Str::kebab($tail));
        }

        return $variants;
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
