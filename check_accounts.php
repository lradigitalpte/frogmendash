<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = \Webkul\Security\Models\User::withoutGlobalScopes()->find(7);
\Illuminate\Support\Facades\Auth::login($user);

use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Account;

$journals = Journal::withoutGlobalScopes()->where('type','general')->get();
foreach($journals as $j) echo "Journal id={$j->id} name={$j->name}\n";

$accts = Account::withoutGlobalScopes()->whereIn('account_type',['expense_direct_cost','expense'])->get();
foreach($accts as $a) echo "Expense acct id={$a->id} code={$a->code} name={$a->name} type={$a->account_type->value}\n";

$inv = Account::withoutGlobalScopes()->where('name','like','%tock%')->orWhere('name','like','%nventory%')->orWhere('code','like','110%')->withoutGlobalScopes()->get();
foreach($inv as $a) echo "Asset acct id={$a->id} code={$a->code} name={$a->name} type={$a->account_type->value}\n";

// Also check products cost field
$products = \Webkul\Inventory\Models\Product::withoutGlobalScopes()->whereNotNull('cost')->where('cost','>',0)->get();
foreach($products as $p) echo "Product id={$p->id} name={$p->name} cost={$p->cost} expense_account_id={$p->property_account_expense_id}\n";
