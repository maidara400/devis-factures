<?php

/*
| Informations de l'émetteur, imprimées sur les devis et factures (PDF / e-mails).
*/

return [
    'name' => env('COMPANY_NAME', 'Votre Entreprise SARL'),
    'address' => env('COMPANY_ADDRESS', 'Almadies, Dakar, Sénégal'),
    'email' => env('COMPANY_EMAIL', 'contact@entreprise.sn'),
    'phone' => env('COMPANY_PHONE', '+221 33 000 00 00'),
    'tax_number' => env('COMPANY_TAX_NUMBER', 'NINEA 000000000'),
    'currency' => env('COMPANY_CURRENCY', 'FCFA'),
    'payment_terms' => env('COMPANY_PAYMENT_TERMS', 'Paiement à 30 jours par virement ou Wave / Orange Money.'),
];
