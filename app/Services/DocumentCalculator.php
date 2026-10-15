<?php

namespace App\Services;

/**
 * Calcul des montants d'une ligne et des totaux d'un document (arrondi à 2 décimales ligne par ligne).
 */
class DocumentCalculator
{
    /**
     * @return array{subtotal: float, tax_amount: float}
     */
    public function line(float $quantity, float $unitPrice, float $taxRate): array
    {
        $subtotal = round($quantity * $unitPrice, 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => round($subtotal * $taxRate / 100, 2),
        ];
    }

    /**
     * @param  iterable<array{subtotal: float|string, tax_amount: float|string}>  $lines
     * @return array{subtotal: float, tax_total: float, total: float}
     */
    public function totals(iterable $lines): array
    {
        $subtotal = 0.0;
        $tax = 0.0;

        foreach ($lines as $line) {
            $subtotal += (float) $line['subtotal'];
            $tax += (float) $line['tax_amount'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'total' => round($subtotal + $tax, 2),
        ];
    }
}
