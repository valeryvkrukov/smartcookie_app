<?php

namespace Tests\Unit;

use App\Models\Timesheet;
use PHPUnit\Framework\TestCase;

class TimesheetCalculationTest extends TestCase
{
    public function test_30_minutes_returns_half_credit(): void
    {
        $this->assertSame(0.5, Timesheet::calculateCredits(30));
    }

    public function test_60_minutes_returns_one_credit(): void
    {
        $this->assertSame(1.0, Timesheet::calculateCredits(60));
    }

    public function test_90_minutes_returns_one_and_half_credits(): void
    {
        $this->assertSame(1.5, Timesheet::calculateCredits(90));
    }

    public function test_120_minutes_returns_two_credits(): void
    {
        $this->assertSame(2.0, Timesheet::calculateCredits(120));
    }
}
