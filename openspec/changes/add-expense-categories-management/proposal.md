## Why

Expenses are currently categorised via a static `CategorieDepense` enum, but there is no dedicated page to review spending grouped by category. Shop owners need visibility into how much they spend per category (alimentaire, boissons, hygiène, etc.) to make informed purchasing decisions. Category totals should be aggregated automatically from existing expense data without requiring manual effort.

## What Changes

- Add a dedicated category overview page showing each category with total count and total amount spent
- Aggregate expenses by category with estimated total per category
- Keep the existing `CategorieDepense` enum as the source of truth (categories are not user-managed; they are inferred by AI extraction)
- Show summary analytics on the expense list page (category breakdown)
- No changes to the AI extraction contract or the enum itself

## Capabilities

### New Capabilities

- `category-overview`: aggregated category summary page with total count and total estimated spend per category, scoped to the authenticated user

### Modified Capabilities

- `expense-tracking`: add category-specific analytics (totals, counts) to the expense list and the new overview page; update filtering to include a category summary sidebar or section

## Impact

- `app/Http/Controllers/DepenseController.php` – new method for category overview
- `app/Http/Controllers/` – possibly a new `CategorieController.php` or reuse `DepenseController`
- Routes – new route for category overview
- Views – new category overview Blade view; updates to expense index view
- No changes to models, migrations, enums, or the AI pipeline
- No new dependencies
