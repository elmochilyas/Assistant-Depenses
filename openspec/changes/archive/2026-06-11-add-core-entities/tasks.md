## 1. Foundation — Enums, Migrations, Models

- [x] 1.1 Create `app/Enums/StatutRecu.php` with cases `EnAttente`, `Traite`, `Echoue` and `label()` method
- [x] 1.2 Create `app/Enums/CategorieDepense.php` with cases `Alimentaire`, `Boissons`, `Hygiene`, `Entretien`, `Autre` and `label()` method
- [x] 1.3 Create migration for `recus` table: `user_id` (FK cascade), `texte_brut` (text), `statut` (string, default `en_attente`), `payload_ia` (nullable json), `message_erreur` (nullable text), timestamps
- [x] 1.4 Create migration for `depenses` table: `recu_id` (FK cascade), `libelle` (string), `quantite` (integer), `prix_unitaire` (decimal 10,2), `categorie` (string), timestamps
- [x] 1.5 Run `php artisan migrate`
- [x] 1.6 Create `app/Models/Recu.php` with `$fillable`, `$casts` (statut => StatutRecu, payload_ia => array), relationships (`user`, `depenses`)
- [x] 1.7 Create `app/Models/Depense.php` with `$fillable`, `$casts` (categorie => CategorieDepense), relationships (`recu`)
- [x] 1.8 Update `app/Models/User.php` with `recus()` hasMany relationship

## 2. Backend — Request, Policy, Controllers, Routes

- [x] 2.1 Create `app/Http/Requests/StoreRecuRequest.php` with rules: `texte_brut` required|string|min:10|max:10000
- [x] 2.2 Create `app/Policies/RecuPolicy.php` with `view()` and `delete()` ownership checks; register via `Gate::policy()`
- [x] 2.3 Create `app/Http/Controllers/RecuController.php` with `index` (withCount depenses), `create`, `store` (validates, creates, dispatches job, redirects), `show` (with depenses), `destroy` (authorize, delete)
- [x] 2.4 Create `app/Http/Controllers/DepenseController.php` with `index` accepting optional `?categorie=` query parameter, validated via `CategorieDepense::tryFrom()`, scoped to authenticated user with eager-loaded `recu`
- [x] 2.5 Add routes in `routes/web.php`: `Route::resource('recus', RecuController::class)->except(['edit', 'update'])` and `Route::get('depenses', [DepenseController::class, 'index'])->name('depenses.index')`, both under `auth` middleware
- [x] 2.6 Set `QUEUE_CONNECTION=database` in `.env` and create `jobs` table migration (already exists if `php artisan queue:table` was run)

## 3. AI Layer — Extraction Agent and Job

- [x] 3.1 Create `app/Ai/Agents/ExtracteurDepenses.php` using `laravel/ai` structured output with schema matching the AI contract (articles with libelle, quantite, prix_unitaire, categorie; total_estime; devise)
- [x] 3.2 Create `app/Jobs/ExtraireDepensesDuRecu.php` that: retrieves receipt, skips if already `traite`, calls extractor, stores raw payload in `payload_ia`, creates Depense records in a DB transaction, updates status to `traite`; catches failures and sets status to `echoue` with `message_erreur`
- [x] 3.3 Wire Job dispatch in `RecuController::store()` after successful receipt creation

## 4. Views — Blade Templates

- [x] 4.1 Create `resources/views/recus/index.blade.php` with table showing truncated `texte_brut`, formatted `statut` label, `depenses_count`, and action links (show, delete)
- [x] 4.2 Create `resources/views/recus/create.blade.php` with textarea for `texte_brut`, validation error display, and submit button
- [x] 4.3 Create `resources/views/recus/show.blade.php` showing full `texte_brut`, status badge, and list of `depenses` (libelle, quantite, prix_unitaire, categorie label); also display `message_erreur` if status is `echoue`
- [x] 4.4 Create `resources/views/depenses/index.blade.php` with filter `<form>` containing `<select name="categorie">` (options: "Toutes les catégories" + each CategorieDepense case with label), auto-submit on change, preserving selected value via `@selected`; expense table with libelle, quantite, prix_unitaire, categorie label, and receipt link
- [x] 4.5 Update `resources/views/layouts/navigation.blade.php` with navigation links to receipts index and expenses index

## 5. Tests — Pest Feature Tests

- [x] 5.1 Write receipt tests (`tests/Feature/RecuTest.php`): auth protection (redirect to login), creation with valid text (creates receipt en_attente + dispatches job), validation errors (empty, too short), show own receipt, delete own receipt, cannot view another user's receipt, cannot delete another user's receipt, deletion cascades to depenses
- [x] 5.2 Write expense filter tests (`tests/Feature/DepenseTest.php`): unauthenticated redirect, default unfiltered list shows all user expenses, valid category filter (`?categorie=alimentaire`) returns only matching, invalid category returns unfiltered, user isolation with filter applied
- [x] 5.3 Write extraction job tests (`tests/Feature/ExtractionJobTest.php`): fake AI success creates typed depenses + status traite + stores raw payload, fake AI failure sets status echoue + error message, already-processed receipt skipped
