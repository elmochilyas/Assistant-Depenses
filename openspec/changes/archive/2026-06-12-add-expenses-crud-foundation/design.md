## Context

Expense tracking (list with category filter, category overview, category summary sidebar) has already been implemented in `DepenseController` and its views. A gap exists: the `buildCategorySummary()` method always returns all categories regardless of the applied filter, while the spec requires it to show only the selected category's data when a filter is active.

Current implementation stores all fetched data in `$categorySummary` and passes it to the view. The view already highlights the selected category. The fix is scoped to the controller's `index()` method.

## Goals / Non-Goals

**Goals:**
- Pass an optional `categorie` filter to `buildCategorySummary()`
- When a category is selected, only that category's summary data is aggregated
- When no category is selected (or invalid), all categories are shown as today
- Existing tests continue to pass
- Verify N+1 prevention is in place for all expense views

**Non-Goals:**
- No UI/layout changes to the views
- No changes to the category overview page (`categories()` method)
- No new routes or model changes
- No architectural changes to the Depense model or enums

## Decisions

| Decision | Rationale |
|---|---|
| Pass `$categorie` to `buildCategorySummary()` as a nullable parameter | Minimal change, no new service class needed, keeps controller thin |
| Filter the aggregation query with `when()` when category is set | Avoids conditional branching and keeps query builder fluent |
| Preserve existing fallback to show all categories when `$categorie` is null | Maintains backward compatibility; the `categories()` method already works correctly |
| No new policy for Depense (authorization is through Recu) | All expense queries are already scoped through `whereHas('recu', fn($q) => $q->where('user_id', auth()->id()))` — sufficient for data isolation |

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| Controller method gains a second responsibility (building filtered summary) | Acceptable — the method is private, focused, and only used in `index()`. Extracting to a service would be over-engineering for this scope |
| Filtered summary might show "0" for categories with no expenses when filtered | The spec says "if filtered, only show summary data for the selected category" — the summary will only contain the matching category |
