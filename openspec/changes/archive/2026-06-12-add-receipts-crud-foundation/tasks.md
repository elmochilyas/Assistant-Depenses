## 1. Foundation — Enums, Migrations, Models

- [x] 1.1 Create `StatutRecu` enum at `app/Enums/StatutRecu.php` with cases `EnAttente`, `Traite`, `Echoue` and `label()` method
- [x] 1.2 Create `CategorieDepense` enum at `app/Enums/CategorieDepense.php` with cases `Alimentaire`, `Boissons`, `Hygiene`, `Entretien`, `Autre` and `label()` method
- [x] 1.3 Create migration for `recus` table with columns: `id`, `user_id` (FK), `texte_brut` (text), `statut` (string), `payload_ia` (nullable json), `message_erreur` (nullable text), timestamps
- [x] 1.4 Create migration for `depenses` table with columns: `id`, `recu_id` (FK with cascade delete), `libelle` (string), `quantite` (integer), `prix_unitaire` (decimal), `categorie` (string), timestamps
- [x] 1.5 Create `Recu` model with `$casts` (statut, payload_ia), `$fillable`, and relationships (`user()`, `depenses()`)
- [x] 1.6 Create `Depense` model with `$casts` (categorie), `$fillable`, and relationship (`recu()`)
- [x] 1.7 Add `recus()` relationship to `User` model

## 2. Queue Setup

- [x] 2.1 Run `php artisan queue:table` to create jobs table migration (migration exists)
- [x] 2.2 Update `.env.example` to document `QUEUE_CONNECTION=database` (already present)
- [x] 2.3 Create `ExtraireDepensesDuRecu` Job at `app/Jobs/ExtraireDepensesDuRecu.php` with full AI extraction logic

## 3. Receipt CRUD — Backend

- [x] 3.1 Create `StoreRecuRequest` at `app/Http/Requests/StoreRecuRequest.php` with validation rules: `texte_brut` required, string, min:10, max:10000
- [x] 3.2 Create `RecuPolicy` at `app/Policies/RecuPolicy.php` with `view()` and `delete()` ownership checks
- [x] 3.3 Create `RecuController` with `index()`, `create()`, `store()`, `show()`, `destroy()` methods — all scoped to authenticated user
- [x] 3.4 Register receipt routes in `routes/web.php` (index, create, store, show, destroy) under auth middleware

## 4. Receipt CRUD — Views

- [x] 4.1 Create `resources/views/recus/index.blade.php` — receipt list with truncated text, formatted status label, depense count, links to show/delete
- [x] 4.2 Create `resources/views/recus/create.blade.php` — form with textarea for `texte_brut`
- [x] 4.3 Create `resources/views/recus/show.blade.php` — full receipt text, status, depenses list, delete button

## 5. Expense Listing — Backend

- [x] 5.1 Create `DepenseController` at `app/Http/Controllers/DepenseController.php` with `index()` that supports optional `categorie` filter, scoped to authenticated user
- [x] 5.2 Register expense index route in `routes/web.php` under auth middleware

## 6. Expense Listing — Views

- [x] 6.1 Create `resources/views/depenses/index.blade.php` — expense list with libelle, quantite, prix_unitaire, formatted categorie label, receipt link, and category filter dropdown

## 7. Tests

- [x] 7.1 Write receipt creation test: authenticated user submits valid text, receipt created with `en_attente`, Job dispatched, redirect response
- [x] 7.2 Write validation test: empty text and short text rejected, no Job dispatched
- [x] 7.3 Write authorization test: user cannot view or delete another user's receipt
- [x] 7.4 Write receipt deletion test: receipt and associated expenses deleted, other user's receipts unaffected
- [x] 7.5 Write expense listing test: authenticated user sees own expenses, category filter works, invalid filter ignored
- [x] 7.6 Run full test suite and verify all tests pass (42 passed, 110 assertions)
