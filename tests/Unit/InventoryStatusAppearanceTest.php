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

    public function test_quality_control_no_longer_uses_near_matching_green_text(): void
    {
        $colors = InventoryStatusAppearance::colors('Quality Control QC');

        $this->assertSame('#16a34a', $colors['background']);
        $this->assertSame('#111827', $colors['text']);
        $this->assertGreaterThanOrEqual(
            4.5,
            InventoryStatusAppearance::contrastRatio($colors['background'], $colors['text']),
        );
    }

    #[DataProvider('knownStatuses')]
    public function test_known_statuses_meet_aa_contrast(string $status): void
    {
        $colors = InventoryStatusAppearance::colors($status);

        $this->assertGreaterThanOrEqual(
            4.5,
            InventoryStatusAppearance::contrastRatio($colors['background'], $colors['text']),
            $status.' chip text should meet WCAG AA for normal text',
        );
    }

    public function test_palette_embeds_computed_text_colors(): void
    {
        $palette = InventoryStatusAppearance::palette();

        $this->assertSame(
            InventoryStatusAppearance::colors('Ready'),
            $palette['colors']['ready'],
        );
        $this->assertSame(
            InventoryStatusAppearance::colors(null),
            $palette['default'],
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function knownStatuses(): array
    {
        return [
            'Ready' => ['Ready'],
            'Repair' => ['Repair'],
            'Holding for parts' => ['Holding for parts'],
            'Quality Control QC' => ['Quality Control QC'],
            'Sold' => ['Sold'],
            'Scrap' => ['Scrap'],
            'Triage' => ['Triage'],
        ];
    }
}
