schema: spec-driven

project: Assistant Dépenses

context: |
  ## Business Problem
  Small grocery-store owners receive many handwritten/informal supplier receipts
  (abbreviations, Darija). Manual spreadsheet entry is too slow, and there is
  no visibility on expenses by category. The app transforms raw pasted receipt
  text into structured, categorized expenses — without the user waiting on a
  frozen page.

  ## Target User
  A shop owner who registers, pastes supplier receipt text, launches AI
  extraction, sees immediate "pending" status, browses receipt status, views
  extracted expenses, filters by category, and deletes receipts.

  ## Tech Stack
  - Laravel (latest stable)
  - Groq API via `laravel/ai` SDK (no raw Http::post)
  - Laravel Queue + Worker (QUEUE_CONNECTION=database)
  - Laravel Breeze (authentication)
  - Pest (testing)
  - Laravel Debugbar (query debugging)
  - OpenSpec (spec management)
  - OpenCode (AI coding agent)

  ## Key Entities
  - User (hasMany Recu, hasMany Depense through Recu)
  - Recu (belongsTo User, hasMany Depense)
    - Fields: id, user_id, texte_brut, statut (enum), payload_ia (nullable json),
      message_erreur (nullable text), timestamps
  - Depense (belongsTo Recu, belongsTo User through Recu)
    - Fields: id, recu_id, libelle, quantite, prix_unitaire, categorie (enum), timestamps
  - Cascade: delete Recu deletes its Depenses

  ## Receipt Status Enum (StatutRecu)
  - en_attente → created, awaiting queue processing
  - traite → AI extraction succeeded, expenses stored
  - echoue → AI call failed or response was invalid
  - Each case has a label() method for French display

  ## Expense Category Enum (CategorieDepense)
  - alimentaire, boissons, hygiene, entretien, autre
  - Each case has a label() method for French display (e.g., hygiène)

  ## Non-negotiable Architecture Rules
  - Use StoreRecuRequest to validate receipt text before any AI work
  - Save receipt with en_attente status first
  - Dispatch ExtraireDepensesDuRecu Job asynchronously
  - Never call Groq synchronously from a controller
  - HTTP response must return immediately after Job dispatch
  - Use laravel/ai structured output with a defined schema
  - Store raw AI payload via Eloquent array cast
  - Use Eloquent enum casts for statut and categorie
  - Update status to traite on success, echoue on failure
  - Failures must store a readable error message (not raw API details)

  ## AI Extraction Contract
  Output must match this schema (non-negotiable):
    articles: [{ libelle: string, quantite: integer, prix_unitaire: number,
                 categorie: alimentaire|boissons|hygiene|entretien|autre }]
    total_estime: number
    devise: string

  ## Authorization & Data Isolation
  - All routes require authentication
  - Users can only access their own Recu and Depense records
  - Use policies, scoped queries, or explicit where clauses
  - Never trust route model binding alone without ownership check
  - For receipt listing: auth()->user()->recus()->...
  - For expense listing: auth()->user()->recus()->with('depenses')...

  ## Validation Rules
  - texte_brut: required, string, min:10, max:10000

  ## Testing Conventions
  - Framework: Pest
  - Never call real Groq API in tests; use Laravel AI fake
  - Must test: auth protection, receipt creation, validation errors,
    Job dispatching, successful extraction, failed extraction,
    receipt deletion with cascading expenses, category filtering,
    user data isolation
  - Use ->assertRedirect(), ->assertSessionHas(), Queue::fake(),
    Ai::fake() from Laravel AI SDK

  ## Performance Constraints
  - AI extraction must be fully async (Queue + Worker)
  - No synchronous AI calls in request lifecycle
  - Use eager loading (with(), withCount()) for all relationship displays
  - Verify zero N+1 queries with Laravel Debugbar

  ## AI-Assisted Workflow
  - AGENTS.md at root with permanent instructions
  - OpenSpec: feature by feature; proposal → delta specs → design → tasks
  - Review all artifacts before implementation
  - Minimum 3 documented feature specs in repository
  - All OpenSpec files versioned with Git
  - Commit messages follow pattern: <type>(ai): <description>
  - Feature branches: feature/recus-crud, feature/extraction-ia,
    feature/queue-traitement

rules:
  proposal:
    - Keep proposals under 500 words
    - Include "User Stories" section mapping to business needs
    - Include "Non-goals" section
    - Reference specific enum names (StatutRecu, CategorieDepense)
  design:
    - Always reference the AI extraction contract
    - Describe query strategy (eager loading) for each view
    - Show authorization boundary for each route
  tasks:
    - Each task must be scoped to one file or one logical concern
    - Break tasks into chunks of max 2 hours
    - Include test task for every feature task
