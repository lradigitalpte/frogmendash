<?php

namespace Webkul\Inventory\Support;

final class QuantityDisplay
{
    public static function format(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '0';
        }

        return (string) (int) round((float) $state);
    }

    public static function toInteger(mixed $state): int
    {
        if ($state === null || $state === '') {
            return 0;
        }

        return (int) round((float) $state);
    }
}
