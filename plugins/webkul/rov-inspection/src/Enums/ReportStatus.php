<?php

namespace Webkul\RovInspection\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Final = 'final';
    case Shared = 'shared';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft  => 'Draft',
            self::Final  => 'Final',
            self::Shared => 'Shared',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft  => 'gray',
            self::Final  => 'info',
            self::Shared => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft  => 'heroicon-o-pencil',
            self::Final  => 'heroicon-o-document-check',
            self::Shared => 'heroicon-o-share',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}
