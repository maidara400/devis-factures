<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Article;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Database\Seeder;

/**
 * Jeu de données réaliste pour la démo (clients sénégalais, catalogue, devis/factures à divers statuts).
 */
class DemoDataSeeder extends Seeder
{
    public function run(DocumentService $service): void
    {
        if (Document::exists()) {
            return;
        }

        $author = User::where('email', 'manager@example.com')->firstOrFail();

        $articles = collect([
            ['WEB-001', 'Création site vitrine', 'Site responsive jusqu\'à 5 pages', 650000, 'forfait'],
            ['WEB-002', 'Développement application web', 'Développement sur mesure (Laravel)', 85000, 'jour'],
            ['DES-001', 'Identité visuelle', 'Logo, charte graphique et déclinaisons', 300000, 'forfait'],
            ['HEB-001', 'Hébergement annuel', 'Hébergement mutualisé + certificat SSL', 120000, 'an'],
            ['MNT-001', 'Maintenance mensuelle', 'Mises à jour, sauvegardes, support', 45000, 'mois'],
            ['FOR-001', 'Formation', 'Session de formation (6 heures)', 150000, 'session'],
        ])->map(fn ($a) => Article::create([
            'reference' => $a[0], 'name' => $a[1], 'description' => $a[2],
            'unit_price' => $a[3], 'tax_rate' => 18, 'unit' => $a[4], 'is_active' => true,
        ]));

        $clients = collect([
            ['Teranga Logistics SARL', 'contact@teranga-logistics.sn', 'Dakar', 'Plateau'],
            ['Baobab Café', 'hello@baobabcafe.sn', 'Dakar', 'Mermoz'],
            ['Sahel Énergie SA', 'achats@sahel-energie.sn', 'Thiès', 'Zone industrielle'],
            ['Cabinet Ndiaye & Associés', 'secretariat@ndiaye-avocats.sn', 'Dakar', 'Point E'],
            ['Kër Gui Hôtel', 'direction@kergui.sn', 'Saly', 'Route de la Corniche'],
        ])->map(fn ($c) => Client::create([
            'name' => $c[0], 'email' => $c[1], 'phone' => '+221 77 '.fake()->numerify('### ## ##'),
            'city' => $c[2], 'address' => $c[3], 'country' => 'Sénégal', 'tax_number' => fake()->numerify('00#######'),
        ]));

        // [type, client idx, jours depuis aujourd'hui, statut, lignes (idx article, quantité)]
        $plan = [
            ['invoice', 0, -150, 'paid', [[0, 1], [3, 1]]],
            ['invoice', 1, -120, 'paid', [[2, 1]]],
            ['invoice', 2, -90, 'paid', [[1, 12]]],
            ['invoice', 3, -60, 'paid', [[5, 2], [4, 3]]],
            ['invoice', 0, -45, 'sent', [[4, 6]]],          // en retard
            ['invoice', 4, -20, 'sent', [[0, 1], [2, 1]]],
            ['invoice', 1, -5, 'draft', [[3, 1], [4, 12]]],
            ['quote', 2, -30, 'sent', [[1, 20]]],
            ['quote', 4, -10, 'paid', [[0, 1], [4, 12]]],    // accepté
            ['quote', 3, -2, 'draft', [[5, 3]]],
        ];

        foreach ($plan as [$type, $clientIdx, $offset, $status, $lines]) {
            $date = now()->addDays($offset);

            $document = $service->create([
                'type' => $type,
                'client_id' => $clients[$clientIdx]->id,
                'issue_date' => $date->toDateString(),
                'due_date' => $date->copy()->addDays(30)->toDateString(),
                'notes' => $type === 'invoice' ? 'Merci pour votre confiance.' : 'Devis valable 30 jours.',
                'lines' => collect($lines)->map(fn ($l) => [
                    'article_id' => $articles[$l[0]]->id, 'quantity' => $l[1],
                ])->all(),
            ], $author);

            if ($status !== 'draft') {
                $document = $service->changeStatus($document, DocumentStatus::Sent);
                $document->forceFill(['sent_at' => $date])->save();
            }
            if ($status === 'paid') {
                $document = $service->changeStatus($document, DocumentStatus::Paid);
                $document->forceFill(['paid_at' => $date->copy()->addDays(rand(3, 20))])->save();
            }
        }
    }
}
