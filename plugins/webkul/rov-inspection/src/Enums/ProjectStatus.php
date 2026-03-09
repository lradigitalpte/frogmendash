<?php

namespace Webkul\RovInspection\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft      => 'Draft',
            self::InProgress => 'In Progress',
            self::Completed  => 'Completed',
            self::Archived   => 'Archived',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft      => 'gray',
            self::InProgress => 'info',
            self::Completed  => 'success',
            self::Archived   => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft      => 'heroicon-o-pencil',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Completed  => 'heroicon-o-check-circle',
            self::Archived   => 'heroicon-o-archive-box',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray();
    }
}
