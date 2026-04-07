<?php

namespace Webkul\RovInspection\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Severity: string implements HasColor, HasIcon, HasLabel
{
    case Major = 'major';
    case Moderate = 'moderate';
    case Minor = 'minor';

    public const LEGACY_MAP = [
        'critical' => 'major',
        'high' => 'major',
        'medium' => 'moderate',
        'low' => 'minor',
    ];

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        if (isset(self::LEGACY_MAP[$normalized])) {
            return self::LEGACY_MAP[$normalized];
        }

        return self::tryFrom($normalized)?->value;
    }

    public static function labelFor(?string $value): string
    {
        $normalized = self::normalize($value);

        return $normalized ? (self::tryFrom($normalized)?->getLabel() ?? '—') : '—';
    }

    public static function colorFor(?string $value): string|array|null
    {
        $normalized = self::normalize($value);

        return $normalized ? (self::tryFrom($normalized)?->getColor() ?? 'gray') : 'gray';
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Major    => 'Major',
            self::Moderate => 'Moderate',
            self::Minor    => 'Minor',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Major    => 'danger',
            self::Moderate => 'warning',
            self::Minor    => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Major    => 'heroicon-o-exclamation-circle',
            self::Moderate => 'heroicon-o-exclamation-triangle',
            self::Minor    => 'heroicon-o-information-circle',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}
