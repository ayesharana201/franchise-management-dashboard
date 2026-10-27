<?php

namespace Tests\Unit;

use App\Models\Location;
use App\Services\RoyaltyCalculator;
use Tests\TestCase;

class RoyaltyCalculatorTest extends TestCase
{
    private function location(float $royalty = 6, float $marketing = 2, float $tech = 150): Location
    {
        return new Location(['royalty_rate' => $royalty, 'marketing_fee_rate' => $marketing, 'tech_fee' => $tech]);
    }

    public function test_calculates_percentage_and_flat_fees(): void
    {
        $r = (new RoyaltyCalculator())->calculate($this->location(), 50000);

        $this->assertSame(3000.0, $r['royalty_amount']);
        $this->assertSame(1000.0, $r['marketing_fee']);
        $this->assertSame(150.0, $r['tech_fee']);
        $this->assertSame(4150.0, $r['total_due']);
    }

    public function test_zero_sales_still_charges_flat_tech_fee(): void
    {
        $r = (new RoyaltyCalculator())->calculate($this->location(), 0);

        $this->assertSame(0.0, $r['royalty_amount']);
        $this->assertSame(150.0, $r['total_due']);
    }

    public function test_negative_sales_are_treated_as_zero(): void
    {
        $r = (new RoyaltyCalculator())->calculate($this->location(), -500);

        $this->assertSame(0.0, $r['gross_sales']);
        $this->assertSame(150.0, $r['total_due']);
    }

    public function test_rounds_to_two_decimals(): void
    {
        $r = (new RoyaltyCalculator())->calculate($this->location(5.5, 1.25, 0), 1234.56);

        $this->assertSame(67.9, $r['royalty_amount']);
        $this->assertSame(15.43, $r['marketing_fee']);
    }
}
