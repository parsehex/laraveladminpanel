<?php

namespace Tests\Unit\Legacy;

use App\Legacy\LegacyText;
use Tests\TestCase;

class LegacyTextTest extends TestCase
{
    public function test_plain_decodes_html_entities(): void
    {
        $this->assertSame(
            "TIM'S OLD REFRIGERATOR",
            LegacyText::plain('TIM&#039;S OLD REFRIGERATOR'),
        );
        $this->assertSame(
            '36" Standard-Depth',
            LegacyText::plain('36&quot; Standard-Depth'),
        );
    }

    public function test_clean_part_strips_trailing_quote_comma_and_lifts_substitute(): void
    {
        $cleaned = LegacyText::cleanPart(
            "240383406\nUSE WCI 5304515677",
            "SCREW, TRUSS HD QUAD, #10-16 X .500, ZINC\",\nSCREW",
            null,
        );

        $this->assertSame('240383406', $cleaned['part_number']);
        $this->assertSame('SCREW, TRUSS HD QUAD, #10-16 X .500, ZINC', $cleaned['product_name']);
        $this->assertSame('USE WCI 5304515677', $cleaned['cross_reference']);
        $this->assertTrue($cleaned['changed']);
    }

    public function test_clean_part_leaves_a_normal_row_unchanged(): void
    {
        $cleaned = LegacyText::cleanPart('241601001', 'WRENCH', null);

        $this->assertSame('241601001', $cleaned['part_number']);
        $this->assertSame('WRENCH', $cleaned['product_name']);
        $this->assertNull($cleaned['cross_reference']);
        $this->assertFalse($cleaned['changed']);
    }
}
