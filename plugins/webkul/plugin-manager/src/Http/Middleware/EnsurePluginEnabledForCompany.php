<?php

namespace Webkul\PluginManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\PluginManager\Support\PluginNavigationHelper;

class EnsurePluginEnabledForCompany
{
    /**
     * Block access to Filament resources/pages that belong to a plugin not enabled for the current company.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->default_company_id) {
            return $next($request);
        }

        // Only check on initial page load (GET); Livewire and other POSTs are already on an allowed page
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $route = $request->route();
        if (! $route) {
            return $next($request);
        }

        $name = $route->getName();
        if (! $name || ! str_starts_with($name, 'filament.admin.')) {
            return $next($request);
        }

        // Resolve resource or page class from route name (e.g. filament.admin.resources.orders.index => OrderResource)
        $resourceClass = $this->getResourceOrPageClassFromRoute($route);
        if ($resourceClass && ! PluginNavigationHelper::canAccessByClass($resourceClass)) {
            // Special case: any page/resource under the Projects namespace should send the user
            // to the Plugins page instead of showing a hard 403 when Projects is disabled.
            if (str_starts_with($resourceClass, 'Webkul\\Project\\')) {
                return redirect('/admin/plugins?tab=apps');
            }

            abort(403, __('This module is not enabled for your company. Enable it in Settings → Plugins.'));
        }

        return $next($request);
    }

    protected function getResourceOrPageClassFromRoute($route): ?string
    {
        $name = $route->getName();
        if (! $name) {
            return null;
        }

        try {
            $panel = \Filament\Facades\Filament::getPanel('admin');
        } catch (\Throwable $e) {
            return null;
        }

        // filament.admin.resources.orders.index => look up resource for 'orders'
        if (preg_match('/^filament\.admin\.resources\.([a-z0-9_-]+)\./', $name, $m)) {
            $slug = $m[1];
            foreach ($panel->getResources() as $resource) {
                if ($resource::getSlug() === $slug) {
                    return $resource;
                }
            }
        }

        // filament.admin.pages. => get page class
        if (preg_match('/^filament\.admin\.pages\.([a-z0-9_-]+)/', $name, $m)) {
            $slug = $m[1];
            foreach ($panel->getPages() as $page) {
                if ($page::getSlug() === $slug) {
                    return $page;
                }
            }
        }

        // Clusters: filament.admin.clusters.xyz...
        if (preg_match('/^filament\.admin\.clusters\.([a-z0-9_-]+)/', $name, $m)) {
            $clusterSlug = $m[1];
            foreach ($panel->getClusters() as $cluster) {
                if ($cluster::getSlug() === $clusterSlug) {
                    return $cluster;
                }
            }
        }

        return null;
    }
}
