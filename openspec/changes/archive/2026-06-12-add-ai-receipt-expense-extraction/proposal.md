## Why

Small shop owners currently paste supplier receipts into the app but have no structured expense output. The app needs AI-powered extraction that runs asynchronously via a Queue Worker, uses `laravel/ai` structured output for guaranteed JSON shape, and stores typed `Depense` records per extracted article.

## What Changes

- Add `ExtracteurDepenses` agent using `laravel/ai` structured output with the defined article schema
- Add `ExtraireDepensesDuRecu` Job dispatched after receipt creation (async, database queue)
- Add `RecuController` for CRUD: index, create, store, show, destroy — with scoped ownership
- Add `DepenseController` for listing, filtering by category, and category overview page
- Add `StoreRecuRequest` validating `texte_brut` (required, string, min:10, max:10000)
- Add `RecuPolicy` enforcing user ownership on view and delete
- Add `StatutRecu` enum (`en_attente`, `traite`, `echoue`) with `label()` method
- Add `CategorieDepense` enum (`alimentaire`, `boissons`, `hygiene`, `entretien`, `autre`) with `label()` method
- Add Eloquent Models (`Recu`, `Depense`) with enum casts and relationships
- Add migrations for `recus` and `depenses` tables
- Add Blade views for receipt CRUD and expense listing with category filter
- Add `Category overview` page showing aggregated totals per category
- Add full Pest test suite covering: submission, validation, authorization, extraction job (with `laravel/ai` fake), failure handling, cascading deletion, category filtering, and query scoping
- Configure Groq provider in `config/ai.php` and set `QUEUE_CONNECTION=database`

## Capabilities

### New Capabilities

- `ai-extraction`: Structured AI extraction agent and async Job that converts receipt text into typed expense records
- `receipt-management`: Authenticated CRUD for supplier receipts with status tracking (`en_attente` → `traite` / `echoue`)
- `expense-tracking`: List and filter extracted expenses by category, with category summary sidebar
- `category-overview`: Aggregated view of expenses grouped by `CategorieDepense` with totals scoped to the authenticated user

### Modified Capabilities

- (none — first-pass implementation of all capabilities)

## Impact

- New controllers: `RecuController`, `DepenseController`
- New models: `Recu`, `Depense` with relationships to `User`
- New Job: `ExtraireDepensesDuRecu` (dispatched asynchronously)
- New AI agent: `ExtracteurDepenses` via `laravel/ai` SDK
- New Form Request: `StoreRecuRequest`
- New Policy: `RecuPolicy`
- New enums: `StatutRecu`, `CategorieDepense` with Eloquent casts
- New migrations: `create_recus_table`, `create_depenses_table`
- New views: `recus` (index, create, show), `depenses` (index, categories)
- New routes: `recus.*`, `depenses.*` under `auth` middleware
- Dependency: `laravel/ai` SDK for Groq integration
- Dependency: `QUEUE_CONNECTION=database` for async processing
- Dependency: `GROQ_API_KEY` in `.env`
