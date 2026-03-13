<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Webkul\Account\AccountManager;
use Webkul\Account\Models\Move;

// Backfill COGS for all posted customer invoices that don't yet have a COGS entry
$invoices = Move::withoutGlobalScopes()
    ->where('move_type', 'out_invoice')
    ->where('state', 'posted')
    ->get();

$mgr = app(AccountManager::class);
$backfilled = 0;
$skipped = 0;

foreach ($invoices as $inv) {
    $hasCogs = Move::withoutGlobalScopes()
        ->where('move_type', 'entry')
        ->where('invoice_origin', $inv->name)
        ->exists();

    if ($hasCogs) {
        $lineCount = DB::table('accounts_account_move_lines')
            ->whereIn('move_id', Move::withoutGlobalScopes()
                ->where('move_type', 'entry')
                ->where('invoice_origin', $inv->name)
                ->pluck('id'))
            ->count();
        echo "Already has COGS: {$inv->name} (lines={$lineCount})\n";
        $skipped++;
        continue;
    }

    try {
        $mgr->postCogsEntries($inv);
        $result = Move::withoutGlobalScopes()
            ->where('move_type', 'entry')
            ->where('invoice_origin', $inv->name)
            ->first();
        if ($result) {
            $lineCount = DB::table('accounts_account_move_lines')->where('move_id', $result->id)->count();
            echo "Backfilled {$inv->name}: COGS Move id={$result->id}, lines={$lineCount}\n";
            $backfilled++;
        } else {
            echo "No COGS needed for {$inv->name} (no qualifying product lines with cost > 0)\n";
            $skipped++;
        }
    } catch (\Throwable $e) {
        echo "ERROR for {$inv->name}: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Backfilled={$backfilled}  No-COGS/Already-done={$skipped}\n";
