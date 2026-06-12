## 1. Dependencies & Configuration

- [x] 1.1 Install `laravel/ai` SDK via composer
- [x] 1.2 Publish `laravel/ai` config: `php artisan vendor:publish --tag=ai-config`
- [x] 1.3 Configure `config/ai.php` with Groq provider and model (e.g., `llama-3.3-70b-versatile`)
- [x] 1.4 Add `GROQ_API_KEY` to `.env` and `.env.example`
- [x] 1.5 Set `QUEUE_CONNECTION=database` in `.env`

## 2. Queue Setup

- [x] 2.1 Run `php artisan queue:table` to create jobs table migration
- [x] 2.2 Run `php artisan migrate` to create jobs table
- [ ] 2.3 Verify queue worker command: `php artisan queue:work -v`

## 3. AI Agent Implementation

- [x] 3.1 Create `app/Ai/Agents/ExtracteurDepenses.php` extending `StructuredAnonymousAgent`
- [x] 3.2 Implement `schema()` method matching AI Extraction Contract (articles, total_estime, devise)
- [x] 3.3 Implement `prompt()` method with extraction instructions for French receipt text
- [x] 3.4 Ensure categories map to `CategorieDepense` enum values only
- [x] 3.5 Agent schema validated via fake extraction job tests (ExtractionJobTest)

## 4. ExtraireDepensesDuRecu Job Implementation

- [x] 4.1 Create Job: `php artisan make:job ExtraireDepensesDuRecu`
- [x] 4.2 Implement `ShouldQueue` interface
- [x] 4.3 Constructor accepts `Recu $recu` (serialized by ID)
- [x] 4.4 Implement `handle()` method:
  - [x] 4.4.1 Load receipt with user relationship
  - [x] 4.4.2 Verify receipt ownership (user_id matches)
  - [x] 4.4.3 Skip if status is `StatutRecu::Traite`
  - [x] 4.4.4 Delete existing Depense records for this receipt
  - [x] 4.4.5 Call `ExtracteurDepenses` agent with `texte_brut`
  - [x] 4.4.6 Store raw AI response in `recus.payload_ia` (array cast)
  - [x] 4.4.7 Create Depense records in `DB::transaction()`
  - [x] 4.4.8 Update receipt status to `StatutRecu::Traite`
- [x] 4.5 Implement `failed(Throwable $e)` method:
  - [x] 4.5.1 Update receipt status to `StatutRecu::Echoue`
  - [x] 4.5.2 Store readable error message in `message_erreur`
- [x] 4.6 Add try/catch in `handle()` for expected AI failures (validation, API errors)

## 5. Controller Integration (RecuController)

- [x] 5.1 In `store()` method: dispatch `ExtraireDepensesDuRecu::dispatch($recu)` after saving receipt
- [x] 5.2 Ensure HTTP response redirects immediately (no waiting)
- [x] 5.3 Add flash message: "Reçu en cours de traitement"

## 6. Tests

- [x] 6.1 Test: Valid receipt creates receipt + dispatches Job (Queue::fake)
- [x] 6.2 Test: Empty/invalid receipt text rejected, no Job dispatched
- [x] 6.3 Test: Job successful extraction creates expenses + updates status (Ai::fake)
- [x] 6.4 Test: Job failure sets receipt to echoue + error message (Ai::fake with exception)
- [x] 6.5 Test: Job idempotency - already traite receipt is skipped
- [x] 6.6 Test: Job retries on echoue - replaces expenses
- [x] 6.7 Test: Authorization - user cannot process another user's receipt
- [ ] 6.8 N+1 query check with Debugbar on receipt list/detail

## 7. Verification

- [x] 7.1 Run `php artisan migrate:fresh --seed` and verify no errors
- [x] 7.2 Start queue worker: `php artisan queue:work -v`
- [x] 7.3 Submit a test receipt via UI, verify async processing
- [x] 7.4 Check receipt status transitions: en_attente → echoue (no GROQ_API_KEY configured)
- [x] 7.5 Verify no expenses created on failure (correct — AI call failed due to missing API key)
- [x] 7.6 Run Pest test suite: `php artisan test`