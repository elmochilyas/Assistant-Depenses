## Context

Current state: The application has Laravel Breeze authentication but no receipt or expense management. Users need to paste supplier receipt text and get structured expense data back without waiting for AI processing.

## Goals / Non-Goals

**Goals:**
- Async AI extraction via Queue Job using `laravel/ai` structured output
- Receipt CRUD with status lifecycle (`en_attente` → `traite` / `echoue`)
- Expense listing with category filter and category overview
- Full data isolation per authenticated user
- Zero N+1 queries on all list pages

**Non-Goals:**
- Image upload / multimodal extraction (bonus feature)
- PDF or file attachment support
- Real-time status updates via WebSockets or polling
- Expense editing or update — only create (via AI) and delete (via receipt deletion)

## Decisions

1. **Asynchronous Job over synchronous AI call** — The controller creates the receipt and dispatches `ExtraireDepensesDuRecu` immediately. The user sees `en_attente` and gets a redirect. The Queue Worker processes the Job later. This prevents page freezes during the ~2-5s Groq API call.

2. **`laravel/ai` structured output over raw `Http::post()`** — The SDK guarantees the response matches the JSON schema. A malformed response raises a catchable exception rather than silently producing null fields. The `ExtracteurDepenses` agent extends `StructuredAnonymousAgent` and defines the schema via `JsonSchema`.

3. **Database queue over sync driver** — `QUEUE_CONNECTION=database` ensures the Job truly runs asynchronously. The `sync` driver would hide the async behavior and defeat the purpose.

4. **Eloquent enum casts over magic strings** — `StatutRecu` and `CategorieDepense` enums with `label()` methods eliminate stringly-typed status checks scattered across controllers and views. The enum values are stored in the database, and `label()` provides French display strings.

5. **`RecuPolicy` over inline `where('user_id', ...)` checks** — The policy enforces ownership on `view` and `delete` with a single `$recu->user_id === $user->id` check. Route model binding is used but the policy (via `$this->authorize()`) prevents cross-user access even if a user guesses another receipt's ID.

6. **Scope-based querying for expense list** — `Depense::whereHas('recu', fn($q) => $q->where('user_id', auth()->id()))` ensures the expense list never shows other users' data, regardless of URL manipulation.

7. **Eager loading strategy** — Receipt list uses `withCount('depenses')`; receipt detail uses `with('depenses')`; expense list uses `with('recu')`. This avoids N+1 on all views.

8. **Database transaction in Job** — The extraction Job wraps expense creation and status update in `DB::transaction()`. If any article creation fails, the entire operation rolls back and the receipt stays `en_attente` or gets marked `echoue`.

9. **Idempotent Job** — Before processing, the Job checks `$recu->statut === StatutRecu::Traite` and returns early. Existing expenses are deleted before recreation to handle retries cleanly.

## Risks / Trade-offs

- **Groq API latency** → Mitigated by async Job. The user never waits, but the receipt remains `en_attente` until the worker completes.
- **API key exposure in `.env`** → Mitigated by never committing `.env`. The gitignore must include it, and `.env.example` documents the required `GROQ_API_KEY` variable.
- **Queue worker not running** → If `php artisan queue:work` is not running, receipts stay `en_attente` indefinitely. Documented in README as a required step.
- **Structured output schema mismatch** → The `laravel/ai` SDK catches schema violations. The Job wraps this in a try-catch and sets status to `echoue` with a readable message.
- **Race condition on retry** → The idempotency check and delete-before-recreate pattern prevents duplicate expenses. The database transaction prevents partial writes.
