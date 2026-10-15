<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $invoices = Document::where('type', DocumentType::Invoice);

        $stats = [
            'paid_month' => (clone $invoices)->where('status', DocumentStatus::Paid)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            'pending' => (clone $invoices)->where('status', DocumentStatus::Sent)->sum('total'),
            'overdue' => (clone $invoices)->where('status', DocumentStatus::Sent)
                ->whereDate('due_date', '<', today())->count(),
            'drafts' => Document::where('status', DocumentStatus::Draft)->count(),
            'clients' => Client::count(),
            'open_quotes' => Document::where('type', DocumentType::Quote)->where('status', DocumentStatus::Sent)->count(),
        ];

        return view('dashboard', [
            'stats' => $stats,
            'chart' => $this->monthlyChart(),
            'recent' => Document::with('client')->latest('id')->limit(6)->get(),
        ]);
    }

    /**
     * Facturé vs encaissé sur les 6 derniers mois.
     *
     * @return list<array{label: string, invoiced: float, paid: float}>
     */
    private function monthlyChart(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $invoices = Document::where('type', DocumentType::Invoice)
            ->whereDate('issue_date', '>=', $start)
            ->get(['issue_date', 'status', 'total']);

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $inMonth = $invoices->filter(fn ($d) => $d->issue_date->isSameMonth($month));

            $months[] = [
                'label' => $month->translatedFormat('M'),
                'invoiced' => (float) $inMonth->sum('total'),
                'paid' => (float) $inMonth->where('status', DocumentStatus::Paid)->sum('total'),
            ];
        }

        return $months;
    }
}
