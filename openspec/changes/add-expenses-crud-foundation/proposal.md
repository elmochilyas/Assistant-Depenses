## Why

Shop owners need visibility into extracted expenses by category with filtering and aggregated totals. Currently, expense CRUD (list, filter, category overview) has been implemented but needs a structured review against the official specs, and the category summary on the index page does not update when a category filter is applied.

## What Changes

- Review the existing `Depense` model, `DepenseController`, views, routes, and tests against the `expense-tracking` and `category-overview` specs
- Fix the category summary sidebar to filter its data when a category is selected (currently shows all categories even when filtered)
- Verify N+1 query prevention with eager loading
- Ensure all authorization scoping is correct
- Confirm cascade deletion works as required
- Create a feature branch `feature/depenses-crud` for all changes

## Capabilities

### New Capabilities

- No new capabilities — this change reviews and refines already-implemented expense tracking

### Modified Capabilities

- `expense-tracking`: The category summary sidebar must update when a category filter is applied (scoping summary data to the selected category)
- `category-overview`: Confirm zero-category display and aggregation queries match the spec

## Impact

- `app/Models/Depense.php` — minor review, no structural changes expected
- `app/Http/Controllers/DepenseController.php` — fix `buildCategorySummary()` to accept optional category filter
- `resources/views/depenses/index.blade.php` — no template changes expected (filtering handled in controller)
- `tests/Feature/DepenseTest.php` — potential new test for filtered summary behavior
- `routes/web.php` — no changes expected
- Branch: `feature/depenses-crud` (new)
