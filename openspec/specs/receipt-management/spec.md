## Purpose

Allow authenticated users to create, list, view, and delete their supplier receipts. Validation, authorization, and status tracking are enforced at the application layer.

## Requirements

### Requirement: User can create a receipt
The system SHALL allow an authenticated user to submit raw receipt text and create a receipt record with `en_attente` status.

- `texte_brut` SHALL be validated: required, string, min:10 characters, max:10000 characters
- The receipt SHALL be stored with `user_id` set to the authenticated user
- The receipt SHALL have `statut` = `StatutRecu::EnAttente`
- An `ExtraireDepensesDuRecu` Job SHALL be dispatched after the receipt is stored
- The HTTP response SHALL redirect immediately with a flash message, without waiting for the Job

#### Scenario: Valid receipt text creates receipt and dispatches job
- **WHEN** an authenticated user submits valid receipt text
- **THEN** a receipt is created with status `en_attente`
- **AND** the `ExtraireDepensesDuRecu` Job is dispatched
- **AND** the response redirects with a success flash message

#### Scenario: Empty receipt text is rejected
- **WHEN** an authenticated user submits empty receipt text
- **THEN** validation fails with an error for `texte_brut`
- **AND** no Job is dispatched

#### Scenario: Receipt text below minimum length is rejected
- **WHEN** an authenticated user submits receipt text shorter than 10 characters
- **THEN** validation fails with an error for `texte_brut`
- **AND** no Job is dispatched

#### Scenario: Unauthenticated user is redirected to login
- **WHEN** an unauthenticated user tries to access the receipt creation form
- **THEN** the user is redirected to the login page

### Requirement: User can list their receipts
The system SHALL display a list of all receipts belonging to the authenticated user.

- Each receipt SHALL show its truncated `texte_brut`, formatted `statut` label, and the count of extracted `depenses`
- The list SHALL use `withCount('depenses')` to avoid N+1 queries
- Only the authenticated user's receipts SHALL be displayed

#### Scenario: Authenticated user sees only their receipts
- **WHEN** an authenticated user visits the receipt list
- **THEN** they see only their own receipts with formatted status and expense count

#### Scenario: Receipt list shows zero N+1 queries
- **WHEN** the receipt list page is loaded
- **THEN** the number of executed queries SHALL be proportional to the page structure, not the number of receipts

### Requirement: User can view a single receipt
The system SHALL display the full receipt detail to the authenticated owner.

- The detail page SHALL show the full `texte_brut`, the formatted `statut` label, and the list of extracted `depenses` with `libelle`, `quantite`, `prix_unitaire`, and formatted `categorie`
- The page SHALL use `with('depenses')` to avoid N+1 queries
- If the receipt status is `echoue`, the `message_erreur` SHALL be displayed

#### Scenario: User views own receipt traite
- **WHEN** an authenticated user views one of their receipts with status `traite`
- **THEN** they see the full text, the status "Traité", and the list of extracted expenses

#### Scenario: User views own receipt echoue
- **WHEN** an authenticated user views one of their receipts with status `echoue`
- **THEN** they see the full text, the status "Échoué", and the error message

#### Scenario: User cannot view another user's receipt
- **WHEN** an authenticated user tries to view a receipt belonging to another user
- **THEN** a 403 or 404 response is returned

### Requirement: User can delete a receipt
The system SHALL allow an authenticated user to delete their own receipt and all associated expenses.

- Deleting a receipt SHALL cascade-delete all its `depenses` records
- Authorization SHALL be enforced via `RecuPolicy` or explicit ownership check

#### Scenario: User deletes own receipt
- **WHEN** an authenticated user deletes one of their receipts
- **THEN** the receipt is removed from the database
- **AND** all associated expenses are also removed
- **AND** the response redirects with a success flash message

#### Scenario: User cannot delete another user's receipt
- **WHEN** an authenticated user tries to delete a receipt belonging to another user
- **THEN** a 403 or 404 response is returned
