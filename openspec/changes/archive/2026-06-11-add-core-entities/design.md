## Context

A fresh Laravel 13 installation with Breeze (auth), Pest, Debugbar, and `laravel/ai` but zero domain code. All entities (Recu, Depense), enums, migrations, controllers, views, the AI extraction job, and tests must be built from scratch. The application must process receipts asynchronously so the user never waits on a frozen page.

## Goals / Non-Goals

**Goals:**
- Enums (`StatutRecu`, `CategorieDepense`) with Eloquent enum casts and French labels
- Database schema for `recus` and `depenses` with foreign keys and cascade delete
- Eloquent models with relationships and array/JSON casts
- StoreRecuRequest validation (texte_brut: required, string, min:10, max:10000)
- Receipt CRUD (create, list, show, delete) scoped to authenticated user
- Expense listing with `?categorie=` query filter
- Asynchronous AI extraction via `ExtraireDepensesDuRecu` Job + `ExtracteurDepenses` agent using `laravel/ai` structured output
- Receipt status lifecycle: `en_attente` → `traite` / `echoue`
- RecuPolicy for authorization
- Pest tests for all critical behaviors using `Queue::fake()` and `Ai::fake()`
- Category filter: GET param validated against `CategorieDepense` enum, invalid values fall back to unfiltered

**Non-Goals:**
- Image upload (future bonus)
- Receipt editing (update)
- Expense editing or deletion
- REST API endpoints
- Real-time notifications

## Decisions

1. **One cohesive change vs split changes** — Chosen: one change. The domain entities, enums, migrations, and basic CRUD are tightly coupled. Splitting would create artificial dependencies between changes and block meaningful testing until all pieces exist. The `add-core-entities` change builds everything needed for the app to function end-to-end.

2. **Enum casts vs string columns** — Chosen: Eloquent enum casts. Ensures type safety from storage to display. `StatutRecu` and `CategorieDepense` use `string` backed enums stored as varchar in the database. This prevents magic strings scattered across controllers and views.

3. **RecuPolicy vs inline ownership checks** — Chosen: RecuPolicy with `view` and `delete` methods. Keeps authorization logic centralized and reusable. For `DepenseController`, the query is scoped via `whereHas('recu', fn => user_id)` directly since no separate DepensePolicy is needed (expenses are read-only).

4. **Category filter validation strategy** — Chosen: `CategorieDepense::tryFrom()` with silent fallback to unfiltered. Invalid query parameter values do not crash; they simply show all expenses. The select dropdown in the view always sends valid values, so this handles only manual URL manipulation.

5. **Eager loading strategy** — Chosen: `withCount('depenses')` for the receipt index list, `with('depenses')` for the receipt detail page, and `with('recu')` for the expense listing. Verified with Laravel Debugbar to guarantee zero N+1 queries.

6. **laravel/ai structured output vs raw Http::post** — Chosen: `laravel/ai`. The SDK validates the response schema at the SDK level, eliminating silent `json_decode` failures. If the AI returns malformed data or the API is unreachable, an exception is caught and the receipt transitions to `echoue` with a readable message.

7. **Database queue vs sync** — Chosen: `QUEUE_CONNECTION=database`. The sync driver would make the user wait for the AI response during the HTTP request, violating the core requirement. The database driver is simple, has zero external dependencies, and works out of the box.

8. **Cascade delete vs soft delete** — Chosen: cascade delete via foreign key `ON DELETE CASCADE`. Soft delete adds complexity without a clear need for this use case. The user explicitly wants to delete receipts and their expenses.

## Category Filter Design

**Route**: `GET /depenses?categorie={value}` (no change — existing route)

**Controller logic** in `DepenseController@index`:
```
$categorie = request()->query('categorie')
if $categorie is null or empty → show all expenses
if CategorieDepense::tryFrom($categorie) is null → show all expenses (invalid ignored)
else → where('categorie', $categorie)
```

**View**: `<select name="categorie">` with auto-submit via `onchange`. Options: "Toutes les catégories" (empty value) + each `CategorieDepense::case()` with `label()`. Selected value preserved via `@selected(request('categorie') === $cat->value)`.

**Authorization**: Query always scoped to `auth()->user()` via `Depense::whereHas('recu', fn($q) => $q->where('user_id', auth()->id()))`.

**Tests**:
1. Default unfiltered list shows all user expenses
2. Valid category filters correctly
3. Invalid category falls back to unfiltered
4. Second user's expenses never appear regardless of filter

## Risks / Trade-offs

- **[Risk] Queue worker stopped** → If `php artisan queue:work` is not running, receipts remain `en_attente` forever. Mitigation: documented in README and AGENTS.md. The status display makes it visible.
- **[Risk] AI API key missing or expired** → Extraction fails, receipt shows `echoue` with message. Mitigation: the Job handles this gracefully and stores a readable error.
- **[Risk] Large receipt text** → AI token limits could be hit. Mitigation: `StoreRecuRequest` sets a 10,000 character max, well within typical Groq quotas.
- **[Trade-off] Cascade delete** → No soft-delete recovery. Acceptable: the user story explicitly requires deletion without recovery.
- **[Trade-off] One big change** → More files to review at once. Acceptable: the alternative (many tiny interdependent changes) would require more context-switching and makes it harder to verify end-to-end behavior.
