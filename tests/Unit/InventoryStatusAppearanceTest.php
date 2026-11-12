<?php

namespace Tests\Unit;

use App\Support\InventoryStatusAppearance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InventoryStatusAppearanceTest extends TestCase
{
    public function test_contrasting_text_prefers_dark_on_light_backgrounds(): void
    {
        $this->assertSame('#111827', InventoryStatusAppearance::contrastingText('#ffffff'));
        $this->assertSame('#111827', InventoryStatusAppearance::contrastingText('#eab308'));
        $this->assertSame('#111827', InventoryStatusAppearance::contrastingText('#67d4f1'));
    }

    public function test_contrasting_text_prefers_light_on_dark_backgrounds(): void
    {
        $this->assertSame('#ffffff', InventoryStatusAppearance::contrastingText('#111827'));
        $this->assertSame('#ffffff', InventoryStatusAppearance::contrastingText('#7c2d12'));
    }

    public function test_mute_blends_toward_white(): void
    {
        $muted = InventoryStatusAppearance::mute('#000000', 0.5);

        $this->assertSame('#808080', $muted);
    }

    #[DataProvider('knownFallbackBackgrounds')]
    public function test_fallback_backgrounds_meet_aa_contrast_with_chosen_text(string $background): void
    {
        $text = InventoryStatusAppearance::contrastingText($background);

        $this->assertGreaterThanOrEqual(
            4.5,
            InventoryStatusAppearance::contrastRatio($background, $text),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function knownFallbackBackgrounds(): array
    {
        return [
            'ready' => ['#3b82f6'],
            'repair' => ['#f97316'],
            'holding for parts' => ['#eab308'],
            'quality control' => ['#16a34a'],
            'sold' => ['#059669'],
            'scrap' => ['#111827'],
            'triage' => ['#ffffff'],
        ];
    }
}
