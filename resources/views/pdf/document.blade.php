<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 0; }
        .band { background: #4f46e5; color: #fff; padding: 28px 40px 22px; }
        .band table { width: 100%; }
        .brand { font-size: 18px; font-weight: bold; letter-spacing: .3px; }
        .brand-sub { font-size: 9px; color: #c7d2fe; margin-top: 4px; line-height: 1.5; }
        .doc-type { font-size: 26px; font-weight: bold; text-align: right; letter-spacing: 2px; text-transform: uppercase; }
        .doc-number { text-align: right; font-size: 12px; color: #c7d2fe; margin-top: 4px; }
        .content { padding: 28px 40px; }
        .meta { width: 100%; margin-bottom: 26px; }
        .meta td { vertical-align: top; width: 50%; }
        .label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 5px; }
        .client-name { font-size: 13px; font-weight: bold; margin-bottom: 3px; }
        .muted { color: #64748b; line-height: 1.55; }
        .dates { text-align: right; }
        .dates td { padding: 3px 0 3px 18px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; }
        .badge-draft { background: #f1f5f9; color: #475569; }
        .badge-sent { background: #dbeafe; color: #1d4ed8; }
        .badge-paid { background: #d1fae5; color: #047857; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th { background: #f1f5f9; color: #475569; font-size: 8px; text-transform: uppercase; letter-spacing: .8px; text-align: left; padding: 9px 10px; }
        table.lines td { padding: 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .r { text-align: right; }
        .totals { width: 45%; margin-left: 55%; margin-top: 18px; border-collapse: collapse; }
        .totals td { padding: 6px 10px; }
        .totals .grand td { background: #4f46e5; color: #fff; font-size: 12px; font-weight: bold; }
        .notes { margin-top: 28px; padding: 12px 14px; background: #f8fafc; border-left: 3px solid #4f46e5; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 14px 40px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
@php
    use App\Support\Money;
    use App\Enums\DocumentType;
    $isQuote = $document->type === DocumentType::Quote;
@endphp

<div class="band">
    <table>
        <tr>
            <td>
                <div class="brand">{{ config('company.name') }}</div>
                <div class="brand-sub">
                    {{ config('company.address') }}<br>
                    {{ config('company.email') }} · {{ config('company.phone') }}<br>
                    {{ config('company.tax_number') }}
                </div>
            </td>
            <td>
                <div class="doc-type">{{ $document->type->label() }}</div>
                <div class="doc-number">N° {{ $document->number }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="content">
    <table class="meta">
        <tr>
            <td>
                <div class="label">Facturé à</div>
                <div class="client-name">{{ $document->client->name }}</div>
                <div class="muted">
                    @if($document->client->address){{ $document->client->address }}<br>@endif
                    @if($document->client->city){{ $document->client->city }}, {{ $document->client->country }}<br>@endif
                    @if($document->client->email){{ $document->client->email }}<br>@endif
                    @if($document->client->tax_number)NINEA : {{ $document->client->tax_number }}@endif
                </div>
            </td>
            <td>
                <table class="dates" align="right">
                    <tr><td class="muted">Date d'émission</td><td class="r"><strong>{{ $document->issue_date->format('d/m/Y') }}</strong></td></tr>
                    @if($document->due_date)
                        <tr><td class="muted">{{ $isQuote ? 'Valable jusqu\'au' : 'Échéance' }}</td><td class="r"><strong>{{ $document->due_date->format('d/m/Y') }}</strong></td></tr>
                    @endif
                    <tr><td class="muted">Statut</td><td class="r"><span class="badge badge-{{ $document->status->value }}">{{ $document->status->label($document->type) }}</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
        <tr>
            <th style="width:42%">Désignation</th>
            <th class="r">Qté</th>
            <th class="r">P.U. HT</th>
            <th class="r">TVA</th>
            <th class="r">Total HT</th>
        </tr>
        </thead>
        <tbody>
        @foreach($document->lines as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="r">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                <td class="r">{{ Money::format($line->unit_price) }}</td>
                <td class="r">{{ rtrim(rtrim($line->tax_rate, '0'), '.') }} %</td>
                <td class="r"><strong>{{ Money::format($line->subtotal) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Total HT</td><td class="r">{{ Money::format($document->subtotal) }}</td></tr>
        <tr><td class="muted">TVA</td><td class="r">{{ Money::format($document->tax_total) }}</td></tr>
        <tr class="grand"><td>Total TTC</td><td class="r">{{ Money::format($document->total) }}</td></tr>
    </table>

    @if($document->notes)
        <div class="notes">
            <div class="label">Notes</div>
            {!! nl2br(e($document->notes)) !!}
        </div>
    @endif

    @unless($isQuote)
        <div class="notes">
            <div class="label">Conditions de paiement</div>
            {{ config('company.payment_terms') }}
        </div>
    @endunless
</div>

<div class="footer">{{ config('company.name') }} · {{ config('company.tax_number') }} · {{ $document->number }}</div>
</body>
</html>
