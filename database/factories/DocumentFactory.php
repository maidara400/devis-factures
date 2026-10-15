<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => DocumentType::Quote,
            'number' => fn (array $a) => $a['type']->prefix().'-'.now()->year.'-'.fake()->unique()->numerify('9####'),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'status' => DocumentStatus::Draft,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
        ];
    }

    public function invoice(): static
    {
        return $this->state(['type' => DocumentType::Invoice]);
    }

    public function status(DocumentStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    /**
     * Document créé via le service (numérotation + lignes + totaux réels).
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public static function createWithLines(
        DocumentType $type = DocumentType::Quote,
        ?array $lines = null,
        ?Client $client = null,
        ?User $user = null,
    ): Document {
        return app(DocumentService::class)->create([
            'type' => $type->value,
            'client_id' => ($client ?? Client::factory()->create())->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'lines' => $lines ?? [
                ['description' => 'Développement', 'quantity' => 2, 'unit_price' => 100000, 'tax_rate' => 18],
                ['description' => 'Hébergement', 'quantity' => 1, 'unit_price' => 50000, 'tax_rate' => 18],
            ],
        ], $user ?? User::factory()->create());
    }
}
