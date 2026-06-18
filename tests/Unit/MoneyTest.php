<?php
namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_construction_and_conversion(): void
    {
        $this->assertSame(10000, Money::ofPaise(10000)->paise);
        $this->assertSame(10000, Money::ofRupees(100)->paise);
        $this->assertSame(100.0, Money::ofPaise(10000)->toRupees());
    }

    public function test_add_and_subtract(): void
    {
        $this->assertSame(15000, Money::ofPaise(10000)->add(Money::ofPaise(5000))->paise);
        $this->assertSame(5000, Money::ofPaise(10000)->subtract(Money::ofPaise(5000))->paise);
    }

    public function test_subtract_never_negative(): void
    {
        $this->assertSame(0, Money::ofPaise(5000)->subtract(Money::ofPaise(9000))->paise);
    }

    public function test_percent(): void
    {
        // 18% GST on ₹800 (80000 paise) = ₹144 (14400 paise)
        $this->assertSame(14400, Money::ofPaise(80000)->percent(18)->paise);
    }

    public function test_negative_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::ofPaise(-1);
    }
}
