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
}
