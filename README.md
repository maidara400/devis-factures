# Devisio — Gestion de devis et factures

Application **Laravel 12** de gestion de devis et de factures : clients, catalogue d'articles, génération de **PDF**, workflow de statuts (brouillon → envoyé → payé), **numérotation automatique**, **export CSV**, envoi par e-mail via **file d'attente**, **rôles et permissions**, interface web moderne et **API REST versionnée et documentée (OpenAPI)**.

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white) ![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white) ![Tests](https://img.shields.io/badge/tests-43%20passed-brightgreen)

## Fonctionnalités

| Domaine | Détail |
|---|---|
| Clients & articles | CRUD, recherche, suppression logique (l'historique des documents est conservé) |
| Devis & factures | Lignes dynamiques, remise des champs depuis l'article, TVA par ligne, totaux HT / TVA / TTC |
| Numérotation | `DEV-2026-0001`, `FAC-2026-0001` : séquentielle, sans trou, par type et par année |
| Statuts | `draft → sent → paid` (pour un devis : « accepté »). Un document envoyé n'est plus modifiable |
| Conversion | Devis envoyé/accepté → facture brouillon (une seule facture par devis) |
| PDF | Mise en page soignée générée avec DomPDF (aperçu, téléchargement, pièce jointe d'e-mail) |
| E-mail | `SendDocumentJob` : génère le PDF, l'envoie au client puis passe le document en « envoyé » |
| Export CSV | Flux immédiat **ou** export en arrière-plan (`ExportDocumentsCsvJob`), filtrable, compatible Excel |
| Sécurité | Sanctum (jeton Bearer API) + session web, rate limiting, comptes désactivables, 3 rôles |
| API | `/api/v1`, ressources JSON, validation, pagination, documentation interactive **`/docs/api`** |

### Rôles et permissions

| Permission | Admin | Gestionnaire | Comptable |
|---|:-:|:-:|:-:|
| Voir clients / articles / documents | ✅ | ✅ | ✅ |
| Créer / modifier clients et articles | ✅ | ✅ | ❌ |
| Créer / modifier / envoyer / convertir documents | ✅ | ✅ | ❌ |
| Changer le statut (envoyé / payé) | ✅ | ✅ | ✅ |
| Supprimer un brouillon | ✅ | ✅ | ❌ |
| Export CSV | ✅ | ✅ | ✅ |
| Gérer les utilisateurs | ✅ | ❌ | ❌ |

Chaque permission est un *Gate* Laravel (`can:documents.manage`, `@can('clients.view')`…), définis à partir de l'enum `App\Enums\Role`.

## Compétences Laravel illustrées

- **Relations Eloquent** : `Client hasMany Document`, `Document hasMany DocumentLine`, `belongsTo` (client, auteur), auto-relation `sourceQuote` / `invoice`, `SoftDeletes`, scopes (`search`, `filter`), casts d'enums.
- **Transactions DB** : création/mise à jour d'un document (en-tête + lignes + totaux + numéro) atomique ; numérotation avec `SELECT … FOR UPDATE` dans la même transaction (un rollback ne consomme aucun numéro) ; conversion devis → facture avec verrou anti double conversion. Voir `app/Services/`.
- **Jobs / files d'attente** : `SendDocumentJob` (retries + backoff), `ExportDocumentsCsvJob` (suivi via la table `exports`).
- **PDF** : `barryvdh/laravel-dompdf`, vue Blade dédiée `resources/views/pdf/document.blade.php`.
- **Validation** : `FormRequest` partagés entre l'API et l'interface web, règles conditionnelles, messages et attributs en français.
- **Rôles / permissions** : enums + Gates + middleware `can:`, middleware `active`.
- **API documentée** : [Scramble](https://scramble.dedoc.co) génère l'OpenAPI depuis le code (FormRequests, Resources, PHPDoc).

## Installation

Prérequis : PHP ≥ 8.2, Composer, Node ≥ 20 (SQLite par défaut, aucun serveur de base de données à installer).

```bash
git clone https://github.com/maidara400/devis-factures.git
cd devis-factures

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # Windows : type nul > database\database.sqlite
php artisan migrate --seed              # schéma + comptes + données de démonstration

npm install
npm run build                           # ou `npm run dev` en développement
```

### Lancer l'application

```bash
php artisan serve                       # http://127.0.0.1:8000
php artisan queue:work                  # worker pour l'envoi d'e-mails et les exports
```

> `composer dev` lance tout d'un coup (serveur, worker, Vite), mais utilise `pail` qui nécessite `pcntl` (Linux/macOS/WSL).

Les e-mails sont écrits dans `storage/logs/laravel.log` (`MAIL_MAILER=log`). Configurez un vrai SMTP dans `.env` pour les envoyer.

### Comptes de démonstration

Mot de passe commun : `Password123` *(à changer hors démonstration — le seeder n'est pas exécuté pour les données en `production`)*

| E-mail | Rôle |
|---|---|
| `admin@example.com` | Administrateur |
| `ldiasse@groupeisi.com` | Administrateur (compte évaluateur) |
| `manager@example.com` | Gestionnaire |
| `comptable@example.com` | Comptable |

## Documentation de l'API

- Interface interactive (Stoplight) : **`/docs/api`**
- Spécification OpenAPI 3.1 : `/docs/api.json` — copie versionnée dans [`docs/openapi.json`](docs/openapi.json) (régénérer : `php artisan scramble:export --path=docs/openapi.json`)

### Exemple rapide

```bash
# 1. Authentification
curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"manager@example.com","password":"Password123"}'
# → { "token": "1|abc…", "token_type": "Bearer", "user": { … } }

# 2. Créer une facture
curl -s -X POST http://127.0.0.1:8000/api/v1/documents \
  -H "Accept: application/json" -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{
    "type": "invoice",
    "client_id": 1,
    "issue_date": "2026-10-15",
    "due_date": "2026-11-14",
    "lines": [
      {"description": "Audit", "quantity": 2, "unit_price": 100000, "tax_rate": 18}
    ]
  }'

# 3. Envoyer le document (job en file d'attente) / télécharger le PDF / exporter
curl -X POST http://127.0.0.1:8000/api/v1/documents/1/send -H "Authorization: Bearer <token>" -H "Accept: application/json"
curl -o facture.pdf http://127.0.0.1:8000/api/v1/documents/1/pdf -H "Authorization: Bearer <token>"
curl -o export.csv "http://127.0.0.1:8000/api/v1/documents-export?type=invoice&status=paid" -H "Authorization: Bearer <token>"
```

### Principaux endpoints (`/api/v1`)

| Méthode | Route | Description |
|---|---|---|
| POST | `/auth/login` · `/auth/logout` · GET `/auth/me` | Authentification par jeton |
| GET/POST/PUT/DELETE | `/clients`, `/articles` | CRUD (recherche `?search=`) |
| GET/POST/PUT/DELETE | `/documents` | Liste filtrable (`type`, `status`, `client_id`, `from`, `to`, `search`) |
| PATCH | `/documents/{id}/status` | Changer le statut (transitions contrôlées) |
| POST | `/documents/{id}/convert-to-invoice` | Devis → facture |
| GET | `/documents/{id}/pdf` | PDF |
| POST | `/documents/{id}/send` | Envoi e-mail asynchrone (202) |
| GET | `/documents-export` | Export CSV immédiat |
| POST/GET | `/exports`, `/exports/{id}`, `/exports/{id}/download` | Export CSV en arrière-plan |
| CRUD | `/users` | Gestion des utilisateurs (admin) |

Codes d'erreur : `401` non authentifié · `403` permission manquante · `422` validation ou règle métier (ex. modifier une facture envoyée) · `429` trop de requêtes.

## Tests

```bash
php artisan test
```

43 tests (unitaires et fonctionnels) couvrant l'authentification, les permissions, la numérotation, le rollback transactionnel, les transitions de statut, la conversion, le PDF, les jobs, l'export CSV, l'API et l'interface web.

Un modèle de pipeline GitHub Actions est fourni dans [`docs/github-actions-ci.yml`](docs/github-actions-ci.yml) : copiez-le dans `.github/workflows/ci.yml` pour exécuter les tests à chaque push.

## Architecture

```
app/
├── Enums/            Role, Permission, DocumentType, DocumentStatus
├── Exceptions/       BusinessRuleException (422 API / message flash web)
├── Http/
│   ├── Controllers/Api/V1/   API REST
│   ├── Controllers/Web/      Interface Blade
│   ├── Requests/             Validation (partagée API + web)
│   └── Resources/            Représentations JSON
├── Jobs/             SendDocumentJob, ExportDocumentsCsvJob
├── Mail/             DocumentMail (PDF en pièce jointe)
├── Models/           Client, Article, Document, DocumentLine, NumberSequence, Export, User
└── Services/         DocumentService (transactions), DocumentNumberGenerator,
                      DocumentCalculator, DocumentPdfService, DocumentCsvExporter
```

## Workflow Git

Le dépôt suit un **Git Flow** simplifié : `main` (stable) ← `develop` (intégration) ← `feature/*`. Chaque fonctionnalité est développée sur sa branche avec des commits atomiques ([Conventional Commits](https://www.conventionalcommits.org/)), puis fusionnée dans `develop` (`--no-ff`).

## Licence

MIT
