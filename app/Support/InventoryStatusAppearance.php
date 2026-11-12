<?php

namespace App\Support;

use App\Models\InventoryStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class InventoryStatusAppearance
{
    public const CACHE_KEY = 'inventory_status_backgrounds';

    private const TEXT_LIGHT = '#ffffff';

    private const TEXT_DARK = '#111827';

    private const DEFAULT_BACKGROUND = '#ffffff';

    /**
     * How much white to blend into muted/table variants (0 = original, 1 = white).
     */
    private const MUTED_WHITE_MIX = 0.72;

    /**
     * Fallback backgrounds when the DB has no color yet (keyed by lowercased name).
     *
     * @var array<string, string>
     */
    private const FALLBACK_BACKGROUNDS = [
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
        'sent to ebay' => '#6366f1',
        'video' => '#c026d3',
    ];

    /**
     * @var array<string, string>|null
     */
    private static ?array $backgroundsByName = null;

    /**
     * @return array{background: string, text: string}
     */
    public static function colors(?string $status, bool $muted = false): array
    {
        $background = self::background($status, $muted);

        return [
            'background' => $background,
            'text' => self::contrastingText($background),
        ];
    }

    public static function background(?string $status, bool $muted = false): string
    {
        $key = self::normalizeKey($status);
        $background = self::backgroundsByName()[$key]
            ?? self::FALLBACK_BACKGROUNDS[$key]
            ?? self::DEFAULT_BACKGROUND;

        return $muted ? self::mute($background) : $background;
    }

    /**
     * Soften a status color for dense tables (e.g. trucks index breakdown chips).
     */
    public static function mute(string $backgroundHex, float $whiteMix = self::MUTED_WHITE_MIX): string
    {
        $whiteMix = max(0.0, min(1.0, $whiteMix));
        [$red, $green, $blue] = self::hexToRgb($backgroundHex);

        $mixed = [
            (int) round($red + ((255 - $red) * $whiteMix)),
            (int) round($green + ((255 - $green) * $whiteMix)),
            (int) round($blue + ((255 - $blue) * $whiteMix)),
        ];

        return sprintf('#%02x%02x%02x', $mixed[0], $mixed[1], $mixed[2]);
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

    /**
     * Inline CSS for chips/buttons/rows.
     */
    public static function style(?string $status, bool $muted = false): string
    {
        $colors = self::colors($status, $muted);

        return 'background-color: '.$colors['background'].'; color: '.$colors['text'].';';
    }

    /**
     * Lowercased status name => colors, for Alpine/JS choosers that rebuild options at runtime.
     *
     * @return array{colors: array<string, array{background: string, text: string}>, default: array{background: string, text: string}}
     */
    public static function palette(bool $muted = false): array
    {
        $colors = [];

        foreach (self::backgroundsByName() as $name => $background) {
            $resolved = $muted ? self::mute($background) : $background;
            $colors[$name] = [
                'background' => $resolved,
                'text' => self::contrastingText($resolved),
            ];
        }

        foreach (self::FALLBACK_BACKGROUNDS as $name => $background) {
            if (isset($colors[$name])) {
                continue;
            }

            $resolved = $muted ? self::mute($background) : $background;
            $colors[$name] = [
                'background' => $resolved,
                'text' => self::contrastingText($resolved),
            ];
        }

        $defaultBackground = $muted ? self::mute(self::DEFAULT_BACKGROUND) : self::DEFAULT_BACKGROUND;

        return [
            'colors' => $colors,
            'default' => [
                'background' => $defaultBackground,
                'text' => self::contrastingText($defaultBackground),
            ],
        ];
    }

    public static function flush(): void
    {
        self::$backgroundsByName = null;
        Cache::forget(self::CACHE_KEY);
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

    /**
     * @return array<string, string>
     */
    private static function backgroundsByName(): array
    {
        if (self::$backgroundsByName !== null) {
            return self::$backgroundsByName;
        }

        if (! Schema::hasTable('inventory_statuses') || ! Schema::hasColumn('inventory_statuses', 'color')) {
            return self::$backgroundsByName = self::FALLBACK_BACKGROUNDS;
        }

        self::$backgroundsByName = Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            $backgrounds = self::FALLBACK_BACKGROUNDS;

            InventoryStatus::query()
                ->whereNotNull('color')
                ->where('color', '<>', '')
                ->orderBy('id')
                ->get(['name', 'color'])
                ->each(function (InventoryStatus $status) use (&$backgrounds): void {
                    $normalized = self::normalizeHex($status->color);

                    if ($normalized === null) {
                        return;
                    }

                    $backgrounds[self::normalizeKey($status->name)] = $normalized;
                });

            return $backgrounds;
        });

        return self::$backgroundsByName;
    }

    private static function normalizeKey(?string $status): string
    {
        $key = strtolower(trim((string) $status));

        return $key === '' ? 'triage' : $key;
    }

    private static function normalizeHex(?string $hex): ?string
    {
        $hex = ltrim(trim((string) $hex), '#');

        if (strlen($hex) === 3 && preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return '#'.strtolower($hex);
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
        $normalized = self::normalizeHex($hex) ?? self::DEFAULT_BACKGROUND;
        $hex = ltrim($normalized, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
