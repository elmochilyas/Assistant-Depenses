## Purpose

Allow authenticated users to list and filter their extracted expenses by category. Data isolation is enforced so users only see their own expenses.

## Requirements

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

### Requirement: User can filter expenses by category
The system SHALL allow the authenticated user to filter the expense list by selecting a category.

- The filter SHALL be a GET query parameter named `categorie` on the expense index route
- A `<select>` dropdown SHALL be displayed in the view with the following options:
  - "Toutes les catégories" (empty value, default)
  - Each `CategorieDepense` case with its `label()` as display text
- When no category is selected, all expenses SHALL be displayed
- When a valid category is selected, only expenses with that category SHALL be displayed
- Invalid or unrecognized category values SHALL be silently treated as "no filter" (show all)
- The selected category SHALL be preserved in the `<select>` element after form submission
- The filter SHALL be scoped to the authenticated user's expenses only

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
- **THEN** all their expenses are displayed (invalid value is ignored)
- **AND** the filter select shows "Toutes les catégories" as selected

#### Scenario: Filtered list respects user isolation
- **WHEN** an authenticated user visits the expense list with `categorie=alimentaire`
- **THEN** only their own expenses with that category are displayed
- **AND** no expenses belonging to other users appear in the results
