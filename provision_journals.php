<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Webkul\Account\AccountManager;
use Webkul\Account\Models\Move;

// Delete any broken COGS moves for INV/2026/15 (no lines)
$brokenMoves = Move::withoutGlobalScopes()
    ->where('move_type', 'entry')
    ->where('invoice_origin', 'INV/2026/15')
    ->get();
foreach ($brokenMoves as $bm) {
    $lineCount = DB::table('accounts_account_move_lines')->where('move_id', $bm->id)->count();
    if ($lineCount === 0) {
        $bm->forceDelete();
        echo "Deleted broken move id={$bm->id} (had 0 lines).\n";
    }
}

// Re-trigger COGS for INV/2026/15
$inv = Move::withoutGlobalScopes()->where('name', 'INV/2026/15')->first();
if ($inv) {
    try {
        $mgr = app(AccountManager::class);
        $mgr->postCogsEntries($inv);
        echo "Re-triggered postCogsEntries for INV/2026/15.\n";
    } catch (\Throwable $e) {
        echo "ERROR during postCogsEntries: " . $e->getMessage() . "\n";
        echo $e->getFile() . ":" . $e->getLine() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
    echo "Re-triggered postCogsEntries for INV/2026/15.\n";

    $result = Move::withoutGlobalScopes()
        ->where('move_type', 'entry')
        ->where('invoice_origin', 'INV/2026/15')
        ->first();

    if ($result) {
        echo "COGS Move id={$result->id} state=" . (is_object($result->state) ? $result->state->value : $result->state) . "\n";
        $lines = DB::table('accounts_account_move_lines')->where('move_id', $result->id)->get(['id', 'account_id', 'debit', 'credit']);
        echo "Lines: " . $lines->count() . "\n";
        foreach ($lines as $line) {
            $acc = DB::table('accounts_accounts')->where('id', $line->account_id)->first(['code', 'name']);
            echo "  id={$line->id} account={$line->account_id}({$acc?->code}) debit={$line->debit} credit={$line->credit}\n";
        }
    } else {
        echo "FAILED: no COGS entry created.\n";
    }
}
