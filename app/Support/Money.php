<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $currency = config('company.currency');
        $amount = (float) $amount;
        $decimals = floor($amount) === $amount ? 0 : 2;

        return number_format($amount, $decimals, ',', "\u{202F}")."\u{00A0}".$currency;
    }
}
