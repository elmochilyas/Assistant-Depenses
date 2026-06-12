## Why

A fresh Laravel 13 installation exists with Breeze auth, Debugbar, Pest, and `laravel/ai`, but zero application code. The shop owner needs to paste supplier receipts and get structured expenses — but nothing exists to create, store, process, or display them. This change establishes the entire domain foundation in one cohesive pass.

## What Changes

- Create `StatutRecu` and `CategorieDepense` enums with Eloquent enum casts and French labels
- Create `recus` and `depenses` database tables with foreign keys and cascade deletes
- Create `Recu` and `Depense` Eloquent models with relationships and array/enum casts
- Add `recus()` and `depenses()` relationships to the existing `User` model
- Create `StoreRecuRequest` form request (texte_brut: required, string, min:10, max:10000)
- Create `RecuController` with full CRUD (index, create, store, show, destroy)
- Create `DepenseController` with index listing and optional `?categorie=` query filter
- Create `RecuPolicy` for ownership authorization
- Create `ExtraireDepensesDuRecu` Job for async AI extraction
- Create `ExtracteurDepenses` AI agent using `laravel/ai` structured output
- Create Blade views for receipt management and expense listing with category filter dropdown
- Add routes under the `auth` middleware group
- Write Pest tests covering all critical behaviors
- Configure `QUEUE_CONNECTION=database` for async processing

## User Stories

- **US2**: List receipts with formatted status and expense count
- **US3**: Submit receipt text and see immediate "Reçu en cours de traitement"
- **US4**: View receipt detail with source text and extracted expenses
- **US5**: Delete a receipt and its associated expenses
- **US6**: AI extraction guaranteed via `laravel/ai` structured output
- **US7**: Status evolves from `en_attente` → `traite` (success) or `echoue` (failure)
- **US8**: Filter expenses by category (`StatutRecu`, `CategorieDepense`)

## Capabilities

### New Capabilities
- `receipt-management`: Create, list, show, and delete receipts with status display and expense count
- `expense-tracking`: List and filter expenses by category, scoped to the authenticated user
- `ai-extraction`: Asynchronous receipt extraction via `laravel/ai` structured output with status lifecycle

### Modified Capabilities
- *(none — no existing specs)*

## Impact

- New files: enums, models, migrations, controllers, form request, policy, job, AI agent, Blade views, tests
- Modified files: `app/Models/User.php`, `routes/web.php`, `.env`
- New dependencies: none (all already installed: Breeze, Pest, Debugbar, `laravel/ai`)
- Database: two new tables (`recus`, `depenses`) via migrations
- Queue: must run `php artisan queue:work` for async extraction to complete

## Non-goals

- Image upload (future bonus feature)
- API endpoints (no REST/JSON API)
- Real-time notifications or events
- Receipt editing (update) — only create, show, list, delete
- Expense editing or deletion — view-only once extracted
