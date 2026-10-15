<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type', 'number', 'client_id', 'user_id', 'source_quote_id', 'status',
        'issue_date', 'due_date', 'notes', 'subtotal', 'tax_total', 'total', 'sent_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class)->orderBy('position');
    }

    public function sourceQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_quote_id')->withTrashed();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(self::class, 'source_quote_id');
    }

    public function isDraft(): bool
    {
        return $this->status === DocumentStatus::Draft;
    }

    public function isOverdue(): bool
    {
        return $this->type === DocumentType::Invoice
            && $this->status === DocumentStatus::Sent
            && $this->due_date?->isPast();
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['client_id'] ?? null, fn (Builder $q, $v) => $q->where('client_id', $v))
            ->when($filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('issue_date', '>=', $v))
            ->when($filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('issue_date', '<=', $v))
            ->when($filters['search'] ?? null, fn (Builder $q, $v) => $q->where(function (Builder $q) use ($v) {
                $q->where('number', 'like', "%{$v}%")
                    ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', "%{$v}%"));
            }));
    }
}
