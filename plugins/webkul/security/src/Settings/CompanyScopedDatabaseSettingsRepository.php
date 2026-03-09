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
    protected function currentCompanyId(): ?int
    {
        $user = Auth::user();

        return $user?->default_company_id;
    }

    public function getPropertiesInGroup(string $group): array
    {
        $companyId = $this->currentCompanyId();
        $builder = $this->getBuilder();

        $rows = $builder
            ->where('group', $group)
            ->where(function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId);
                if ($companyId !== null) {
                    $q->orWhereNull('company_id');
                }
            })
            ->orderByRaw('company_id IS NULL ASC') // company-specific first (non-null before null)
            ->get(['name', 'payload']);

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

        return $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name)
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
        $row = $this->getBuilder()
            ->where('group', $group)
            ->where('name', $name)
            ->where(function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId);
                if ($companyId !== null) {
                    $q->orWhereNull('company_id');
                }
            })
            ->orderByRaw('company_id IS NULL ASC')
            ->first(['payload']);

        if (! $row) {
            return null;
        }

        return $this->decode($row->payload);
    }

    public function createProperty(string $group, string $name, $payload): void
    {
        $table = $this->getBuilder()->getModel()->getTable();
        $connection = $this->getBuilder()->getConnection()->getName();

        DB::connection($connection)->table($table)->insert([
            'group'      => $group,
            'name'      => $name,
            'company_id' => $this->currentCompanyId(),
            'payload'   => $this->encode($payload),
            'locked'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updatePropertiesPayload(string $group, array $properties): void
    {
        $companyId = $this->currentCompanyId();
        $table = $this->getBuilder()->getModel()->getTable();
        $connection = $this->getBuilder()->getConnection()->getName();

        $now = now();
        $propertiesInBatch = collect($properties)->map(function ($payload, $name) use ($group, $companyId, $now) {
            return [
                'group'      => $group,
                'name'      => $name,
                'company_id' => $companyId,
                'payload'   => $this->encode($payload),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->toArray();

        DB::connection($connection)->table($table)->upsert(
            $propertiesInBatch,
            ['group', 'name', 'company_id'],
            ['payload', 'updated_at']
        );
    }

    public function deleteProperty(string $group, string $name): void
    {
        $companyId = $this->currentCompanyId();
        $builder = $this->getBuilder()->where('group', $group)->where('name', $name);
        if ($companyId !== null) {
            $builder->where('company_id', $companyId);
        } else {
            $builder->whereNull('company_id');
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

        return $this->getBuilder()
            ->where('group', $group)
            ->where('locked', true)
            ->where(function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId);
                if ($companyId !== null) {
                    $q->orWhereNull('company_id');
                }
            })
            ->orderByRaw('company_id IS NULL ASC')
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }

    protected function scopeWriteByCompany(string $group, array $properties, callable $callback): void
    {
        $companyId = $this->currentCompanyId();
        $builder = $this->getBuilder()
            ->where('group', $group)
            ->whereIn('name', $properties);
        if ($companyId !== null) {
            $builder->where('company_id', $companyId);
        } else {
            $builder->whereNull('company_id');
        }
        $callback($builder);
    }
}
