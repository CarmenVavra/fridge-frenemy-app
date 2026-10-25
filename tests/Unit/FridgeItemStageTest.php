<?php

namespace Tests\Unit;

use App\Enums\ButlerTone;
use App\Models\FridgeItem;
use App\Support\FridgeMood;
use PHPUnit\Framework\TestCase;

class FridgeItemStageTest extends TestCase
{
    public function test_urgency_stages_follow_the_design_spec(): void
    {
        $this->assertSame(5, FridgeItem::stageForDays(-2));
        $this->assertSame(4, FridgeItem::stageForDays(0));
        $this->assertSame(4, FridgeItem::stageForDays(1));
        $this->assertSame(3, FridgeItem::stageForDays(2));
        $this->assertSame(3, FridgeItem::stageForDays(3));
        $this->assertSame(2, FridgeItem::stageForDays(4));
        $this->assertSame(2, FridgeItem::stageForDays(7));
        $this->assertSame(1, FridgeItem::stageForDays(8));
    }

    public function test_quotes_only_exist_for_critical_items(): void
    {
        $this->assertNull(ButlerTone::Classic->quote('Eier', 9));
        $this->assertSame('Butter hat noch einen Tag. Ich bin nicht wütend. Nur enttäuscht.', ButlerTone::Classic->quote('Butter', 1));
        $this->assertStringContainsString('Seit 1 Tag.', (string) ButlerTone::DramaQueen->quote('Spinat', -1));
    }

    public function test_mood_thresholds(): void
    {
        $this->assertSame('Stolz', FridgeMood::forRescueRatio(0.72)['word']);
        $this->assertSame('Skeptisch', FridgeMood::forRescueRatio(0.5)['word']);
        $this->assertSame('Wütend', FridgeMood::forRescueRatio(0.49)['word']);
    }
}
