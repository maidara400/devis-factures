<?php

namespace App\Enums;

enum DocumentType: string
{
    case Quote = 'quote';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Devis',
            self::Invoice => 'Facture',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Quote => 'DEV',
            self::Invoice => 'FAC',
        };
    }
}
