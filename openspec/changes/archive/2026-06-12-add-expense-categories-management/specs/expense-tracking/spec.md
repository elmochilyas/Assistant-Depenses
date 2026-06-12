## ADDED Requirements

### Requirement: Expense list shows category summary sidebar

The system SHALL display a category summary section on the expense index page that shows aggregated totals per category alongside the expense list.

- A category summary section SHALL be displayed on the expense index page (e.g., sidebar, top banner, or inline block)
- The summary SHALL show each `CategorieDepense` value with its count of expenses and total amount spent
- The summary SHALL be scoped to the authenticated user's expenses only
- The summary data SHALL be loaded via a single aggregated query, not per-row queries
- Each category in the summary SHALL link to the filtered expense list for that category (using the existing `categorie` query parameter)
- The summary SHALL update when the category filter is applied (if filtered, only show summary data for the selected category)

#### Scenario: Authenticated user sees category summary on expense list

- **WHEN** an authenticated user visits the expense index page
- **THEN** they see a category summary section showing each category with count and total
- **AND** the summary shows data scoped to their own expenses

#### Scenario: Category summary links are clickable

- **WHEN** an authenticated user clicks a category in the summary section
- **THEN** they are redirected to the expense list filtered by that category

#### Scenario: Category summary updates when filter is applied

- **WHEN** an authenticated user filters expenses by a specific category
- **THEN** the category summary section highlights or focuses on the selected category's data
