<?php

namespace Webkul\PluginManager\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webkul\PluginManager\Models\CompanyPlugin;

class PluginNavigationHelper
{
    /** Plugin names that are always shown in navigation (Settings, Plugins, core). */
    protected static array $alwaysShowPlugins = [
        'webkul.support',
        'webkul.plugin-manager',
        'webkul.security',
    ];

    /** Navigation labels/keys that should never be hidden by plugin filtering. */
    protected static array $alwaysShowGroups = [
        'dashboard',
        'admin.navigation.dashboard',
        'home',
        'admin.navigation.home',
    ];

    /**
     * Filter navigation groups so only groups for enabled plugins (and core) are shown.
     *
     * @param  array<\Filament\Navigation\NavigationGroup>|Collection  $navigation
     * @return Collection<int, \Filament\Navigation\NavigationGroup>
     */
    public static function filterNavigationForCompany($navigation): Collection
    {
        $navigation = collect($navigation);

        if (! auth()->check() || ! auth()->user()->default_company_id) {
            return $navigation;
        }

        return $navigation->filter(function ($group) {
            $label = $group->getLabel();
            if (self::isAlwaysShowGroup($label)) {
                return true;
            }

            $pluginName = self::pluginNameForNavigationGroup($label);
            if ($pluginName === null) {
                return true;
            }
            if (in_array($pluginName, self::$alwaysShowPlugins, true)) {
                return true;
            }
            return self::isPluginEnabledForCurrentCompany($pluginName);
        })->values();
    }
    /**
     * Get plugin name from a class namespace (e.g. Webkul\Sales\Filament\... => webkul.sales,
     * Webkul\RovInspection\... => webkul.rov-inspection).
     */
    public static function pluginNameFromClass(string $class): ?string
    {
        if (! str_starts_with($class, 'Webkul\\')) {
            return null;
        }
        $parts = explode('\\', $class);
        if (! isset($parts[1])) {
            return null;
        }
        $segment = $parts[1];
        // CamelCase to kebab-case so RovInspection => rov-inspection (matches plugin id in DB)
        $kebab = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $segment));

        $base = 'webkul.'.$kebab;

        return self::resolveConfiguredPluginName($base);
    }

    /**
     * Resolve a plugin name to a canonical configured key when possible.
     */
    protected static function resolveConfiguredPluginName(string $pluginName): string
    {
        $configured = array_keys(config('plugin-navigation-groups', []));
        if (in_array($pluginName, $configured, true)) {
            return $pluginName;
        }

        $tail = Str::afterLast($pluginName, '.');
        $prefix = Str::beforeLast($pluginName, '.');

        if ($tail === $pluginName || $tail === '') {
            return $pluginName;
        }

        $candidates = [
            $prefix.'.'.Str::plural($tail),
            $prefix.'.'.Str::singular($tail),
            $prefix.'.'.Str::kebab(Str::plural(Str::replace('-', ' ', $tail))),
            $prefix.'.'.Str::kebab(Str::singular(Str::replace('-', ' ', $tail))),
        ];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $configured, true)) {
                return $candidate;
            }
        }

        return $pluginName;
    }

    /**
     * Get plugin name that "owns" the given navigation group (label or key).
     */
    public static function pluginNameForNavigationGroup(?string $group): ?string
    {
        if (! $group) {
            return null;
        }

        $normalizedGroup = self::normalizeGroupLabel($group);
        $config = config('plugin-navigation-groups', []);

        foreach ($config as $pluginName => $groups) {
            foreach ((array) $groups as $mappedGroup) {
                if (! is_string($mappedGroup) || $mappedGroup === '') {
                    continue;
                }

                if (
                    strcasecmp($group, $mappedGroup) === 0
                    || self::normalizeGroupLabel($mappedGroup) === $normalizedGroup
                ) {
                    return $pluginName;
                }
            }
        }

        return null;
    }

    protected static function isAlwaysShowGroup(?string $group): bool
    {
        if (! $group) {
            return true;
        }

        $normalized = self::normalizeGroupLabel($group);

        foreach (self::$alwaysShowGroups as $allowedGroup) {
            if ($normalized === self::normalizeGroupLabel($allowedGroup)) {
                return true;
            }
        }

        return false;
    }

    protected static function normalizeGroupLabel(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['_', '-', '.'], ' ')
            ->squish()
            ->value();
    }

    /**
     * Whether the current user's company has the given plugin enabled (for nav/access).
     */
    public static function isPluginEnabledForCurrentCompany(string $pluginName): bool
    {
        return CompanyPlugin::isEnabledForCompany($pluginName);
    }

    /**
     * Whether the current user can access a resource/page (by class or nav group).
     */
    public static function canAccessByClass(string $class): bool
    {
        $pluginName = self::pluginNameFromClass($class);
        if (! $pluginName) {
            return true;
        }
        // Core/plugin-manager always allowed (plugin-manager from config, pluginmanager from class namespace)
        if (in_array($pluginName, ['webkul.support', 'webkul.plugin-manager', 'webkul.pluginmanager', 'webkul.security'], true)) {
            return true;
        }

        return self::isPluginEnabledForCurrentCompany($pluginName);
    }

    /**
     * Whether the current user can access a resource/page by its navigation group.
     */
    public static function canAccessByNavigationGroup(?string $group): bool
    {
        $pluginName = self::pluginNameForNavigationGroup($group);
        if (! $pluginName) {
            return true;
        }

        return self::isPluginEnabledForCurrentCompany($pluginName);
    }
}
