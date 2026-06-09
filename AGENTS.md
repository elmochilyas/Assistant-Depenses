# AGENTS.md — Assistant Dépenses

## 1. Project Overview

Assistant Dépenses is a Laravel application for small shop owners who need to transform raw supplier receipt text into structured expenses.

A user pastes the raw text of a supplier receipt. The application validates the input, stores the receipt, dispatches an asynchronous extraction Job, calls the official `laravel/ai` SDK with guaranteed structured output, and stores one typed expense record for each extracted item.

The application must remain simple, reliable, explainable and easy to demonstrate.

---

## 2. Core User Flow

The mandatory flow is:

1. An authenticated user pastes the raw text of a supplier receipt.
2. Laravel validates the text using `StoreRecuRequest`.
3. Laravel stores a `Recu` record with the status `en_attente`.
4. Laravel dispatches the `ExtraireDepensesDuRecu` Job.
5. The HTTP request ends immediately and the user sees:
   `Reçu en cours de traitement`.
6. A Queue Worker processes the Job asynchronously.
7. The Job calls the structured AI extraction agent through the official `laravel/ai` SDK.
8. The Job stores the raw AI payload in the receipt record.
9. The Job creates one typed `Depense` record per extracted article.
10. The Job updates the receipt status to `traite`.
11. If processing fails, the Job updates the receipt status to `echoue` and stores a clear error message.

The user must never wait for the AI response inside an HTTP request.

---

## 3. Mandatory Architecture Rules

These rules are not optional.

### Laravel AI SDK

* Use the official `laravel/ai` SDK.
* Do not call Groq directly with `Http::post()`, Guzzle, cURL or any custom HTTP client.
* Configure the AI provider through Laravel configuration and environment variables.
* Use guaranteed structured output with a defined schema.
* Preserve the raw structured AI payload in the database for traceability.

### Queue Processing

* AI extraction must run asynchronously.
* Use a dedicated Laravel Job named:

```bash
php artisan make:job ExtraireDepensesDuRecu
```

* Dispatch the Job only after the receipt has been validated and stored.
* Do not call the AI provider directly from a controller.
* The queue must be processed using:

```bash
php artisan queue:work
```

* Handle failures explicitly.
* A failed extraction must update the receipt status to `echoue`.

### Validation

* Use a dedicated Form Request named:

```bash
php artisan make:request StoreRecuRequest
```

* Validate receipt input before creating or dispatching any AI-related work.
* The raw receipt text must be:

  * required
  * a string
  * non-empty
  * constrained by a reasonable minimum and maximum length

### Typed Storage

* Use PHP enums and Eloquent enum casts.
* The `Recu` status must use an enum cast.
* The `Depense` category must use an enum cast.
* The raw AI payload must use an `array` cast.
* Do not use magic strings repeatedly throughout controllers and views.

### Relationships

* A `User` has many `Recu` records.
* A `Recu` belongs to one `User`.
* A `Recu` has many `Depense` records.
* A `Depense` belongs to one `Recu`.
* Deleting a receipt must also delete its associated expenses.

### Database Queries

* Avoid N+1 query problems.
* Use eager loading when relationships are displayed.
* Use `withCount('depenses')` when displaying the number of extracted expenses.
* Use `with('depenses')` for receipt detail pages.
* Verify query efficiency using Laravel Debugbar.

### Authorization

* Every receipt and expense must belong to the authenticated user.
* A user must never view, update or delete another user's receipts or expenses.
* Use policies, scoped queries or explicit authorization checks.
* Never trust route model binding alone without ownership verification.

---

## 4. Domain Model

### User

A user owns receipts.

Expected relationship:

```php
public function recus()
{
    return $this->hasMany(Recu::class);
}
```

### Recu

A receipt stores the raw text, its processing state, the raw AI payload and an optional error message.

Expected fields:

```text
id
user_id
texte_brut
statut
payload_ia nullable json
message_erreur nullable text
created_at
updated_at
```

Expected relationships:

```php
public function user()
{
    return $this->belongsTo(User::class);
}

public function depenses()
{
    return $this->hasMany(Depense::class);
}
```

### Depense

An expense represents one extracted item from a receipt.

Expected fields:

```text
id
recu_id
libelle
quantite
prix_unitaire
categorie
created_at
updated_at
```

Expected relationship:

```php
public function recu()
{
    return $this->belongsTo(Recu::class);
}
```

---

## 5. Required Enums

Create a receipt status enum similar to:

```php
enum StatutRecu: string
{
    case EnAttente = 'en_attente';
    case Traite = 'traite';
    case Echoue = 'echoue';
}
```

Create an expense category enum similar to:

```php
enum CategorieDepense: string
{
    case Alimentaire = 'alimentaire';
    case Boissons = 'boissons';
    case Hygiene = 'hygiene';
    case Entretien = 'entretien';
    case Autre = 'autre';
}
```

Each enum should expose a display label method for the interface:

```php
public function label(): string
{
    return match ($this) {
        self::EnAttente => 'En attente',
        self::Traite => 'Traité',
        self::Echoue => 'Échoué',
    };
}
```

Use the enum values for database storage and filtering.
Use the enum labels for user-facing display.

---

## 6. AI Structured Output Contract

The AI extraction schema must respect this contract:

```json
{
  "articles": [
    {
      "libellé": "string",
      "quantité": "integer",
      "prix_unitaire": "number",
      "catégorie": "enum: alimentaire | boissons | hygiène | entretien | autre"
    }
  ],
  "total_estimé": "number",
  "devise": "string"
}
```

The Laravel implementation may use ASCII property names internally when needed for maintainability:

```text
libelle
quantite
prix_unitaire
categorie
total_estime
devise
```

However, the meaning and allowed values must remain identical to the required contract.

The extraction agent must:

* extract each purchased item;
* normalize the product label;
* infer the category only from the allowed enum values;
* preserve quantities as integers;
* preserve unit prices as numeric values;
* calculate or extract the estimated total;
* return the currency, such as `MAD`;
* avoid adding commentary outside the structured output.

---

## 7. Receipt Status Lifecycle

Use the following state flow:

```text
en_attente
   ├── successful extraction → traite
   └── extraction failure    → echoue
```

Rules:

* Create a receipt with `en_attente`.
* Keep it as `en_attente` while waiting in the Queue.
* Set it to `traite` only after expenses are successfully stored.
* Set it to `echoue` when the AI call, validation of the structured result or database operation fails.
* Save a readable error message in `message_erreur`.
* Do not expose sensitive API details to the user interface.

---

## 8. Required Features

### Authentication

Implement:

* registration;
* login;
* logout.

### Receipt Management

Implement:

* list the authenticated user's receipts;
* display the formatted processing status;
* display the extracted expense count;
* submit raw receipt text;
* show receipt source text;
* show extracted expenses;
* delete a receipt with its associated expenses.

### AI Extraction

Implement:

* structured extraction through `laravel/ai`;
* asynchronous processing through a Queue Job;
* typed expense storage;
* raw payload storage;
* clear failure handling.

### Expense Tracking

Implement:

* list all expenses belonging to the authenticated user;
* show label, quantity, unit price and formatted category;
* filter by category.

---

## 9. Suggested Application Structure

Keep controllers thin.

Suggested structure:

```text
app/
├── Ai/
│   └── Agents/
│       └── ExtracteurDepenses.php
├── Enums/
│   ├── CategorieDepense.php
│   └── StatutRecu.php
├── Http/
│   ├── Controllers/
│   │   ├── RecuController.php
│   │   └── DepenseController.php
│   └── Requests/
│       └── StoreRecuRequest.php
├── Jobs/
│   └── ExtraireDepensesDuRecu.php
├── Models/
│   ├── Depense.php
│   ├── Recu.php
│   └── User.php
└── Policies/
    └── RecuPolicy.php
```

Extract reusable domain logic into a service only when this makes the code clearer.
Do not create unnecessary abstractions.

---

## 10. Controller Responsibilities

### RecuController

The receipt controller may:

* show the authenticated user's receipt list;
* show the receipt creation form;
* validate submitted data through `StoreRecuRequest`;
* create a receipt with status `en_attente`;
* dispatch `ExtraireDepensesDuRecu`;
* redirect immediately with a confirmation message;
* show an authorized receipt detail page;
* delete an authorized receipt.

The receipt controller must not:

* call Groq;
* wait for an AI response;
* parse AI output;
* create expense records from AI output;
* contain complex extraction logic.

### DepenseController

The expense controller may:

* list the authenticated user's expenses;
* apply an optional category filter;
* eager-load required receipt data.

The expense controller must not expose expenses belonging to another user.

---

## 11. Job Responsibilities

`ExtraireDepensesDuRecu` is responsible for the background extraction workflow.

Expected responsibilities:

1. Retrieve the receipt.
2. Confirm that the receipt can still be processed.
3. Call the structured AI extraction agent.
4. Validate and preserve the raw structured payload.
5. Create expense records inside a database transaction.
6. Update the receipt status to `traite`.
7. Catch failures or use the Job failure hook.
8. Update the receipt status to `echoue`.
9. Store a readable error message.

Use a database transaction when creating expenses and changing the final receipt status so partial data is not stored accidentally.

The Job should be idempotent where reasonably possible:

* avoid creating duplicate expenses if the same Job is retried;
* delete or replace previous extracted expenses safely before recreation when appropriate;
* do not process an already completed receipt unnecessarily.

---

## 12. Queue Configuration

Prefer a database-backed Queue for the initial project version.

Expected local commands may include:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work -v
```

Never use the synchronous Queue driver for the final implementation because it would hide the asynchronous behavior required by the project.

The application must work with:

```env
QUEUE_CONNECTION=database
```

---

## 13. Code Quality Rules

Follow these rules during implementation:

* Use Laravel conventions.
* Prefer readable code over clever code.
* Keep methods focused.
* Keep controllers thin.
* Use descriptive French domain names consistently where the project already uses French.
* Avoid duplicated logic.
* Use route names.
* Use validation messages when helpful.
* Use database transactions for extraction persistence.
* Use cascade deletion or an explicit safe deletion strategy.
* Do not add packages unless they solve a clear requirement.
* Do not implement bonus functionality before mandatory functionality works.
* Do not silently swallow exceptions.
* Do not expose API keys or secrets.
* Do not commit `.env`.

---

## 14. Security Rules

* Never commit API keys.
* Store the Groq API key in `.env`.
* Add required environment variable examples to `.env.example`.
* Validate all user input.
* Escape user-generated receipt text when rendering HTML.
* Scope all database queries to the authenticated user.
* Prevent access to receipts through guessed URLs.
* Do not display internal exception traces in production views.

---

## 15. Testing Expectations

Write tests for important behavior.

Mandatory or strongly recommended tests:

### Receipt Submission Test

Verify that:

* an authenticated user can submit valid receipt text;
* a receipt is created with status `en_attente`;
* an extraction Job is dispatched;
* the response redirects immediately.

### Validation Test

Verify that:

* empty receipt text is rejected;
* invalid input does not dispatch an extraction Job.

### Authorization Test

Verify that:

* a user cannot access another user's receipt;
* a user cannot delete another user's receipt.

### Extraction Job Test

Use the `laravel/ai` fake mechanism.

Verify that:

* no real Groq request is performed;
* structured articles create typed expense records;
* the raw payload is stored;
* the receipt becomes `traite`.

### Failure Test

Verify that:

* extraction failure changes the receipt status to `echoue`;
* a readable error message is stored.

### Query Performance Check

Use Debugbar manually during development to verify:

* the receipt listing does not create an N+1 query;
* the receipt detail page eager-loads expenses;
* the expense listing performs a controlled number of queries.

---

## 16. OpenSpec Workflow

Use OpenSpec before implementing each significant feature.

Minimum documented features:

```text
recus-crud
queue-traitement
extraction-ia
```

Optional additional specifications:

```text
authentification
depenses-filtrage
tests-extraction
upload-image-bonus
```

For each feature:

1. Create an OpenSpec proposal.
2. Define expected behavior and scenarios.
3. Define architecture decisions.
4. Review generated tasks.
5. Ask the coding agent to work in Plan mode.
6. Review the implementation plan.
7. Switch to Build mode.
8. Implement only the approved scope.
9. Run tests.
10. Review generated code manually.
11. Commit the code with an explicit AI mention.
12. Archive or sync the OpenSpec change according to the configured workflow.

Commit all OpenSpec files to the repository.

Do not bypass specifications by directly generating the entire project in one prompt.

---

## 17. Git Workflow

Use feature branches.

Required branches:

```text
feature/recus-crud
feature/extraction-ia
feature/queue-traitement
```

Additional branches may be used when helpful:

```text
feature/authentication
feature/depenses-filtrage
test/extraction-fake
docs/readme
```

Commit regularly.
The project requires at least 15 explicit commits with clear messages and a mention of AI usage.

Suggested commit format:

```text
<type>(ai): <clear description>
```

Examples:

```text
docs(ai): add AGENTS.md with mandatory architecture rules
chore(ai): initialize OpenSpec workflow for OpenCode
docs(ai): add receipt CRUD OpenSpec proposal
feat(ai): add receipt and expense models with enum casts
feat(ai): add authenticated receipt CRUD
feat(ai): dispatch receipt extraction job asynchronously
feat(ai): add structured Laravel AI extraction agent
feat(ai): persist typed expenses from structured AI output
fix(ai): mark receipts as failed when extraction throws an exception
feat(ai): add expense category filtering
perf(ai): eager load receipt expenses and remove N+1 queries
test(ai): fake Laravel AI extraction job response
docs(ai): document queue worker and Groq configuration
```

Do not use vague messages such as:

```text
update
fix
changes
done
```

---

## 18. Bonus Features

Only implement bonus features after all mandatory requirements work.

### Image Upload Bonus

Possible scope:

* upload a receipt image;
* store the file using Laravel Storage;
* validate file type and size;
* use a multimodal model through `laravel/ai`;
* preserve the existing asynchronous Queue architecture.

### Pest Testing Bonus

Prioritize this bonus before image upload.

Use the Laravel AI SDK fake so tests are:

* fast;
* deterministic;
* independent of Groq availability;
* free of external API costs.

---

## 19. Commands Frequently Used During Development

```bash
php artisan serve
php artisan queue:work -v
php artisan migrate
php artisan migrate:fresh --seed
php artisan test
php artisan route:list
php artisan tinker
```

Use Debugbar during manual verification.

Restart the Queue Worker after changing Job code:

```bash
php artisan queue:restart
php artisan queue:work -v
```

---

## 20. Definition of Done

A feature is complete only when:

* its OpenSpec artifacts exist;
* the implementation matches the specification;
* the code follows this `AGENTS.md`;
* authorization is verified;
* validation is implemented;
* relevant tests pass;
* query performance has been considered;
* generated code has been manually reviewed;
* the feature is committed with a clear AI-related message;
* the developer can explain why the architecture was chosen.

The goal is not only to generate working code.
The goal is to build a reliable Laravel application and understand every architectural decision.
