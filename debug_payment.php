<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Webkul\Account\Enums\PaymentStatus;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\PartialReconcile;
use Webkul\Account\Models\Payment;

$user = \Webkul\Security\Models\User::withoutGlobalScopes()->find(7);
\Illuminate\Support\Facades\Auth::login($user);

echo "Reconciling all existing paid/in-process payments...\n\n";

$payments = Payment::withoutGlobalScopes()
    ->whereIn('state', [PaymentStatus::PAID->value, PaymentStatus::IN_PROCESS->value])
    ->get();

foreach ($payments as $payment) {
    $invoices = $payment->invoices;
    if ($invoices->isEmpty()) {
        continue;
    }

    echo "Payment {$payment->name} (id={$payment->id}) - {$invoices->count()} invoice(s)\n";

    try {
        AccountFacade::reconcilePaymentWithInvoices($payment);
        echo "  Reconciled OK\n";
    } catch (\Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }

    foreach ($invoices as $inv) {
        $inv->refresh();
        echo "  Invoice {$inv->name}: payment_state={$inv->payment_state->value} amount_residual={$inv->amount_residual}\n";
    }
}

echo "\nTotal PartialReconciles: " . PartialReconcile::withoutGlobalScopes()->count() . "\n";
