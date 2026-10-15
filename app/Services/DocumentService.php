<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Exceptions\BusinessRuleException;
use App\Models\Article;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly DocumentCalculator $calculator,
    ) {}

    /**
     * Crée un document (brouillon) avec ses lignes, son numéro et ses totaux, de façon atomique.
     *
     * @param  array{type: string, client_id: int, issue_date: string, due_date?: ?string, notes?: ?string, lines: array<int, array<string, mixed>>}  $data
     */
    public function create(array $data, User $user): Document
    {
        return DB::transaction(function () use ($data, $user) {
            $type = DocumentType::from($data['type']);
            $issueDate = Carbon::parse($data['issue_date']);

            $document = Document::create([
                'type' => $type,
                'number' => $this->numbers->next($type, $issueDate),
                'client_id' => $data['client_id'],
                'user_id' => $user->id,
                'status' => DocumentStatus::Draft,
                'issue_date' => $issueDate,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($document, $data['lines']);

            return $document->load(['client', 'lines', 'user']);
        });
    }

    /**
     * Met à jour un brouillon (en-tête + remplacement complet des lignes).
     */
    public function update(Document $document, array $data): Document
    {
        if (! $document->isDraft()) {
            throw new BusinessRuleException('Seul un brouillon peut être modifié.');
        }

        return DB::transaction(function () use ($document, $data) {
            $document->update(collect($data)->only(['client_id', 'issue_date', 'due_date', 'notes'])->all());

            if (isset($data['lines'])) {
                $this->syncLines($document, $data['lines']);
            }

            return $document->refresh()->load(['client', 'lines', 'user']);
        });
    }

    public function delete(Document $document): void
    {
        if (! $document->isDraft()) {
            throw new BusinessRuleException('Seul un brouillon peut être supprimé.');
        }

        $document->delete();
    }

    /**
     * Change le statut en respectant les transitions autorisées (brouillon → envoyé → payé).
     */
    public function changeStatus(Document $document, DocumentStatus $target): Document
    {
        if (! $document->status->canTransitionTo($target)) {
            throw new BusinessRuleException(sprintf(
                'Transition impossible : %s → %s.',
                $document->status->label($document->type),
                $target->label($document->type),
            ));
        }

        if ($target === DocumentStatus::Sent && $document->lines()->doesntExist()) {
            throw new BusinessRuleException('Un document sans ligne ne peut pas être envoyé.');
        }

        $document->forceFill([
            'status' => $target,
            'sent_at' => match ($target) {
                DocumentStatus::Sent => $document->sent_at ?? now(),
                DocumentStatus::Draft => null,
                default => $document->sent_at,
            },
            'paid_at' => $target === DocumentStatus::Paid ? now() : null,
        ])->save();

        return $document->refresh();
    }

    /**
     * Transforme un devis envoyé/accepté en facture brouillon (une seule facture par devis).
     */
    public function convertToInvoice(Document $quote): Document
    {
        if ($quote->type !== DocumentType::Quote) {
            throw new BusinessRuleException('Seul un devis peut être converti en facture.');
        }

        if ($quote->isDraft()) {
            throw new BusinessRuleException('Envoyez le devis avant de le convertir en facture.');
        }

        return DB::transaction(function () use ($quote) {
            // Verrou sur le devis pour empêcher une double conversion concurrente.
            $quote = Document::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if ($quote->invoice()->exists()) {
                throw new BusinessRuleException('Ce devis a déjà été converti en facture.');
            }

            $invoice = $this->create([
                'type' => DocumentType::Invoice->value,
                'client_id' => $quote->client_id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'notes' => $quote->notes,
                'lines' => $quote->lines->map(fn ($l) => [
                    'article_id' => $l->article_id,
                    'description' => $l->description,
                    'quantity' => $l->quantity,
                    'unit_price' => $l->unit_price,
                    'tax_rate' => $l->tax_rate,
                ])->all(),
            ], $quote->user);

            $invoice->update(['source_quote_id' => $quote->id]);

            return $invoice->load('sourceQuote');
        });
    }

    /**
     * Remplace les lignes du document et recalcule ses totaux.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(Document $document, array $lines): void
    {
        $articles = Article::withTrashed()
            ->whereIn('id', collect($lines)->pluck('article_id')->filter())
            ->get()
            ->keyBy('id');

        $document->lines()->delete();

        $rows = [];
        foreach (array_values($lines) as $i => $line) {
            $article = $articles->get($line['article_id'] ?? null);

            $quantity = (float) $line['quantity'];
            $unitPrice = (float) ($line['unit_price'] ?? $article?->unit_price ?? 0);
            $taxRate = (float) ($line['tax_rate'] ?? $article?->tax_rate ?? 0);

            $rows[] = [
                'article_id' => $article?->id,
                'position' => $i + 1,
                'description' => $line['description'] ?? $article?->name ?? '',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                ...$this->calculator->line($quantity, $unitPrice, $taxRate),
            ];
        }

        $document->lines()->createMany($rows);
        $document->update($this->calculator->totals($rows));
    }
}
