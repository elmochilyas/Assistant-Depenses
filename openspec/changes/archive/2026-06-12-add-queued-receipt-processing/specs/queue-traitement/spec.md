## ADDED Requirements

### Requirement: Queue worker processes extraction jobs asynchronously
The system SHALL process AI extraction jobs on a background queue worker using the database driver.

- The queue connection SHALL be configured as `database` in `config/queue.php`
- The queue table SHALL be created via `php artisan queue:table` migration
- The worker SHALL be started with `php artisan queue:work`
- Jobs SHALL be processed in FIFO order

#### Scenario: Queue table exists and worker runs
- **WHEN** the migration `queue:table` is run
- **THEN** a `jobs` table is created with columns for payload, attempts, reserved_at, available_at, created_at

#### Scenario: Worker processes queued jobs
- **WHEN** `php artisan queue:work` is running and a job is dispatched
- **THEN** the worker picks up the job and executes its `handle()` method

### Requirement: ExtraireDepensesDuRecu Job is dispatched after receipt creation
The system SHALL dispatch the `ExtraireDepensesDuRecu` Job immediately after storing a receipt with `en_attente` status.

- The Job SHALL receive the `Recu` model (serialized by ID)
- The Job SHALL be queued on the default queue connection
- The HTTP response SHALL NOT wait for the Job to complete

#### Scenario: Job dispatched synchronously in request
- **WHEN** an authenticated user submits valid receipt text
- **THEN** the receipt is created with status `en_attente`
- **AND** the `ExtraireDepensesDuRecu` Job is dispatched to the queue
- **AND** the HTTP response redirects immediately

### Requirement: Job processes receipt and calls AI extraction agent
The system SHALL implement the `ExtraireDepensesDuRecu` Job to retrieve the receipt, call the AI agent, and persist results.

- The Job SHALL load the receipt by ID
- The Job SHALL verify the receipt belongs to the authenticated user (passed via Job constructor)
- The Job SHALL skip processing if receipt status is already `traite`
- The Job SHALL instantiate and call `ExtracteurDepenses` agent with the receipt text
- The Job SHALL store the raw AI response in `recus.payload_ia`
- The Job SHALL create `Depense` records for each article in a database transaction
- The Job SHALL update receipt status to `traite` on success

#### Scenario: Successful extraction creates expenses and updates status
- **WHEN** the Job processes a receipt with valid AI response
- **THEN** the raw AI payload is stored in `recus.payload_ia`
- **AND** one `Depense` record is created per article with correct `libelle`, `quantite`, `prix_unitaire`, `categorie`
- **AND** the receipt status is updated to `traite`

#### Scenario: Already processed receipt is skipped
- **WHEN** the Job runs on a receipt with status `traite`
- **THEN** the Job exits without making any changes
- **AND** no AI call is made

#### Scenario: Job replaces existing expenses on retry of failed receipt
- **WHEN** the Job re-runs on a receipt with status `echoue`
- **THEN** existing `Depense` records for that receipt are deleted
- **AND** new expenses are created from the fresh AI response
- **AND** the receipt status is updated to `traite`

### Requirement: Job handles AI extraction failures gracefully
The system SHALL detect extraction failures and update the receipt status to `echoue` with a readable error message.

- If the AI API is unreachable, the receipt SHALL be marked `echoue`
- If the AI response does not match the schema, the receipt SHALL be marked `echoue`
- If any exception occurs during processing, the receipt SHALL be marked `echoue`
- A readable error message SHALL be stored in `recus.message_erreur`
- No `Depense` records SHALL be created when extraction fails
- The operation SHALL be wrapped in a database transaction

#### Scenario: API failure sets receipt to echoue
- **WHEN** the Job processes a receipt and the AI API is unreachable
- **THEN** the receipt status is updated to `echoue`
- **AND** a readable error message is stored in `message_erreur`
- **AND** no Depense records are created

#### Scenario: Invalid AI response sets receipt to echoue
- **WHEN** the Job processes a receipt and the AI returns an invalid response
- **THEN** the receipt status is updated to `echoue`
- **AND** a readable error message is stored
- **AND** no Depense records are created

#### Scenario: Exception during processing sets receipt to echoue
- **WHEN** an unexpected exception occurs during Job execution
- **THEN** the receipt status is updated to `echoue` via the `failed()` method
- **AND** a readable error message is stored
- **AND** no Depense records are created