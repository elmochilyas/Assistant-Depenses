## 1. Foundation — Enums, Migrations, Models

- [ ] 1.1 Create `StatutRecu` enum at `app/Enums/StatutRecu.php` with cases `EnAttente`, `Traite`, `Echoue` and `label()` method
- [ ] 1.2 Create `CategorieDepense` enum at `app/Enums/CategorieDepense.php` with cases `Alimentaire`, `Boissons`, `Hygiene`, `Entretien`, `Autre` and `label()` method
- [ ] 1.3 Create migration for `recus` table with columns: `id`, `user_id` (FK), `texte_brut` (text), `statut` (string), `payload_ia` (nullable json), `message_erreur` (nullable text), timestamps
- [ ] 1.4 Create migration for `depenses` table with columns: `id`, `recu_id` (FK with cascade delete), `libelle` (string), `quantite` (integer), `prix_unitaire` (decimal), `categorie` (string), timestamps
- [ ] 1.5 Create `Recu` model with `$casts` (statut, payload_ia), `$fillable`, and relationships (`user()`, `depenses()`)
- [ ] 1.6 Create `Depense` model with `$casts` (categorie), `$fillable`, and relationship (`recu()`)
- [ ] 1.7 Add `recus()` relationship to `User` model

## 2. Queue Setup

- [ ] 2.1 Run `php artisan queue:table` to create jobs table migration
- [ ] 2.2 Update `.env.example` to document `QUEUE_CONNECTION=database`
- [ ] 2.3 Create `ExtraireDepensesDuRecu` Job stub at `app/Jobs/ExtraireDepensesDuRecu.php` with `handle()` that updates status to `traite` (placeholder for AI extraction)

## 3. Receipt CRUD — Backend

- [ ] 3.1 Create `StoreRecuRequest` at `app/Http/Requests/StoreRecuRequest.php` with validation rules: `texte_brut` required, string, min:10, max:10000
- [ ] 3.2 Create `RecuPolicy` at `app/Policies/RecuPolicy.php` with `view()` and `delete()` ownership checks
- [ ] 3.3 Create `RecuController` with `index()`, `create()`, `store()`, `show()`, `destroy()` methods — all scoped to authenticated user
- [ ] 3.4 Register receipt routes in `routes/web.php` (index, create, store, show, destroy) under auth middleware

## 4. Receipt CRUD — Views

- [ ] 4.1 Create `resources/views/recus/index.blade.php` — receipt list with truncated text, formatted status label, depense count, links to show/delete
- [ ] 4.2 Create `resources/views/recus/create.blade.php` — form with textarea for `texte_brut`
- [ ] 4.3 Create `resources/views/recus/show.blade.php` — full receipt text, status, depenses list, delete button

## 5. Expense Listing — Backend

- [ ] 5.1 Create `DepenseController` at `app/Http/Controllers/DepenseController.php` with `index()` that supports optional `categorie` filter, scoped to authenticated user
- [ ] 5.2 Register expense index route in `routes/web.php` under auth middleware

## 6. Expense Listing — Views

- [ ] 6.1 Create `resources/views/depenses/index.blade.php` — expense list with libelle, quantite, prix_unitaire, formatted categorie label, receipt link, and category filter dropdown

## 7. Tests

- [ ] 7.1 Write receipt creation test: authenticated user submits valid text, receipt created with `en_attente`, Job dispatched, redirect response
- [ ] 7.2 Write validation test: empty text and short text rejected, no Job dispatched
- [ ] 7.3 Write authorization test: user cannot view or delete another user's receipt
- [ ] 7.4 Write receipt deletion test: receipt and associated expenses deleted, other user's receipts unaffected
- [ ] 7.5 Write expense listing test: authenticated user sees own expenses, category filter works, invalid filter ignored
- [ ] 7.6 Run full test suite and verify all tests pass
