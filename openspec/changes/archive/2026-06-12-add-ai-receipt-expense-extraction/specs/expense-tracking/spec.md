## ADDED Requirements

### Requirement: User can list all their expenses
The system SHALL display a list of all expenses belonging to the authenticated user.

- Each expense SHALL show `libelle`, `quantite`, `prix_unitaire`, and the formatted `categorie` label
- The receipt associated with each expense SHALL be accessible (linked or displayed)
- The query SHALL use eager loading (`with('recu')`) to avoid N+1 queries
- Only expenses whose receipt belongs to the authenticated user SHALL be displayed

#### Scenario: Authenticated user sees all their expenses
- **WHEN** an authenticated user visits the expense list
- **THEN** they see all expenses associated with their receipts
- **AND** each expense shows libelle, quantite, prix_unitaire, and formatted category label

#### Scenario: Unauthenticated user is redirected to login
- **WHEN** an unauthenticated user tries to access the expense list
- **THEN** the user is redirected to the login page

#### Scenario: User never sees another user's expenses
- **WHEN** an authenticated user visits the expense list
- **THEN** no expenses belonging to other users are displayed

### Requirement: Expense list shows category summary sidebar
The system SHALL display a category summary section on the expense index page that shows aggregated totals per category alongside the expense list.

- A category summary section SHALL be displayed on the expense index page
- The summary SHALL show each `CategorieDepense` value with its count of expenses and total amount spent
- The summary SHALL be scoped to the authenticated user's expenses only
- The summary data SHALL be loaded via a single aggregated query, not per-row queries
- Each category in the summary SHALL link to the filtered expense list for that category
- The summary SHALL update its data when the category filter is applied

#### Scenario: Authenticated user sees category summary on expense list
- **WHEN** an authenticated user visits the expense index page
- **THEN** they see a category summary section showing each category with count and total
- **AND** the summary shows data scoped to their own expenses

#### Scenario: Category summary links are clickable
- **WHEN** an authenticated user clicks a category in the summary section
- **THEN** they are redirected to the expense list filtered by that category

#### Scenario: Category summary shows only selected category when filter is active
- **WHEN** an authenticated user filters expenses by a specific category
- **THEN** the category summary section shows only the selected category's aggregated data

### Requirement: User can filter expenses by category
The system SHALL allow the authenticated user to filter the expense list by selecting a category.

- The filter SHALL be a GET query parameter named `categorie`
- A `<select>` dropdown SHALL be displayed with all category options and "Toutes les catégories" default
- When no category is selected, all expenses SHALL be displayed
- When a valid category is selected, only expenses with that category SHALL be displayed
- Invalid category values SHALL be silently treated as no filter

#### Scenario: Default list without filter shows all expenses
- **WHEN** an authenticated user visits the expense list without a `categorie` parameter
- **THEN** all their expenses are displayed
- **AND** the filter select shows "Toutes les catégories" as selected

#### Scenario: Filter by valid category shows only matching expenses
- **WHEN** an authenticated user visits the expense list with `categorie=alimentaire`
- **THEN** only expenses with category `alimentaire` are displayed
- **AND** the filter select shows "Alimentaire" as selected

#### Scenario: Invalid category falls back to unfiltered
- **WHEN** an authenticated user visits the expense list with `categorie=invalid_value`
- **THEN** all their expenses are displayed
- **AND** the filter select shows "Toutes les catégories" as selected

#### Scenario: Filtered list respects user isolation
- **WHEN** an authenticated user visits the expense list with `categorie=alimentaire`
- **THEN** only their own expenses with that category are displayed
- **AND** no expenses belonging to other users appear in the results
