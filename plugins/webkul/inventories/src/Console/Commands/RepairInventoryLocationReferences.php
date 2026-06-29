<?php

namespace Webkul\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Inventory\Services\InventoryLocationReferenceRepairer;
use Webkul\Support\Models\Company;

class RepairInventoryLocationReferences extends Command
{
    protected $signature = 'erp:inventory:repair-location-references
        {--company= : Limit to a single company ID}
        {--dry-run : Report issues without writing changes (default behaviour)}
        {--fix : Apply repairs}
        {--provision-missing : Clone inventory config from the template company when locations are missing}
        {--include-done : Also repair completed or canceled transfers}';

    protected $description = 'Audit and repair inventory records that reference locations from another company';

    public function handle(InventoryLocationReferenceRepairer $repairer): int
    {
        $companyId = $this->option('company') !== null
            ? (int) $this->option('company')
            : null;

        if ($companyId !== null && ! Company::withoutGlobalScopes()->where('id', $companyId)->exists()) {
            $this->error("Company #{$companyId} was not found.");

            return self::FAILURE;
        }

        $dryRun = ! $this->option('fix');
        $provisionMissing = (bool) $this->option('provision-missing');
        $includeDone = (bool) $this->option('include-done');

        if ($dryRun) {
            $this->info('Dry run — no database changes will be made. Pass --fix to apply repairs.');
        } elseif ($provisionMissing) {
            $this->warn('Running with --provision-missing: missing inventory config will be cloned from the template company.');
        }

        $result = $repairer->run($companyId, $dryRun, $provisionMissing, $includeDone);
        $issues = collect($result['issues']);
        $summary = $repairer->issueSummary($issues);

        if ($issues->isEmpty()) {
            $this->info('No cross-company inventory location references found.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Company', 'Table', 'Record', 'Column', 'Current location', 'Resolution', 'Status'],
            $issues->map(fn (array $issue) => [
                $issue['company_name'],
                $issue['table'],
                $issue['record_label'],
                $issue['location_column'],
                $issue['wrong_location'],
                $issue['resolved_location'] ?? '—',
                $issue['status'],
            ])->all()
        );

        $this->newLine();
        $this->line("Total issues: {$summary['total']}");

        foreach ($summary['by_company'] as $companyName => $count) {
            $this->line("  {$companyName}: {$count}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("{$summary['would_repair']} issue(s) can be repaired automatically.");
            $this->line("{$summary['unrepairable']} issue(s) need manual attention or --provision-missing.");
            $this->newLine();
            $this->comment('Run with --fix to apply repairs. Add --provision-missing if a company has no inventory locations yet.');

            return $summary['unrepairable'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->info("Repaired {$result['repaired']} reference(s).");

        if ($result['unrepairable'] > 0) {
            $this->warn("{$result['unrepairable']} reference(s) could not be repaired automatically.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
