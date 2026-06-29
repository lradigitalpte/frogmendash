<?php

namespace Webkul\Inventory\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Webkul\Security\Models\Scopes\CompanyScope;

/**
 * Inventory locations include shared virtual rows (Customers, Suppliers, transit)
 * where company_id is null. Those must remain visible to every tenant.
 */
class LocationCompanyScope extends CompanyScope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (static::isBypassed()) {
            return;
        }

        if (app()->runningInConsole()) {
            return;
        }

        $user = Auth::guard('web')->user();

        if ($user) {
            if (method_exists($user, 'isPlatformAdmin') && $user::isPlatformAdmin()) {
                return;
            }

            $companyId = $user->default_company_id ?? null;

            if ($companyId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(function (Builder $query) use ($model, $companyId) {
                $query->where($model->getTable().'.company_id', $companyId)
                    ->orWhereNull($model->getTable().'.company_id');
            });

            return;
        }

        if (Auth::guard('customer')->check()) {
            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
