<?php

namespace Webkul\Security\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    protected string $column;

    /**
     * Create a new scope instance.
     *
     * @param  string  $column  The company foreign key column (e.g. 'company_id' or 'employee_company_id')
     */
    public function __construct(string $column = 'company_id')
    {
        $this->column = $column;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $companyId = $user->default_company_id;

        if ($companyId === null) {
            return;
        }

        $builder->where($model->getTable().'.'.$this->column, $companyId);
    }
}
