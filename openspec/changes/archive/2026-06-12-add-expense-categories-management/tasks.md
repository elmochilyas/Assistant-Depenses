## 1. Category Overview Route and Controller

- [x] 1.1 Add `GET /depenses/categories` route in `routes/web.php` pointing to `DepenseController@categories`
- [x] 1.2 Implement `DepenseController@categories` method: query aggregated expenses grouped by `categorie`, merge with `CategorieDepense::cases()` for zero-count categories, return view with data

## 2. Category Overview View

- [x] 2.1 Create `resources/views/depenses/categories.blade.php` displaying a table: category label, expense count, total amount formatted in MAD
- [x] 2.2 Ensure all `CategorieDepense` values render (including empty ones with "0" and "0,00 MAD")

## 3. Category Summary on Expense Index

- [x] 3.1 Add a scoped aggregation query to `DepenseController@index` to build category summary data
- [x] 3.2 Modify `resources/views/depenses/index.blade.php` to include a category summary section (sidebar or inline block) with links to filtered views
- [x] 3.3 When a category filter is active, highlight the selected category in the summary

## 4. Tests

- [x] 4.1 Test that authenticated user can view category overview with all categories displayed
- [x] 4.2 Test that categories with zero expenses still appear with 0 count and 0 total
- [x] 4.3 Test that unauthenticated user is redirected to login for category overview page
- [x] 4.4 Test that category summary appears on expense index page
- [x] 4.5 Test that category summary links correctly filter expenses
- [x] 4.6 Test that query count is minimal (no N+1) on category overview page

## 5. Verify and Clean Up

- [x] 5.1 Run `php artisan route:list` to confirm new route is registered
- [x] 5.2 Run `php artisan test` to confirm all existing and new tests pass
- [x] 5.3 Manually verify with Laravel Debugbar that no N+1 queries occur
- [x] 5.4 Commit with message: `feat(ai): add expense category management with overview and summary`
