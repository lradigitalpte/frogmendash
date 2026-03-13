<?php

namespace Webkul\Account\Filament\Resources\PaymentResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Account\Enums\PaymentStatus;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Payment;

class MarkAsPaidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'customers.payment.mark-as-paid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Mark as Paid')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading('Mark Payment as Paid')
            ->modalDescription('This will mark the payment as paid and reconcile it with the linked invoice(s).')
            ->action(function (Payment $record, Component $livewire): void {
                $record->state = PaymentStatus::PAID;
                $record->save();

                // Reconcile with linked invoices
                AccountFacade::reconcilePaymentWithInvoices($record);

                $livewire->refreshFormData(['state']);

                Notification::make()
                    ->success()
                    ->title('Payment Marked as Paid')
                    ->body('The payment has been marked as paid and reconciled with the invoice.')
                    ->send();
            })
            ->hidden(fn (Payment $record) => $record->state !== PaymentStatus::IN_PROCESS);
    }
}
