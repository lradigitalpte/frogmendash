<?php

namespace Webkul\Security\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts queries to the authenticated user's company.
 *
 * SECURITY: this scope now fails CLOSED. Previous behaviour returned an
 * UNSCOPED query whenever there was no authenticated user or the user had no
 * company — which silently exposed every tenant's data on unauthenticated web
 * requests and for users without a default company. The hardened rules are:
 *
 *   - Explicit system bypass (see {@see self::runWithout()})  -> unscoped
 *   - Console / queue / scheduler context (trusted server work) -> unscoped
 *   - Authenticated admin (web guard) platform admin            -> unscoped (operator)
 *   - Authenticated admin (web guard) with a company            -> scoped to it
 *   - Authenticated admin (web guard) with NO company           -> NO rows
 *   - Authenticated customer (customer guard)                   -> left to
 *       resource-level ownership scoping for now (tightened in a later phase)
 *   - Unauthenticated web request                               -> NO rows
 *
 * This is Phase 0 of TENANCY_REDESIGN_PLAN.md: stop the active leaks without
 * breaking the customer storefront, background jobs, or the platform admin.
 */
class CompanyScope implements Scope
{
    /**
     * When true, the scope is skipped entirely. Toggled only by trusted
     * server-side code that intentionally needs cross-company access.
     */
    protected static bool $bypassed = false;

    protected string $column;

    /**
     * @param  string  $column  The company foreign key column (e.g. 'company_id' or 'employee_company_id')
     */
    public function __construct(string $column = 'company_id')
    {
        $this->column = $column;
    }

    /**
     * Run a callback with company scoping disabled, then restore the previous
     * state. Use sparingly and only in trusted contexts (e.g. an intentional
     * cross-tenant report or a public, separately-gated endpoint).
     *
     * @template TReturn
     * @param  callable():TReturn  $callback
     * @return TReturn
     */
    public static function runWithout(callable $callback)
    {
        $previous = static::$bypassed;
        static::$bypassed = true;

        try {
            return $callback();
        } finally {
            static::$bypassed = $previous;
        }
    }

    public static function isBypassed(): bool
    {
        return static::$bypassed;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // 1. Explicit, intentional bypass.
        if (static::$bypassed) {
            return;
        }

        // 2. Trusted server-side context (artisan commands, queue workers,
        //    scheduler, tinker, tests). These legitimately operate across
        //    companies; per-tenant job context is a later phase.
        if (app()->runningInConsole()) {
            return;
        }

        // 3. Admin / staff user (default 'web' guard).
        $user = Auth::guard('web')->user();

        if ($user) {
            // Platform operator sees everything (avoids locking out the installer).
            if (method_exists($user, 'isPlatformAdmin') && $user::isPlatformAdmin()) {
                return;
            }

            $companyId = $user->default_company_id ?? null;

            // Authenticated but no company -> fail closed.
            if ($companyId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($model->getTable().'.'.$this->column, $companyId);

            return;
        }

        // 4. Authenticated customer (storefront, separate guard). Their data is
        //    constrained by resource-level ownership checks today; do not break
        //    that here. Tightened to true company scoping in a later phase.
        if (Auth::guard('customer')->check()) {
            return;
        }

        // 5. Unauthenticated web request -> fail closed.
        $builder->whereRaw('1 = 0');
    }
}
