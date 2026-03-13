<?php

namespace Webkul\Account;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Provision baseline module data for new tenants by cloning from a source company.
 */
class TenantProvisioner
{
    public static function provisionAll(Company $company): void
    {
        $sourceCompanyId = self::resolveSourceCompanyId($company->id);

        self::provisionJournals($company);
        self::provisionPaymentTerms($company, $sourceCompanyId);
        self::provisionTaxes($company, $sourceCompanyId);
        self::provisionSimpleCompanyTables($company, $sourceCompanyId);
        self::provisionWarranty($company, $sourceCompanyId);
        self::provisionInventory($company, $sourceCompanyId);
    }

    /**
     * Create the standard journals for $company if they do not already exist.
     */
    public static function provisionJournals(Company $company): void
    {
        // Resolve accounts by code so we are not tied to hardcoded IDs
        $salesAccount    = Account::where('code', '400000')->first();  // Product Sales
        $expenseAccount  = Account::where('code', '600000')->first();  // Expenses
        $cashAccount     = Account::where('code', '101501')->first();  // Cash
        $suspenseAccount = Account::where('code', '101402')->first();  // Bank Suspense

        $currency = $company->currency ?? Currency::where('name', 'USD')->first() ?? Currency::first();

        $templates = [
            [
                'code'                     => 'INV',
                'name'                     => 'Customer Invoices',
                'type'                     => 'sale',
                'default_account_id'       => $salesAccount?->id,
                'suspense_account_id'      => null,
                'sort'                     => 5,
                'show_on_dashboard'        => true,
                'refund_order'             => true,
                'payment_order'            => false,
            ],
            [
                'code'                     => 'BILL',
                'name'                     => 'Vendor Bills',
                'type'                     => 'purchase',
                'default_account_id'       => $expenseAccount?->id,
                'suspense_account_id'      => null,
                'sort'                     => 6,
                'show_on_dashboard'        => true,
                'refund_order'             => true,
                'payment_order'            => false,
            ],
            [
                'code'                     => 'MISC',
                'name'                     => 'Miscellaneous Operations',
                'type'                     => 'general',
                'default_account_id'       => null,
                'suspense_account_id'      => null,
                'sort'                     => 9,
                'show_on_dashboard'        => false,
                'refund_order'             => false,
                'payment_order'            => false,
            ],
            [
                'code'                     => 'EXCH',
                'name'                     => 'Exchange Difference',
                'type'                     => 'general',
                'default_account_id'       => null,
                'suspense_account_id'      => null,
                'sort'                     => 10,
                'show_on_dashboard'        => false,
                'refund_order'             => false,
                'payment_order'            => false,
            ],
            [
                'code'                     => 'BANK',
                'name'                     => 'Bank Transactions',
                'type'                     => 'bank',
                'default_account_id'       => $cashAccount?->id,
                'suspense_account_id'      => $suspenseAccount?->id,
                'sort'                     => null,
                'show_on_dashboard'        => true,
                'refund_order'             => false,
                'payment_order'            => false,
            ],
            [
                'code'                     => 'CASH',
                'name'                     => 'Cash Transactions',
                'type'                     => 'cash',
                'default_account_id'       => null,
                'suspense_account_id'      => null,
                'sort'                     => null,
                'show_on_dashboard'        => true,
                'refund_order'             => false,
                'payment_order'            => false,
            ],
        ];

        foreach ($templates as $tpl) {
            $existingJournal = Journal::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where(function ($query) use ($tpl) {
                    $query->where('code', $tpl['code']);

                    if (in_array($tpl['type'], ['sale', 'purchase', 'bank', 'cash'], true)) {
                        $query->orWhere('type', $tpl['type']);
                    }
                })
                ->orderBy('id')
                ->first();

            if ($existingJournal) {
                $existingJournal->update([
                    'name' => $existingJournal->name ?: $tpl['name'],
                    'code' => $existingJournal->code ?: $tpl['code'],
                    'show_on_dashboard' => $tpl['show_on_dashboard'],
                    'refund_order' => $tpl['refund_order'],
                    'payment_order' => $tpl['payment_order'],
                    'default_account_id' => $existingJournal->default_account_id ?: $tpl['default_account_id'],
                    'suspense_account_id' => $existingJournal->suspense_account_id ?: $tpl['suspense_account_id'],
                    'currency_id' => $existingJournal->currency_id ?: $currency?->id,
                    'creator_id' => $existingJournal->creator_id ?: $company->creator_id,
                ]);

                continue;
            }

            Journal::withoutGlobalScopes()->create([
                'company_id'               => $company->id,
                'currency_id'              => $currency?->id,
                'creator_id'               => $company->creator_id,
                'code'                     => $tpl['code'],
                'name'                     => $tpl['name'],
                'type'                     => $tpl['type'],
                'default_account_id'       => $tpl['default_account_id'],
                'suspense_account_id'      => $tpl['suspense_account_id'],
                'sort'                     => $tpl['sort'],
                'show_on_dashboard'        => $tpl['show_on_dashboard'],
                'refund_order'             => $tpl['refund_order'],
                'payment_order'            => $tpl['payment_order'],
                'auto_check_on_post'       => true,
                'restrict_mode_hash_table' => false,
                'invoice_reference_type'   => 'invoice',
                'invoice_reference_model'  => 'aureus',
                'bank_statements_source'   => null,
                'profit_account_id'        => null,
                'loss_account_id'          => null,
                'bank_account_id'          => null,
                'access_token'             => null,
                'order_override_regex'     => null,
                'color'                    => 0,
            ]);
        }
    }

    private static function provisionPaymentTerms(Company $company, ?int $sourceCompanyId): void
    {
        if (! $sourceCompanyId || ! self::hasTable('accounts_payment_terms') || ! self::hasTable('accounts_payment_due_terms')) {
            return;
        }

        $termIdMap = [];

        $sourceTerms = DB::table('accounts_payment_terms')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceTerms as $sourceTerm) {
            $existingId = DB::table('accounts_payment_terms')
                ->where('company_id', $company->id)
                ->where('name', $sourceTerm->name)
                ->value('id');

            if ($existingId) {
                $termIdMap[$sourceTerm->id] = $existingId;

                continue;
            }

            $record = (array) $sourceTerm;
            unset($record['id']);
            $record['company_id'] = $company->id;
            $record['creator_id'] = $company->creator_id;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('accounts_payment_terms')->insertGetId($record);
            $termIdMap[$sourceTerm->id] = $newId;
        }

        $sourceDueTerms = DB::table('accounts_payment_due_terms')
            ->whereIn('payment_id', array_keys($termIdMap))
            ->orderBy('id')
            ->get();

        foreach ($sourceDueTerms as $dueTerm) {
            $newPaymentId = $termIdMap[$dueTerm->payment_id] ?? null;

            if (! $newPaymentId) {
                continue;
            }

            $alreadyExists = DB::table('accounts_payment_due_terms')
                ->where('payment_id', $newPaymentId)
                ->where('nb_days', $dueTerm->nb_days)
                ->where('delay_type', $dueTerm->delay_type)
                ->where('value', $dueTerm->value)
                ->where('value_amount', $dueTerm->value_amount)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            $record = (array) $dueTerm;
            unset($record['id']);
            $record['payment_id'] = $newPaymentId;
            $record['creator_id'] = $company->creator_id;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            DB::table('accounts_payment_due_terms')->insert($record);
        }
    }

    private static function provisionTaxes(Company $company, ?int $sourceCompanyId): void
    {
        if (! $sourceCompanyId
            || ! self::hasTable('accounts_tax_groups')
            || ! self::hasTable('accounts_taxes')
            || ! self::hasTable('accounts_tax_partition_lines')) {
            return;
        }

        $taxGroupMap = [];

        $sourceTaxGroups = DB::table('accounts_tax_groups')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceTaxGroups as $sourceGroup) {
            $existingId = DB::table('accounts_tax_groups')
                ->where('company_id', $company->id)
                ->where('name', $sourceGroup->name)
                ->value('id');

            if ($existingId) {
                $taxGroupMap[$sourceGroup->id] = $existingId;

                continue;
            }

            $record = (array) $sourceGroup;
            unset($record['id']);
            $record['company_id'] = $company->id;
            $record['creator_id'] = $company->creator_id;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('accounts_tax_groups')->insertGetId($record);
            $taxGroupMap[$sourceGroup->id] = $newId;
        }

        $taxMap = [];

        $sourceTaxes = DB::table('accounts_taxes')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceTaxes as $sourceTax) {
            $existingId = DB::table('accounts_taxes')
                ->where('company_id', $company->id)
                ->where('type_tax_use', $sourceTax->type_tax_use)
                ->where('name', $sourceTax->name)
                ->where('amount', $sourceTax->amount)
                ->value('id');

            if ($existingId) {
                $taxMap[$sourceTax->id] = $existingId;

                continue;
            }

            $record = (array) $sourceTax;
            unset($record['id']);
            $record['company_id'] = $company->id;
            $record['creator_id'] = $company->creator_id;
            $record['tax_group_id'] = $taxGroupMap[$sourceTax->tax_group_id] ?? null;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('accounts_taxes')->insertGetId($record);
            $taxMap[$sourceTax->id] = $newId;
        }

        $sourcePartitions = DB::table('accounts_tax_partition_lines')
            ->where('company_id', $sourceCompanyId)
            ->whereIn('tax_id', array_keys($taxMap))
            ->orderBy('id')
            ->get();

        foreach ($sourcePartitions as $sourcePartition) {
            $newTaxId = $taxMap[$sourcePartition->tax_id] ?? null;

            if (! $newTaxId) {
                continue;
            }

            $exists = DB::table('accounts_tax_partition_lines')
                ->where('company_id', $company->id)
                ->where('tax_id', $newTaxId)
                ->where('document_type', $sourcePartition->document_type)
                ->where('repartition_type', $sourcePartition->repartition_type)
                ->where('sort', $sourcePartition->sort)
                ->exists();

            if ($exists) {
                continue;
            }

            $record = (array) $sourcePartition;
            unset($record['id']);
            $record['company_id'] = $company->id;
            $record['creator_id'] = $company->creator_id;
            $record['tax_id'] = $newTaxId;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            DB::table('accounts_tax_partition_lines')->insert($record);
        }
    }

    private static function provisionSimpleCompanyTables(Company $company, ?int $sourceCompanyId): void
    {
        if (! $sourceCompanyId) {
            return;
        }

        self::cloneSimpleByName('employees_work_locations', $sourceCompanyId, $company->id, $company->creator_id);
        self::cloneSimpleByName('employees_departments', $sourceCompanyId, $company->id, $company->creator_id, ['name'], ['manager_id']);
        self::cloneSimpleByName('sales_teams', $sourceCompanyId, $company->id, $company->creator_id, ['name'], [], ['user_id' => $company->creator_id]);
        self::cloneSimpleByName('time_off_leave_types', $sourceCompanyId, $company->id, $company->creator_id, ['name']);
    }

    private static function provisionWarranty(Company $company, ?int $sourceCompanyId): void
    {
        if (! $sourceCompanyId) {
            return;
        }

        self::cloneSimpleByName('warranty_policies', $sourceCompanyId, $company->id, $company->creator_id, ['name']);
    }

    private static function provisionInventory(Company $company, ?int $sourceCompanyId): void
    {
        if (! $sourceCompanyId
            || ! self::hasTable('inventories_locations')
            || ! self::hasTable('inventories_routes')
            || ! self::hasTable('inventories_operation_types')
            || ! self::hasTable('inventories_rules')
            || ! self::hasTable('inventories_warehouses')) {
            return;
        }

        $locationMap = self::cloneInventoryLocations($sourceCompanyId, $company->id, $company->creator_id);
        $routeMap = self::cloneInventoryRoutes($sourceCompanyId, $company->id, $company->creator_id);
        $operationTypeMap = self::cloneInventoryOperationTypes($sourceCompanyId, $company->id, $company->creator_id, $locationMap);
        $ruleMap = self::cloneInventoryRules($sourceCompanyId, $company->id, $company->creator_id, $locationMap, $routeMap, $operationTypeMap);
        self::cloneInventoryWarehouses($sourceCompanyId, $company->id, $company->creator_id, $locationMap, $routeMap, $operationTypeMap, $ruleMap);
    }

    private static function cloneInventoryLocations(int $sourceCompanyId, int $targetCompanyId, ?int $creatorId): array
    {
        $map = [];

        $sourceRows = DB::table('inventories_locations')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceRows as $row) {
            $existingId = DB::table('inventories_locations')
                ->where('company_id', $targetCompanyId)
                ->where('full_name', $row->full_name)
                ->value('id');

            if ($existingId) {
                $map[$row->id] = $existingId;

                continue;
            }

            $record = (array) $row;
            unset($record['id']);
            $record['company_id'] = $targetCompanyId;
            $record['creator_id'] = $creatorId;
            $record['warehouse_id'] = null;

            if (($row->parent_id !== null) && isset($map[$row->parent_id])) {
                $record['parent_id'] = $map[$row->parent_id];
            }

            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('inventories_locations')->insertGetId($record);
            $map[$row->id] = $newId;
        }

        foreach ($map as $oldId => $newId) {
            $newParentId = DB::table('inventories_locations')->where('id', $newId)->value('parent_id');
            $newParentPath = $newParentId
                ? DB::table('inventories_locations')->where('id', $newParentId)->value('parent_path').$newId.'/'
                : $newId.'/';

            DB::table('inventories_locations')->where('id', $newId)->update(['parent_path' => $newParentPath]);
        }

        return $map;
    }

    private static function cloneInventoryRoutes(int $sourceCompanyId, int $targetCompanyId, ?int $creatorId): array
    {
        $map = [];

        $sourceRows = DB::table('inventories_routes')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceRows as $row) {
            $existingId = DB::table('inventories_routes')
                ->where('company_id', $targetCompanyId)
                ->where('name', $row->name)
                ->value('id');

            if ($existingId) {
                $map[$row->id] = $existingId;

                continue;
            }

            $record = (array) $row;
            unset($record['id']);
            $record['company_id'] = $targetCompanyId;
            $record['creator_id'] = $creatorId;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('inventories_routes')->insertGetId($record);
            $map[$row->id] = $newId;
        }

        return $map;
    }

    private static function cloneInventoryOperationTypes(int $sourceCompanyId, int $targetCompanyId, ?int $creatorId, array $locationMap): array
    {
        $map = [];

        $sourceRows = DB::table('inventories_operation_types')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceRows as $row) {
            $existingId = DB::table('inventories_operation_types')
                ->where('company_id', $targetCompanyId)
                ->where('name', $row->name)
                ->value('id');

            if ($existingId) {
                $map[$row->id] = $existingId;

                continue;
            }

            $record = (array) $row;
            unset($record['id']);
            $record['company_id'] = $targetCompanyId;
            $record['creator_id'] = $creatorId;
            $record['warehouse_id'] = null;
            $record['source_location_id'] = $locationMap[$row->source_location_id] ?? $row->source_location_id;
            $record['destination_location_id'] = $locationMap[$row->destination_location_id] ?? $row->destination_location_id;
            $record['return_operation_type_id'] = null;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('inventories_operation_types')->insertGetId($record);
            $map[$row->id] = $newId;
        }

        foreach ($sourceRows as $row) {
            $newId = $map[$row->id] ?? null;

            if (! $newId || ! $row->return_operation_type_id) {
                continue;
            }

            DB::table('inventories_operation_types')->where('id', $newId)->update([
                'return_operation_type_id' => $map[$row->return_operation_type_id] ?? null,
            ]);
        }

        return $map;
    }

    private static function cloneInventoryRules(
        int $sourceCompanyId,
        int $targetCompanyId,
        ?int $creatorId,
        array $locationMap,
        array $routeMap,
        array $operationTypeMap
    ): array {
        $map = [];

        $sourceRows = DB::table('inventories_rules')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceRows as $row) {
            $existingId = DB::table('inventories_rules')
                ->where('company_id', $targetCompanyId)
                ->where('name', $row->name)
                ->value('id');

            if ($existingId) {
                $map[$row->id] = $existingId;

                continue;
            }

            $record = (array) $row;
            unset($record['id']);
            $record['company_id'] = $targetCompanyId;
            $record['creator_id'] = $creatorId;
            $record['warehouse_id'] = null;
            $record['source_location_id'] = $locationMap[$row->source_location_id] ?? $row->source_location_id;
            $record['destination_location_id'] = $locationMap[$row->destination_location_id] ?? $row->destination_location_id;
            $record['route_id'] = $routeMap[$row->route_id] ?? $row->route_id;
            $record['operation_type_id'] = $operationTypeMap[$row->operation_type_id] ?? $row->operation_type_id;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newId = DB::table('inventories_rules')->insertGetId($record);
            $map[$row->id] = $newId;
        }

        return $map;
    }

    private static function cloneInventoryWarehouses(
        int $sourceCompanyId,
        int $targetCompanyId,
        ?int $creatorId,
        array $locationMap,
        array $routeMap,
        array $operationTypeMap,
        array $ruleMap
    ): void {
        $sourceRows = DB::table('inventories_warehouses')
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($sourceRows as $row) {
            $existingWarehouseId = DB::table('inventories_warehouses')
                ->where('company_id', $targetCompanyId)
                ->where('code', $row->code)
                ->value('id');

            if ($existingWarehouseId) {
                continue;
            }

            $record = (array) $row;
            unset($record['id']);
            $record['company_id'] = $targetCompanyId;
            $record['creator_id'] = $creatorId;
            $record['view_location_id'] = $locationMap[$row->view_location_id] ?? null;
            $record['lot_stock_location_id'] = $locationMap[$row->lot_stock_location_id] ?? null;
            $record['input_stock_location_id'] = $locationMap[$row->input_stock_location_id] ?? null;
            $record['qc_stock_location_id'] = $locationMap[$row->qc_stock_location_id] ?? null;
            $record['output_stock_location_id'] = $locationMap[$row->output_stock_location_id] ?? null;
            $record['pack_stock_location_id'] = $locationMap[$row->pack_stock_location_id] ?? null;
            $record['mto_pull_id'] = $ruleMap[$row->mto_pull_id] ?? null;
            $record['buy_pull_id'] = $ruleMap[$row->buy_pull_id] ?? null;
            $record['pick_type_id'] = $operationTypeMap[$row->pick_type_id] ?? null;
            $record['pack_type_id'] = $operationTypeMap[$row->pack_type_id] ?? null;
            $record['out_type_id'] = $operationTypeMap[$row->out_type_id] ?? null;
            $record['in_type_id'] = $operationTypeMap[$row->in_type_id] ?? null;
            $record['internal_type_id'] = $operationTypeMap[$row->internal_type_id] ?? null;
            $record['qc_type_id'] = $operationTypeMap[$row->qc_type_id] ?? null;
            $record['store_type_id'] = $operationTypeMap[$row->store_type_id] ?? null;
            $record['xdock_type_id'] = $operationTypeMap[$row->xdock_type_id] ?? null;
            $record['crossdock_route_id'] = $routeMap[$row->crossdock_route_id] ?? null;
            $record['reception_route_id'] = $routeMap[$row->reception_route_id] ?? null;
            $record['delivery_route_id'] = $routeMap[$row->delivery_route_id] ?? null;
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $newWarehouseId = DB::table('inventories_warehouses')->insertGetId($record);

            DB::table('inventories_locations')->whereIn('id', array_values($locationMap))->update([
                'warehouse_id' => $newWarehouseId,
            ]);

            DB::table('inventories_operation_types')->whereIn('id', array_values($operationTypeMap))->update([
                'warehouse_id' => $newWarehouseId,
            ]);

            DB::table('inventories_rules')->whereIn('id', array_values($ruleMap))->update([
                'warehouse_id' => $newWarehouseId,
            ]);

            if (self::hasTable('inventories_route_warehouses')) {
                foreach ($routeMap as $newRouteId) {
                    $pivotExists = DB::table('inventories_route_warehouses')
                        ->where('warehouse_id', $newWarehouseId)
                        ->where('route_id', $newRouteId)
                        ->exists();

                    if (! $pivotExists) {
                        DB::table('inventories_route_warehouses')->insert([
                            'warehouse_id' => $newWarehouseId,
                            'route_id' => $newRouteId,
                        ]);
                    }
                }
            }
        }
    }

    private static function cloneSimpleByName(
        string $table,
        int $sourceCompanyId,
        int $targetCompanyId,
        ?int $creatorId,
        array $keys = ['name'],
        array $nullColumns = [],
        array $forceColumns = []
    ): void {
        if (! self::hasTable($table)) {
            return;
        }

        $rows = DB::table($table)
            ->where('company_id', $sourceCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $existsQuery = DB::table($table)->where('company_id', $targetCompanyId);

            foreach ($keys as $key) {
                $existsQuery->where($key, $row->{$key});
            }

            if ($existsQuery->exists()) {
                continue;
            }

            $record = (array) $row;
            unset($record['id']);
            $record['company_id'] = $targetCompanyId;

            if (array_key_exists('creator_id', $record)) {
                $record['creator_id'] = $creatorId;
            }

            foreach ($nullColumns as $nullColumn) {
                if (array_key_exists($nullColumn, $record)) {
                    $record[$nullColumn] = null;
                }
            }

            foreach ($forceColumns as $col => $value) {
                if (array_key_exists($col, $record)) {
                    $record[$col] = $value;
                }
            }

            if (array_key_exists('created_at', $record)) {
                $record['created_at'] = now();
            }

            if (array_key_exists('updated_at', $record)) {
                $record['updated_at'] = now();
            }

            DB::table($table)->insert($record);
        }
    }

    private static function resolveSourceCompanyId(int $targetCompanyId): ?int
    {
        return Company::withoutGlobalScopes()
            ->where('id', '!=', $targetCompanyId)
            ->orderBy('id')
            ->value('id');
    }

    private static function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
