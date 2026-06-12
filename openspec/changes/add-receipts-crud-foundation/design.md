## Context

The application currently has authentication via Laravel Breeze but no receipt or expense infrastructure. Users can register and log in but cannot submit receipt text or view extracted expenses. This change builds the entire CRUD layer: database migrations, Eloquent models with enum casts, form request validation, authorization policy, controllers, and Blade views.

## Goals / Non-Goals

**Goals:**
- Create `recus` and `depenses` tables with foreign keys and cascade delete
- Implement `StatutRecu` and `CategorieDepense` PHP enums with Eloquent casts and `label()` display methods
- Build `Recu` and `Depense` models with typed relationships and `$casts` array
- Validate receipt submission through `StoreRecuRequest` (required, string, min:10, max:10000)
- Authorize all receipt/expense access via `RecuPolicy` and/or scoped queries
- Render receipt listing with `withCount('depenses')` and detail page with `with('depenses')`
- Render expense listing with category filter dropdown and `with('recu')`
- Dispatch `ExtraireDepensesDuRecu` Job after receipt creation (Job body is a stub — AI logic is separate)
- Set up database queue table migration

**Non-Goals:**
- AI extraction logic — the Job will dispatch but its handler is out of scope
- Expense editing or updating — only listing and filtering
- Receipt editing — only create, show, and delete
- Image upload — text-only input for now

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| **Enum location** | `app/Enums/` | AGENTS.md convention; keeps enums isolated from models |
| **Authorization mechanism** | Scoped queries in controllers (`auth()->user()->recus()->...`) + `RecuPolicy` for `view` and `delete` | Scoped queries are simplest for listing; policy provides reusable gate for show/delete with 404 on not-found |
| **Route structure** | `RecuController` with explicit methods (index, create, store, show, destroy); `DepenseController` with index only | RESTful; explicit methods over `resource()` for clarity |
| **Cascade delete** | Foreign key `ON DELETE CASCADE` on `recus.id` → `depenses.recu_id` | Simplest and safest; no risk of orphaned expenses |
| **Queue driver** | `database` | Required by AGENTS.md; `queue:table` migration included |
| **Receipt creation response** | Redirect to `recus.index` with `status` flash message | Standard Laravel practice; user sees pending receipt in list |
| **Status display** | `Recu` model accessor or computed attribute via enum `label()` | Keeps display logic in the enum, not the view |
| **Layout** | Extend `layouts.app` from Breeze | Consistent UI without writing a new layout |
| **Expense listing query** | Subquery: `Depense::whereHas('recu', fn($q) => $q->where('user_id', auth()->id()))` with `with('recu')` | Guarantees data isolation in a single query; N+1 safe |

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| User sees receipt from another user via URL manipulation | `RecuPolicy` `view` and `delete` methods check ownership; controller uses `Gate::authorize` or explicit `findOrFail` scoped to user |
| N+1 on receipt listing when showing expense count | Use `withCount('depenses')` on the query; verify with Debugbar |
| N+1 on expense listing when showing receipt text | Use `with('recu')` on the query |
| Queue worker not running leads to stuck "en_attente" receipts | Document `php artisan queue:work` requirement in setup; receipt status is visible to user |
| French enum labels inconsistent in views | Define `label()` on each enum and use it consistently in all views |
