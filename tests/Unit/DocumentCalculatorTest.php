<?php

namespace Tests\Unit;

use App\Services\DocumentCalculator;
use PHPUnit\Framework\TestCase;

class DocumentCalculatorTest extends TestCase
{
    public function test_line_amounts_are_rounded_to_two_decimals(): void
    {
        $line = (new DocumentCalculator)->line(3, 33.333, 18);

        $this->assertSame(100.0, $line['subtotal']);
        $this->assertSame(18.0, $line['tax_amount']);
    }

    public function test_totals_sum_all_lines(): void
    {
        $totals = (new DocumentCalculator)->totals([
            ['subtotal' => 200000, 'tax_amount' => 36000],
            ['subtotal' => 50000, 'tax_amount' => 9000],
        ]);

        $this->assertSame(['subtotal' => 250000.0, 'tax_total' => 45000.0, 'total' => 295000.0], $totals);
    }
}
