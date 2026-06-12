## Why

The application needs asynchronous AI extraction of receipt text to avoid blocking the user's HTTP request. Currently, the specs define the AI extraction workflow (`ai-extraction` spec) but the queued Job implementation (`ExtraireDepensesDuRecu`), the structured AI agent (`ExtracteurDepenses`), and the queue configuration (`QUEUE_CONNECTION=database`) need to be built. This change implements the queue-traitement capability so receipt processing happens in the background.

## What Changes

- Create `ExtraireDepensesDuRecu` Job for asynchronous AI extraction
- Create `ExtracteurDepenses` AI agent using `laravel/ai` SDK with structured output
- Configure `laravel/ai` SDK with Groq provider in `config/ai.php`
- Set up database queue with migrations and worker command
- Implement Job failure handling: update receipt to `echoue` with readable error
- Implement Job idempotency: skip already `traite` receipts, safely replace expenses on retry
- Wrap extraction persistence in database transaction
- Store raw AI payload in `recus.payload_ia` (array cast)
- Use Eloquent enum casts for `StatutRecu` and `CategorieDepense`

## Capabilities

### New Capabilities

- `queue-traitement`: Asynchronous queue worker setup, Job dispatching, and failure handling for AI extraction

### Modified Capabilities

- `ai-extraction`: The existing spec defines the requirements; this change implements the Job, agent, and queue infrastructure to satisfy those requirements

## Impact

- **New files**: `app/Jobs/ExtraireDepensesDuRecu.php`, `app/Ai/Agents/ExtracteurDepenses.php`, `config/ai.php`
- **Database**: Queue table migration (`php artisan queue:table`), receipt/expense models already exist
- **Configuration**: `.env` needs `GROQ_API_KEY`, `QUEUE_CONNECTION=database`
- **Commands**: `php artisan queue:work` for running the worker
- **Dependencies**: `laravel/ai` SDK must be installed