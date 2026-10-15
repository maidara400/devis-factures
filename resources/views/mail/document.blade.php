<x-mail::message>
# {{ $document->type->label() }} {{ $document->number }}

Bonjour {{ $document->client->name }},

Veuillez trouver ci-joint votre {{ strtolower($document->type->label()) }} **{{ $document->number }}**
d'un montant de **{{ \App\Support\Money::format($document->total) }}** TTC.

@if($document->due_date)
{{ $document->type === \App\Enums\DocumentType::Quote ? 'Ce devis est valable jusqu\'au' : 'Échéance de paiement :' }}
**{{ $document->due_date->format('d/m/Y') }}**.
@endif

Cordialement,<br>
{{ config('company.name') }}<br>
{{ config('company.phone') }}
</x-mail::message>
