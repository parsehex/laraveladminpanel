<?php

namespace App\Legacy;

final class LegacyText
{
    public static function plain(mixed $value): ?string
    {
        $decoded = self::decode($value);
        if ($decoded === null) {
            return null;
        }

        $trimmed = trim($decoded);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array{part_number: string, product_name: ?string, cross_reference: ?string, changed: bool}
     */
    public static function cleanPart(mixed $partNumber, mixed $productName, mixed $crossReference): array
    {
        $originalNumber = self::plain($partNumber);
        $originalName = self::plain($productName);
        $originalCross = self::plain($crossReference);

        $number = null;
        $substitutes = [];

        foreach (preg_split('/\R/', $originalNumber ?? '') ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^USE\s+WCI\s+\S+/i', $line) === 1) {
                $substitutes[] = $line;

                continue;
            }

            if ($number === null) {
                $number = strtoupper(preg_replace('/[^A-Z0-9-]/', '', $line) ?? '');
                $number = $number === '' ? null : $number;
            }
        }

        $name = null;
        $nameLines = preg_split('/\R/', $originalName ?? '') ?: [];
        $firstNameLine = trim((string) ($nameLines[0] ?? ''));
        if ($firstNameLine !== '') {
            $firstNameLine = preg_replace('/",\s*$/', '', $firstNameLine) ?? $firstNameLine;
            $firstNameLine = str_replace('""', '"', $firstNameLine);
            $firstNameLine = trim($firstNameLine);
            $name = $firstNameLine === '' ? null : $firstNameLine;
        }

        $cross = $originalCross ?? '';
        if ($substitutes !== []) {
            $note = implode('; ', $substitutes);
            $cross = $cross === '' ? $note : $cross.' '.$note;
        }
        $cross = $cross === '' ? null : $cross;

        return [
            'part_number' => $number ?? ($originalNumber ?? ''),
            'product_name' => $name,
            'cross_reference' => $cross,
            'changed' => $number !== $originalNumber
                || $name !== $originalName
                || $cross !== $originalCross,
        ];
    }

    private static function decode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = (string) $value;
        for ($pass = 0; $pass < 2; $pass++) {
            $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) {
                break;
            }

            $text = $decoded;
        }

        return $text;
    }
}
