<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \Webkul\Security\Models\User::withoutGlobalScopes()->find(7);
\Illuminate\Support\Facades\Auth::login($user);

$journals = \Webkul\Account\Models\Journal::withoutGlobalScopes()->get();
foreach ($journals as $j) {
    echo 'id=' . $j->id . ' type=' . $j->type->value . ' name=' . $j->name . PHP_EOL;
}


$accounts = \Webkul\Account\Models\Account::withoutGlobalScopes()->orderBy('code')->get();
foreach ($accounts as $a) {
    echo $a->code . ' - ' . $a->name . ' (' . $a->account_type->value . ') id=' . $a->id . PHP_EOL;
}

echo PHP_EOL . '=== DefaultAccountSettings ===' . PHP_EOL;
$settings = app(\Webkul\Account\Settings\DefaultAccountSettings::class);
echo 'income_account_id: ' . $settings->income_account_id . PHP_EOL;
echo 'expense_account_id: ' . $settings->expense_account_id . PHP_EOL;

echo PHP_EOL . '=== Product expense accounts ===' . PHP_EOL;
$user = \Webkul\Security\Models\User::withoutGlobalScopes()->find(7);
\Illuminate\Support\Facades\Auth::login($user);
$products = \Webkul\Account\Models\Product::withoutGlobalScopes()->with(['propertyAccountExpense', 'category.propertyAccountExpense'])->get();
foreach ($products as $p) {
    $accounts = $p->getAccounts();
    echo $p->name . ': expense_account=' . ($accounts['expense']->code ?? 'NULL') . ' id=' . ($accounts['expense']->id ?? 'NULL') . ' cost=' . $p->cost . PHP_EOL;
}
