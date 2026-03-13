<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Webkul\Account\Models\Move;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\JournalType;

$inv = Move::withoutGlobalScopes()->where('name', 'INV/2026/15')->first();
if (! $inv) {
    echo "Invoice not found\n";
    exit;
}

echo "Invoice id={$inv->id} type=" . (is_object($inv->move_type) ? $inv->move_type->value : $inv->move_type) . " company={$inv->company_id} currency={$inv->currency_id}\n";

$cogs = Account::withoutGlobalScopes()->where('code', '500000')->first()
    ?? Account::withoutGlobalScopes()->where('account_type', AccountType::EXPENSE_DIRECT_COST)->first();
$stock = Account::withoutGlobalScopes()->where('code', '110100')->first()
    ?? Account::withoutGlobalScopes()->where('code', 'LIKE', '110%')->where('account_type', AccountType::ASSET_CURRENT)->first();
$journal = Journal::withoutGlobalScopes()->where('type', JournalType::GENERAL)->where('company_id', $inv->company_id)->first();

echo "COGS account=" . ($cogs?->id ?? 'null') . " stock account=" . ($stock?->id ?? 'null') . " general journal=" . ($journal?->id ?? 'null') . "\n";

echo "Invoice lines total=" . $inv->lines->count() . "\n";

$productLines = $inv->lines->filter(function ($line) {
    $displayType = is_object($line->display_type) ? $line->display_type->value : $line->display_type;
    return $displayType === 'product' && $line->product_id && $line->quantity > 0;
});

echo "Qualifying product lines=" . $productLines->count() . "\n";

foreach ($productLines as $line) {
    $product = Webkul\Account\Models\Product::withoutGlobalScopes()->find($line->product_id);
    echo " line#{$line->id} product={$line->product_id} qty={$line->quantity} cost=" . ($product?->cost ?? 'null') . "\n";
}

// Check if COGS entry already exists for this invoice
$existing = Move::withoutGlobalScopes()
    ->where('move_type', 'entry')
    ->where('invoice_origin', $inv->name)
    ->get();

if ($existing->count() > 0) {
    echo "\nCOGS entries already exist for {$inv->name}:\n";
    foreach ($existing as $e) {
        echo "  Move id={$e->id} state=" . (is_object($e->state) ? $e->state->value : $e->state) . "\n";
        foreach ($e->lines as $ml) {
            $acc = Webkul\Account\Models\Account::withoutGlobalScopes()->find($ml->account_id);
            echo "    Line id={$ml->id} account={$ml->account_id}({$acc?->code}) debit={$ml->debit} credit={$ml->credit}\n";
        }
    }
} else {
    echo "\nNo COGS entry yet — triggering postCogsEntries...\n";
    $mgr = app(\Webkul\Account\AccountManager::class);
    $mgr->postCogsEntries($inv);
    $after = Move::withoutGlobalScopes()
        ->where('move_type', 'entry')
        ->where('invoice_origin', $inv->name)
        ->get();
    if ($after->count() > 0) {
        echo "SUCCESS: COGS entries created:\n";
        foreach ($after as $e) {
            echo "  Move id={$e->id} state=" . (is_object($e->state) ? $e->state->value : $e->state) . "\n";
            foreach ($e->lines as $ml) {
                $acc = Webkul\Account\Models\Account::withoutGlobalScopes()->find($ml->account_id);
                echo "    Line id={$ml->id} account={$ml->account_id}({$acc?->code}) debit={$ml->debit} credit={$ml->credit}\n";
            }
        }
    } else {
        echo "FAILED: still no COGS entries after trigger.\n";
    }
}
