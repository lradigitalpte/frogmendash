<?php

namespace Webkul\Warranty\Enums;

use Filament\Support\Contracts\HasLabel;

enum StartTrigger: string implements HasLabel
{
    case DeliveryDate      = 'delivery_date';
    case InvoiceDate       = 'invoice_date';
    case CommissioningDate = 'commissioning_date';
    case Manual            = 'manual';

    public function getLabel(): string
    {
        return match($this) {
            self::DeliveryDate      => 'Delivery date',
            self::InvoiceDate       => 'Invoice date',
            self::CommissioningDate => 'Commissioning / installation date',
            self::Manual            => 'Set manually',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
