<?php

namespace App\Support;

class InventoryStatusAppearance
{
    private const TEXT_LIGHT = '#ffffff';

    private const TEXT_DARK = '#111827';

    /**
     * Canonical chip backgrounds keyed by lowercased status name.
     * Text color is derived from WCAG contrast against the background.
     *
     * @var array<string, string>
     */
    private const BACKGROUNDS = [
        'testing' => '#67d4f1',
        'ready' => '#3b82f6',
        'show room' => '#9333ea',
        'holding' => '#ec4899',
        'repair' => '#f97316',
        'holding for parts' => '#eab308',
        'cleaning' => '#7c2d12',
        'demanufacture' => '#dc2626',
        'breakdown' => '#dc2626',
        'scrap' => '#111827',
        'quality control qc' => '#16a34a',
        'sold' => '#059669',
        'triage' => '#ffffff',
    ];

    private const DEFAULT_BACKGROUND = '#ffffff';

    /**
     * @return array{background: string, text: string}
     */
    public static function colors(?string $status): array
    {
        $background = self::background($status);

        return [
            'background' => $background,
            'text' => self::contrastingText($background),
        ];
    }

    public static function background(?string $status): string
    {
        $key = strtolower(trim((string) $status));

        if ($key === '') {
            $key = 'triage';
        }

        return self::BACKGROUNDS[$key] ?? self::DEFAULT_BACKGROUND;
    }

    /**
     * Pick black or white text for the stronger WCAG contrast ratio against the background.
     */
    public static function contrastingText(string $backgroundHex): string
    {
        $lightRatio = self::contrastRatio($backgroundHex, self::TEXT_LIGHT);
        $darkRatio = self::contrastRatio($backgroundHex, self::TEXT_DARK);

        return $lightRatio >= $darkRatio ? self::TEXT_LIGHT : self::TEXT_DARK;
    }

    public static function tailwindClasses(?string $status): string
    {
        $colors = self::colors($status);

        return 'bg-['.$colors['background'].'] text-['.$colors['text'].']';
    }

    /**
     * Lowercased status name => colors, for Alpine/JS choosers that rebuild options at runtime.
     *
     * @return array{colors: array<string, array{background: string, text: string}>, default: array{background: string, text: string}}
     */
    public static function palette(): array
    {
        $colors = [];

        foreach (self::BACKGROUNDS as $name => $background) {
            $colors[$name] = [
                'background' => $background,
                'text' => self::contrastingText($background),
            ];
        }

        return [
            'colors' => $colors,
            'default' => [
                'background' => self::DEFAULT_BACKGROUND,
                'text' => self::contrastingText(self::DEFAULT_BACKGROUND),
            ],
        ];
    }

    /**
     * WCAG 2 relative-luminance contrast ratio between two hex colors.
     */
    public static function contrastRatio(string $hexA, string $hexB): float
    {
        $luminanceA = self::relativeLuminance($hexA);
        $luminanceB = self::relativeLuminance($hexB);
        $lighter = max($luminanceA, $luminanceB);
        $darker = min($luminanceA, $luminanceB);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function relativeLuminance(string $hex): float
    {
        [$red, $green, $blue] = self::hexToRgb($hex);

        $channels = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, [$red, $green, $blue]);

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [255, 255, 255];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
