<?php

namespace App\Legacy;

final class LegacyCopyValue
{
    public static function parse(?string $value): mixed
    {
        if ($value === null || $value === '\\N') {
            return null;
        }

        return str_replace(['\\\\', '\\t', '\\n', '\\r'], ['\\', "\t", "\n", "\r"], $value);
    }

    public static function bool(mixed $value): bool
    {
        return in_array($value, ['t', 'true', '1', 1, true], true);
    }

    public static function decimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    public static function int(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
