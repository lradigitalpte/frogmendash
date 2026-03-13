<?php

namespace Webkul\Warranty\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WarrantyStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Expired  = 'expired';
    case Void     = 'void';

    public function getLabel(): string
    {
        return match($this) {
            self::Draft   => 'Draft',
            self::Active  => 'Active',
            self::Expired => 'Expired',
            self::Void    => 'Void',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::Draft   => 'gray',
            self::Active  => 'success',
            self::Expired => 'danger',
            self::Void    => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::Draft   => 'heroicon-o-clock',
            self::Active  => 'heroicon-o-shield-check',
            self::Expired => 'heroicon-o-shield-exclamation',
            self::Void    => 'heroicon-o-x-circle',
        };
    }

    public static function options(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }
}
