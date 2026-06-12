## Purpose

Define the asynchronous AI extraction workflow. Receipt text is sent to Groq via `laravel/ai` structured output, and the returned articles are persisted as typed expense records.

## Requirements

### Requirement: AI extraction runs asynchronously via a queued Job
The system SHALL process receipt text through an AI extraction Job that runs asynchronously on a Queue Worker.

- The Job SHALL be named `ExtraireDepensesDuRecu`
- The Job SHALL be dispatched after the receipt is stored, inside the HTTP request lifecycle
- The HTTP response SHALL NOT wait for the Job to complete
- The Queue connection SHALL be `database` (not `sync`)

#### Scenario: Job is dispatched after storing receipt
- **WHEN** an authenticated user submits valid receipt text
- **THEN** a receipt is created with status `en_attente`
- **AND** the `ExtraireDepensesDuRecu` Job is dispatched to the queue

### Requirement: Job uses laravel/ai structured output
The Job SHALL use the official `laravel/ai` SDK to call Groq with guaranteed structured output.

- The SDK SHALL be configured via `config/ai.php` with the Groq provider
- The structured output schema SHALL match the AI Extraction Contract:
  ```json
  {
    "articles": [
      {
        "libelle": "string",
        "quantite": "integer",
        "prix_unitaire": "number",
        "categorie": "alimentaire|boissons|hygiene|entretien|autre"
      }
    ],
    "total_estime": "number",
    "devise": "string"
  }
  ```
- The Job SHALL NOT use `Http::post()`, Guzzle, cURL or any raw HTTP client
- The Job SHALL validate and preserve the raw AI payload in `recus.payload_ia`

#### Scenario: Successful extraction creates typed expenses
- **WHEN** the Job processes a receipt and the AI returns valid structured output
- **THEN** the raw payload is stored in `recus.payload_ia`
- **AND** one `Depense` record is created per article with `libelle`, `quantite`, `prix_unitaire`, `categorie`
- **AND** the receipt status is updated to `traite`

### Requirement: Job handles extraction failures gracefully
The system SHALL detect extraction failures and update the receipt status to `echoue` with a readable error message.

- If the AI API is unreachable, the response does not match the schema, or any exception occurs, the receipt SHALL be marked `echoue`
- A readable error message SHALL be stored in `recus.message_erreur`
- No `Depense` records SHALL be created when the extraction fails
- The operation SHALL be wrapped in a database transaction

#### Scenario: Invalid AI response sets receipt to echoue
- **WHEN** the Job processes a receipt and the AI returns an invalid or malformed response
- **THEN** the receipt status is updated to `echoue`
- **AND** a readable error message is stored
- **AND** no Depense records are created

#### Scenario: API failure sets receipt to echoue
- **WHEN** the Job processes a receipt and the AI API is unreachable or returns an error
- **THEN** the receipt status is updated to `echoue`
- **AND** a readable error message is stored
- **AND** no Depense records are created

### Requirement: Job is idempotent
The system SHALL avoid creating duplicate expenses if the same Job is retried.

- Before processing, the Job SHALL check if the receipt is already `traite` and skip if so
- Extracted expenses SHALL be replaced safely if the Job re-runs on a failed receipt

#### Scenario: Already processed receipt is skipped
- **WHEN** the Job runs on a receipt with status `traite`
- **THEN** the Job exits without making any changes

### Requirement: Extraction agent extracts and normalizes receipt data
The system SHALL use an AI agent class that encapsulates the extraction prompt and schema.

- The agent SHALL be named `App\Ai\Agents\ExtracteurDepenses`
- The agent SHALL extract each purchased item from the receipt text
- The agent SHALL normalize product labels
- The agent SHALL infer the category from the allowed enum values only
- The agent SHALL preserve quantities as integers and unit prices as numeric values
- The agent SHALL return the estimated total and currency (e.g., `MAD`)

#### Scenario: Agent extracts articles and estimated total
- **WHEN** the agent receives a receipt text with multiple items
- **THEN** it returns a structured response with all articles, estimated total, and currency
