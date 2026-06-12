## ADDED Requirements

### Requirement: User can view category overview with aggregated totals
The system SHALL provide a dedicated category overview page that aggregates expenses by `CategorieDepense` for the authenticated user.

- The page SHALL display each `CategorieDepense` value with its French label (via `label()`)
- For each category, the page SHALL display the total number of expenses (`COUNT`)
- For each category, the page SHALL display the total estimated amount spent, calculated as `SUM(prix_unitaire * quantite)` across all expenses in that category
- All `CategorieDepense` values SHALL be displayed, including categories with zero expenses (shown with count 0 and total 0)
- The query SHALL be a single aggregation query grouped by `categorie`
- The query SHALL be scoped to the authenticated user's expenses only
- The page SHALL be accessible at a dedicated route (e.g., `GET /depenses/categories`)
- Categories with zero expenses SHALL be clearly distinguishable

#### Scenario: Authenticated user sees all categories with totals
- **WHEN** an authenticated user visits the category overview page
- **THEN** they see every `CategorieDepense` value with its French label
- **AND** each category shows the count of expenses and total amount spent
- **AND** the data is scoped to their own expenses only

#### Scenario: Category with no expenses shows zero totals
- **WHEN** an authenticated user has no expenses in the "hygiène" category
- **THEN** the category overview still displays "Hygiène" with count 0 and total 0,00 MAD

#### Scenario: Unauthenticated user is redirected to login
- **WHEN** an unauthenticated user tries to access the category overview page
- **THEN** the user is redirected to the login page
