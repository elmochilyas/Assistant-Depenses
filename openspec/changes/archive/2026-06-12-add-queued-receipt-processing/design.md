## Context

The proposal establishes the need for queued AI extraction. The `ai-extraction` spec already defines the requirements: a Job named `ExtraireDepensesDuRecu` that runs asynchronously, uses `laravel/ai` SDK with structured output, handles failures gracefully, and is idempotent. This design details the implementation approach for the Job, AI agent, queue configuration, and database transaction handling.

## Goals / Non-Goals

**Goals:**
- Implement `ExtraireDepensesDuRecu` Job with full async processing
- Create `ExtracteurDepenses` agent using `laravel/ai` StructuredAnonymousAgent
- Configure `laravel/ai` SDK with Groq provider
- Set up database queue with migrations
- Implement failure handling: mark receipt `echoue` with readable error
- Implement idempotency: skip `traite` receipts, safely replace expenses on retry
- Wrap extraction in database transaction
- Store raw AI payload in `recus.payload_ia`

**Non-Goals:**
- Image upload / multimodal extraction (bonus feature)
- Synchronous AI calls
- Custom HTTP clients for Groq
- Real-time status updates via WebSockets/polling (UI shows status on refresh)

## Decisions

### 1. Job Class Structure: `ExtraireDepensesDuRecu`

**Decision**: Use a dedicated Job class implementing `ShouldQueue` with `handle()` method.

**Rationale**: Laravel's queued Jobs are the standard pattern for async work. The Job receives the `Recu` model via constructor (serialized by ID), ensuring the latest data is loaded when the Job runs.

**Alternatives considered**:
- Closure-based dispatch: Less testable, harder to retry
- Synchronous service class: Violates async requirement

### 2. AI Agent: `ExtracteurDepenses` extending `StructuredAnonymousAgent`

**Decision**: Create an agent class in `App\Ai\Agents\ExtracteurDepenses` implementing `HasStructuredOutput`.

**Rationale**: The `laravel/ai` SDK's `StructuredAnonymousAgent` provides guaranteed structured output with schema validation. The agent encapsulates the prompt and schema, making it testable and swappable.

**Schema definition**: Use PHP typed array schema matching the AI Extraction Contract:
```php
public function schema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'articles' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'libelle' => ['type' => 'string'],
                        'quantite' => ['type' => 'integer'],
                        'prix_unitaire' => ['type' => 'number'],
                        'categorie' => ['type' => 'string', 'enum' => ['alimentaire', 'boissons', 'hygiene', 'entretien', 'autre']],
                    ],
                    'required' => ['libelle', 'quantite', 'prix_unitaire', 'categorie'],
                ],
            ],
            'total_estime' => ['type' => 'number'],
            'devise' => ['type' => 'string'],
        ],
        'required' => ['articles', 'total_estime', 'devise'],
    ];
}
```

### 3. Queue Configuration: Database Driver

**Decision**: Use `QUEUE_CONNECTION=database` with `php artisan queue:table` migration.

**Rationale**: Database queue is simple, requires no external infrastructure (Redis), and works for development and small-scale production. The `database` driver persists jobs reliably.

### 4. Failure Handling: `failed()` method + try/catch in `handle()`

**Decision**: Implement both the `failed()` method (for exceptions thrown during `handle()`) and explicit try/catch in `handle()` for expected AI failures.

**Rationale**: 
- `failed()` catches unexpected exceptions (DB errors, etc.)
- Explicit try/catch handles expected AI failures (invalid response, API errors) with custom error messages
- Both paths update receipt to `echoue` with readable message

### 5. Idempotency: Check status at start, delete existing expenses before insert

**Decision**: 
1. At Job start: if `rec->statut === StatutRecu::Traite`, return early
2. Before creating expenses: delete existing `Depense` records for this receipt
3. Wrap in `DB::transaction()`

**Rationale**: 
- Skipping `traite` prevents duplicate processing
- Deleting before insert handles retries on `echoue` receipts
- Transaction ensures atomicity: either all expenses + status update succeed, or nothing

### 6. AI Payload Storage: Eloquent `array` cast on `Recu::$payload_ia`

**Decision**: Store the raw structured output from the agent directly in `payload_ia` (JSON column).

**Rationale**: The `array` cast automatically handles JSON encoding/decoding. Preserves full traceability for debugging.

### 7. Authorization in Job: Verify ownership before processing

**Decision**: In `handle()`, verify `$rec->user_id === auth()->id()` (or pass user_id to Job).

**Rationale**: Defense in depth. Controller already checks authorization, but Job should verify before mutating data.

### 8. Prompt Engineering: Structured prompt in agent's `prompt()` method

**Decision**: Embed extraction instructions in the agent class, requesting French category labels mapped to enum values.

**Rationale**: Keeps prompt with schema, single source of truth. Agent returns categories as enum keys (`alimentaire`, not `Alimentaire`).

## Risks / Trade-offs

| Risk | Mitigation |
|------|------------|
| Groq API rate limits / downtime | Job retries via queue; `echoue` status with readable error; user can re-submit |
| Invalid AI response schema | Structured output validation in SDK; catch validation exceptions; mark `echoue` |
| Duplicate expenses on Job retry | Idempotency: delete existing expenses before insert; skip if already `traite` |
| Queue worker not running | Documentation: `php artisan queue:work` required; monitor with `queue:failed` |
| Large receipt text exceeding token limits | Validation: `texte_brut` max 10000 chars; agent prompt asks for concise extraction |
| N+1 queries in UI | Specs require `withCount('depenses')` and `with('depenses')`; verify with Debugbar |