<?php

namespace Webkul\Security\Settings;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;

/**
 * Settings repository that scopes by company_id so each tenant has its own settings.
 * Reads: prefer row for current user's company, fallback to company_id null (global).
 * Writes: save with current user's company_id so tenants don't overwrite each other.
 */
class CompanyScopedDatabaseSettingsRepository extends DatabaseSettingsRepository
{
    protected function hasCompanyScopeColumn(): bool
    {
        $builder = $this->getBuilder();

        return $builder->getConnection()->getSchemaBuilder()->hasColumn(
            $builder->getModel()->getTable(),
            'company_id'
        );
    }

    protected function currentCompanyId(): ?int
    {
        $user = Auth::user();

        return $user?->default_company_id;
    }

    public function getPropertiesInGroup(string $group): array
    {
        $companyId = $this->currentCompanyId();
        $builder = $this->getBuilder();
        $hasCompanyScope = $this->hasCompanyScopeColumn();

        $rowsQuery = $builder->where('group', $group);

        if ($hasCompanyScope) {
            $rowsQuery
                ->where(function (Builder $q) use ($companyId) {
                    $q->where('company_id', $companyId);

                    if ($companyId !== null) {
                        $q->orWhereNull('company_id');
                    }
                })
                ->orderByRaw('company_id IS NULL ASC');
        }

        $rows = $rowsQuery->get(['name', 'payload']);

        return $rows
            ->unique('name') // first occurrence wins (company-specific over global)
            ->mapWithKeys(function (object $object) {
                return [$object->name => $this->decode($object->payload, true)];
            })
            ->toArray();
    }

    public function checkIfPropertyExists(string $group, string $name): bool
    {
        $companyId = $this->currentCompanyId();
        $hasCompanyScope = $this->hasCompanyScopeColumn();

        $query = $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name);

        if (! $hasCompanyScope) {
            return $query->exists();
        }

        return $query
            ->where(function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId);
                if ($companyId !== null) {
                    $q->orWhereNull('company_id');
                }
            })
            ->exists();
    }

    public function getPropertyPayload(string $group, string $name)
    {
        $companyId = $this->currentCompanyId();
        $hasCompanyScope = $this->hasCompanyScopeColumn();

        $rowQuery = $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name);

        if ($hasCompanyScope) {
            $rowQuery
                ->where(function (Builder $q) use ($companyId) {
                    $q->where('company_id', $companyId);

                    if ($companyId !== null) {
                        $q->orWhereNull('company_id');
                    }
                })
                ->orderByRaw('company_id IS NULL ASC');
        }

        $row = $rowQuery->first(['payload']);

        if (! $row) {
            return null;
        }

        return $this->decode($row->payload);
    }

    public function createProperty(string $group, string $name, $payload): void
    {
        $table = $this->getBuilder()->getModel()->getTable();
        $connection = $this->getBuilder()->getConnection()->getName();
        $hasCompanyScope = $this->hasCompanyScopeColumn();

        $data = [
            'group'      => $group,
            'name'      => $name,
            'payload'   => $this->encode($payload),
            'locked'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($hasCompanyScope) {
            $data['company_id'] = $this->currentCompanyId();
        }

        DB::connection($connection)->table($table)->insert($data);
    }

    public function updatePropertiesPayload(string $group, array $properties): void
    {
        $companyId = $this->currentCompanyId();
        $table = $this->getBuilder()->getModel()->getTable();
        $connection = $this->getBuilder()->getConnection()->getName();
        $hasCompanyScope = $this->hasCompanyScopeColumn();

        $now = now();
        $propertiesInBatch = collect($properties)->map(function ($payload, $name) use ($group, $companyId, $now, $hasCompanyScope) {
            $row = [
                'group'      => $group,
                'name'      => $name,
                'payload'   => $this->encode($payload),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasCompanyScope) {
                $row['company_id'] = $companyId;
            }

            return $row;
        })->values()->toArray();

        $uniqueBy = $hasCompanyScope
            ? ['group', 'name', 'company_id']
            : ['group', 'name'];

        DB::connection($connection)->table($table)->upsert(
            $propertiesInBatch,
            $uniqueBy,
            ['payload', 'updated_at']
        );
    }

    public function deleteProperty(string $group, string $name): void
    {
        $companyId = $this->currentCompanyId();
        $hasCompanyScope = $this->hasCompanyScopeColumn();
        $builder = $this->getBuilder()->where('group', $group)->where('name', $name);

        if ($hasCompanyScope) {
            if ($companyId !== null) {
                $builder->where('company_id', $companyId);
            } else {
                $builder->whereNull('company_id');
            }
        }

        $builder->delete();
    }

    public function lockProperties(string $group, array $properties): void
    {
        $this->scopeWriteByCompany($group, $properties, function (Builder $q) {
            $q->update(['locked' => true]);
        });
    }

    public function unlockProperties(string $group, array $properties): void
    {
        $this->scopeWriteByCompany($group, $properties, function (Builder $q) {
            $q->update(['locked' => false]);
        });
    }

    public function getLockedProperties(string $group): array
    {
        $companyId = $this->currentCompanyId();
        $hasCompanyScope = $this->hasCompanyScopeColumn();

        $query = $this->getBuilder()
            ->where('group', $group)
            ->where('locked', true);

        if ($hasCompanyScope) {
            $query
                ->where(function (Builder $q) use ($companyId) {
                    $q->where('company_id', $companyId);
                    if ($companyId !== null) {
                        $q->orWhereNull('company_id');
                    }
                })
                ->orderByRaw('company_id IS NULL ASC');
        }

        return $query
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }

    protected function scopeWriteByCompany(string $group, array $properties, callable $callback): void
    {
        $companyId = $this->currentCompanyId();
        $hasCompanyScope = $this->hasCompanyScopeColumn();
        $builder = $this->getBuilder()
            ->where('group', $group)
            ->whereIn('name', $properties);

        if ($hasCompanyScope) {
            if ($companyId !== null) {
                $builder->where('company_id', $companyId);
            } else {
                $builder->whereNull('company_id');
            }
        }

        $callback($builder);
    }
}
