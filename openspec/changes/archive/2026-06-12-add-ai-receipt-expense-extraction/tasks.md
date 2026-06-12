## 1. Enums and Models

- [x] 1.1 Create `StatutRecu` enum with `EnAttente`, `Traite`, `Echoue` cases and `label()` method
- [x] 1.2 Create `CategorieDepense` enum with `Alimentaire`, `Boissons`, `Hygiene`, `Entretien`, `Autre` cases and `label()` method
- [x] 1.3 Create `Recu` model with fillable fields, enum cast for `statut`, `array` cast for `payload_ia`, and relationships (`belongsTo User`, `hasMany Depense`)
- [x] 1.4 Create `Depense` model with fillable fields, enum cast for `categorie`, and relationship (`belongsTo Recu`)
- [x] 1.5 Add `recus()` relationship to `User` model (hasMany)

## 2. Migrations

- [x] 2.1 Create `create_recus_table` migration with `user_id`, `texte_brut`, `statut`, `payload_ia`, `message_erreur`, and timestamps
- [x] 2.2 Create `create_depenses_table` migration with `recu_id`, `libelle`, `quantite`, `prix_unitaire`, `categorie`, and timestamps

## 3. Validation and Authorization

- [x] 3.1 Create `StoreRecuRequest` with rules: `texte_brut` required, string, min:10, max:10000; add French validation messages
- [x] 3.2 Create `RecuPolicy` with `view()` and `delete()` ownership checks

## 4. Receipt Controller and Views

- [x] 4.1 Create `RecuController` with `index`, `create`, `store`, `show`, `destroy` actions; use `withCount()` for index, `with()` for show
- [x] 4.2 Create `recus.index` Blade view with table showing truncated text, status badge, expense count, date, actions
- [x] 4.3 Create `recus.create` Blade view with textarea form for receipt text
- [x] 4.4 Create `recus.show` Blade view with full text, status badge, error message block (for `echoue`), and expenses table (for `traite`)

## 5. Expense Controller and Views

- [x] 5.1 Create `DepenseController` with `index` action supporting `categorie` query filter; use `whereHas('recu')` for ownership scope; eager load `recu`
- [x] 5.2 Create `depenses.index` Blade view with category filter dropdown, category summary sidebar, and expenses table
- [x] 5.3 Add `categories` action to `DepenseController` with single aggregated query grouped by `categorie`
- [x] 5.4 Create `depenses.categories` Blade view showing all categories with count and total

## 6. AI Extraction Agent

- [x] 6.1 Create `ExtracteurDepenses` agent extending `StructuredAnonymousAgent`, implementing `HasStructuredOutput` with the article extraction schema
- [x] 6.2 Define structured output schema: `articles` array with `libelle`, `quantite`, `prix_unitaire`, `categorie` (enum constraint), plus `total_estime` and `devise`

## 7. Queue Job

- [x] 7.1 Create `ExtraireDepensesDuRecu` Job with idempotency check (skip if `traite`)
- [x] 7.2 Implement `handle()`: call agent, wrap in `DB::transaction()`, store payload, delete existing expenses, create new ones, update status to `traite`
- [x] 7.3 Implement failure handling: catch `Throwable`, set status `echoue`, store readable error message

## 8. Routes

- [x] 8.1 Register `recus` resource route (except edit, update) under auth middleware
- [x] 8.2 Register `depenses.index` and `depenses.categories` GET routes under auth middleware

## 9. Queue Configuration

- [x] 9.1 Set `QUEUE_CONNECTION=database` in `.env`
- [x] 9.2 Run `php artisan queue:table` and `php artisan migrate` for jobs table

## 10. Tests

- [x] 10.1 Write receipt submission test: authenticated user creates receipt, Job dispatched, redirect with flash
- [x] 10.2 Write validation tests: empty text rejected, short text rejected, no Job dispatched
- [x] 10.3 Write authorization tests: cannot view another user's receipt, cannot delete another user's receipt
- [x] 10.4 Write extraction job test: fake AI response creates typed expenses, payload stored, status `traite`
- [x] 10.5 Write extraction failure test: fake AI exception sets status `echoue` with error message, no expenses created
- [x] 10.6 Write idempotency test: already `traite` receipt is skipped
- [x] 10.7 Write cascade deletion test: deleting receipt removes associated expenses
- [x] 10.8 Write expense listing tests: all expenses shown, category filter works, invalid category shows all, user isolation respected
- [x] 10.9 Write category overview tests: all categories shown (including zero), totals correct, data scoped to authenticated user
- [x] 10.10 Write category summary sidebar tests: summary shown on expense index, links clickable, selected category highlighted in summary

## 11. Final Verification

- [x] 11.1 Run full test suite and confirm all 50+ tests pass
- [x] 11.2 Verify no N+1 queries using Laravel Debugbar on receipt index, receipt show, and expense index pages
