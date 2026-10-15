<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\NumberSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Numérotation séquentielle sans trou, par type et par année : DEV-2026-0001, FAC-2026-0001...
 *
 * À appeler DANS la transaction de création du document : la ligne de séquence est verrouillée
 * (SELECT ... FOR UPDATE) jusqu'au commit, et un rollback ne « brûle » donc aucun numéro.
 */
class DocumentNumberGenerator
{
    public function next(DocumentType $type, CarbonInterface $date): string
    {
        return DB::transaction(function () use ($type, $date) {
            $year = (int) $date->format('Y');

            NumberSequence::query()->insertOrIgnore([
                'type' => $type->value,
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = NumberSequence::query()
                ->where('type', $type->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            return sprintf('%s-%d-%04d', $type->prefix(), $year, $sequence->last_number);
        });
    }
}
