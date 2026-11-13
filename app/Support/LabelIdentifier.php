<?php

namespace App\Support;

use App\Models\Model as ApplianceModel;

class LabelIdentifier
{
    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($value))) ?? '');
    }

    public static function sameSerial(?string $stored, ?string $incoming): bool
    {
        $stored = self::normalize((string) $stored);
        $incoming = self::normalize((string) $incoming);

        if ($stored === '' || $incoming === '') {
            return false;
        }

        if ($stored === $incoming) {
            return true;
        }

        if (! ctype_digit($stored) || ! ctype_digit($incoming)) {
            return false;
        }

        return ltrim($stored, '0') === ltrim($incoming, '0');
    }

    public static function matchingModel(string $modelNumber, ?float $msrp = null): ?ApplianceModel
    {
        $wanted = self::normalize($modelNumber);

        if ($wanted === '') {
            return null;
        }

        $matches = ApplianceModel::query()
            ->orderBy('id')
            ->get()
            ->filter(fn (ApplianceModel $model): bool => self::normalize((string) $model->model_number) === $wanted)
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }

        if ($msrp !== null) {
            $priced = $matches->first(
                fn (ApplianceModel $model): bool => abs((float) $model->msrp - $msrp) < 0.005
            );

            if ($priced instanceof ApplianceModel) {
                return $priced;
            }
        }

        return $matches->first();
    }
}
