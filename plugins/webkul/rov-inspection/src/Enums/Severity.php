<?php

namespace Webkul\RovInspection\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Severity: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Low      => 'Low',
            self::Medium   => 'Medium',
            self::High     => 'High',
            self::Critical => 'Critical',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Low      => 'success',
            self::Medium   => 'info',
            self::High     => 'warning',
            self::Critical => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Low      => 'heroicon-o-check-circle',
            self::Medium   => 'heroicon-o-information-circle',
            self::High     => 'heroicon-o-exclamation-triangle',
            self::Critical => 'heroicon-o-exclamation-circle',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}
