## 1. Branch Setup

- [x] 1.1 Create and switch to `feature/depenses-crud` branch from `main`

## 2. Fix Category Summary Filtering

- [x] 2.1 Update `DepenseController::buildCategorySummary()` to accept an optional `?string $categorie` parameter and apply it to the aggregation query when provided
- [x] 2.2 Update `DepenseController::index()` to pass `request()->query('categorie')` to `buildCategorySummary()`

## 3. Tests

- [x] 3.1 Write a Pest test verifying that the category summary shows only the selected category's data when a filter is applied
- [x] 3.2 Write a Pest test verifying that the category summary shows all categories when no filter is active
- [x] 3.3 Run the full test suite and confirm all existing tests pass

## 4. Verification

- [x] 4.1 Verify no N+1 queries exist in `depenses.index` and `depenses.categories` views
- [x] 4.2 Commit all changes with message `fix(ai): scope category summary to selected filter on expense index`
