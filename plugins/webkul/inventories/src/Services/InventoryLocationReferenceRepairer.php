<?php

namespace Webkul\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\TenantProvisioner;
use Webkul\Support\Models\Company;

class InventoryLocationReferenceRepairer
{
    /**
     * @var array<int, array<int, string>>
     */
    private array $locationFullNames = [];

    /**
     * @return array{issues: list<array<string, mixed>>, repaired: int, unrepairable: int}
     */
    public function run(?int $companyId = null, bool $dryRun = true, bool $provisionMissing = false, bool $includeDone = false): array
    {
        if (! Schema::hasTable('inventories_locations')) {
            return ['issues' => [], 'repaired' => 0, 'unrepairable' => 0];
        }

        $this->warmLocationCache();

        $companies = Company::withoutGlobalScopes()
            ->when($companyId, fn ($query) => $query->where('id', $companyId))
            ->orderBy('id')
            ->get();

        $issues = [];
        $repaired = 0;
        $unrepairable = 0;

        foreach ($companies as $company) {
            if ($provisionMissing && ! $dryRun && $this->companyHasNoLocations($company->id)) {
                TenantProvisioner::provisionInventoryFor($company);
                $this->warmLocationCache();
            }

            foreach ($this->tableDefinitions() as $definition) {
                if (! Schema::hasTable($definition['table'])) {
                    continue;
                }

                $rows = DB::table($definition['table'])
                    ->where('company_id', $company->id)
                    ->when(
                        ($definition['state_column'] ?? null) && ! $includeDone,
                        fn ($query) => $query->whereNotIn(
                            $definition['state_column'],
                            $definition['skip_states'] ?? []
                        )
                    )
                    ->when(
                        ($definition['open_operation'] ?? false) && ! $includeDone,
                        function ($query) use ($definition) {
                            $table = $definition['table'];
                            $operationColumn = $definition['operation_column'] ?? 'operation_id';

                            $query->whereExists(function ($sub) use ($table, $operationColumn) {
                                $sub->select(DB::raw(1))
                                    ->from('inventories_operations')
                                    ->whereColumn('inventories_operations.id', "{$table}.{$operationColumn}")
                                    ->whereNotIn('inventories_operations.state', ['done', 'canceled']);
                            });
                        }
                    )
                    ->get();

                foreach ($rows as $row) {
                    foreach ($definition['location_columns'] as $locationColumn) {
                        $locationId = $row->{$locationColumn} ?? null;

                        if ($this->isValidReference($locationId, (int) $company->id)) {
                            continue;
                        }

                        $issue = [
                            'company_id'      => $company->id,
                            'company_name'    => $company->name,
                            'table'           => $definition['table'],
                            'record_id'       => $row->id,
                            'record_label'    => $this->recordLabel($definition, $row),
                            'location_column' => $locationColumn,
                            'wrong_location'  => $this->describeLocation($locationId),
                            'status'          => 'unrepairable',
                        ];

                        $resolvedId = $this->resolveLocationId($locationId, (int) $company->id);

                        if ($resolvedId === null && $provisionMissing && ! $dryRun) {
                            TenantProvisioner::provisionInventoryFor($company);
                            $this->warmLocationCache();
                            $resolvedId = $this->resolveLocationId($locationId, (int) $company->id);
                        }

                        if ($resolvedId !== null && (int) $resolvedId !== (int) $locationId) {
                            $issue['resolved_location'] = $this->describeLocation($resolvedId);
                            $issue['status'] = $dryRun ? 'would_repair' : 'repaired';

                            if (! $dryRun) {
                                DB::table($definition['table'])
                                    ->where('id', $row->id)
                                    ->update([$locationColumn => $resolvedId]);
                            }

                            $repaired++;
                        } else {
                            $unrepairable++;
                        }

                        $issues[] = $issue;
                    }
                }
            }
        }

        return compact('issues', 'repaired', 'unrepairable');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function audit(?int $companyId = null): array
    {
        return $this->run($companyId, dryRun: true, provisionMissing: false)['issues'];
    }

    private function companyHasNoLocations(int $companyId): bool
    {
        return ! DB::table('inventories_locations')
            ->where('company_id', $companyId)
            ->exists();
    }

    private function warmLocationCache(): void
    {
        $this->locationFullNames = [];

        $locations = DB::table('inventories_locations')
            ->select(['id', 'company_id', 'full_name', 'name', 'type'])
            ->get();

        foreach ($locations as $location) {
            $this->locationFullNames[(int) $location->id] = [
                'company_id' => $location->company_id !== null ? (int) $location->company_id : null,
                'full_name'  => (string) $location->full_name,
                'name'       => (string) $location->name,
                'type'       => (string) $location->type,
            ];
        }
    }

    private function isValidReference(?int $locationId, int $companyId): bool
    {
        if ($locationId === null) {
            return true;
        }

        $location = $this->locationFullNames[$locationId] ?? null;

        if ($location === null) {
            return false;
        }

        if ($location['company_id'] === null) {
            return true;
        }

        return $location['company_id'] === $companyId;
    }

    private function resolveLocationId(?int $wrongLocationId, int $targetCompanyId): ?int
    {
        if ($wrongLocationId === null) {
            return null;
        }

        $wrong = $this->locationFullNames[$wrongLocationId] ?? null;

        if ($wrong === null) {
            return null;
        }

        if ($wrong['company_id'] === null || $wrong['company_id'] === $targetCompanyId) {
            return $wrongLocationId;
        }

        $byFullName = $this->findLocationId($targetCompanyId, $wrong['full_name']);

        if ($byFullName !== null) {
            return $byFullName;
        }

        return $this->findLocationIdByNameAndType($targetCompanyId, $wrong['name'], $wrong['type']);
    }

    private function findLocationId(int $companyId, string $fullName): ?int
    {
        foreach ($this->locationFullNames as $id => $location) {
            if ($location['company_id'] === $companyId && $location['full_name'] === $fullName) {
                return $id;
            }
        }

        return null;
    }

    private function findLocationIdByNameAndType(int $companyId, string $name, string $type): ?int
    {
        foreach ($this->locationFullNames as $id => $location) {
            if ($location['company_id'] === $companyId && $location['name'] === $name && $location['type'] === $type) {
                return $id;
            }
        }

        return null;
    }

    private function describeLocation(?int $locationId): string
    {
        if ($locationId === null) {
            return 'null';
        }

        $location = $this->locationFullNames[$locationId] ?? null;

        if ($location === null) {
            return "#{$locationId} (missing)";
        }

        $company = $location['company_id'] === null ? 'shared' : "company {$location['company_id']}";

        return "#{$locationId} {$location['full_name']} ({$company})";
    }

    /**
     * @param  object  $row
     */
    private function recordLabel(array $definition, object $row): string
    {
        if (isset($definition['name_column'], $row->{$definition['name_column']})) {
            return (string) $row->{$definition['name_column']};
        }

        return "#{$row->id}";
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tableDefinitions(): array
    {
        return [
            [
                'table'             => 'inventories_operation_types',
                'name_column'       => 'name',
                'location_columns'  => ['source_location_id', 'destination_location_id'],
            ],
            [
                'table'             => 'inventories_rules',
                'name_column'       => 'name',
                'location_columns'  => ['source_location_id', 'destination_location_id'],
            ],
            [
                'table'             => 'inventories_warehouses',
                'name_column'       => 'name',
                'location_columns'  => [
                    'view_location_id',
                    'lot_stock_location_id',
                    'input_stock_location_id',
                    'qc_stock_location_id',
                    'output_stock_location_id',
                    'pack_stock_location_id',
                ],
            ],
            [
                'table'             => 'inventories_operations',
                'name_column'       => 'name',
                'location_columns'  => ['source_location_id', 'destination_location_id'],
                'state_column'      => 'state',
                'skip_states'       => ['done', 'canceled'],
            ],
            [
                'table'             => 'inventories_moves',
                'name_column'       => 'name',
                'location_columns'  => ['source_location_id', 'destination_location_id', 'final_location_id'],
                'open_operation'    => true,
            ],
            [
                'table'             => 'inventories_move_lines',
                'location_columns'  => ['source_location_id', 'destination_location_id'],
                'open_operation'    => true,
            ],
            [
                'table'             => 'inventories_scraps',
                'name_column'       => 'name',
                'location_columns'  => ['source_location_id', 'destination_location_id'],
                'state_column'      => 'state',
                'skip_states'       => ['done', 'canceled'],
            ],
            [
                'table'             => 'inventories_product_quantities',
                'location_columns'  => ['location_id'],
            ],
            [
                'table'             => 'inventories_lots',
                'name_column'       => 'name',
                'location_columns'  => ['location_id'],
            ],
            [
                'table'             => 'inventories_order_points',
                'name_column'       => 'name',
                'location_columns'  => ['location_id'],
            ],
        ];
    }

    public function issueSummary(Collection $issues): array
    {
        return [
            'total'         => $issues->count(),
            'by_company'    => $issues->groupBy('company_name')->map->count()->all(),
            'by_table'      => $issues->groupBy('table')->map->count()->all(),
            'unrepairable'  => $issues->where('status', 'unrepairable')->count(),
            'would_repair'  => $issues->where('status', 'would_repair')->count(),
            'repaired'      => $issues->where('status', 'repaired')->count(),
        ];
    }
}
