## Purpose

Provide a structured AI extraction agent using `laravel/ai` SDK to transform raw supplier receipt text into structured expense data.

## Requirements

### Requirement: ExtracteurDepenses agent uses laravel/ai structured output
The system SHALL provide an AI agent class that encapsulates the extraction prompt and schema using `laravel/ai` SDK.

- The agent SHALL be named `App\Ai\Agents\ExtracteurDepenses`
- The agent SHALL extend `StructuredAnonymousAgent` and implement `HasStructuredOutput`
- The agent SHALL define a JSON schema matching the AI Extraction Contract
- The agent SHALL extract each purchased item, normalize labels, infer category from allowed enum values only
- The agent SHALL preserve quantities as integers and unit prices as numeric values
- The agent SHALL return the estimated total and currency (e.g., `MAD`)

#### Scenario: Agent extracts articles and estimated total
- **WHEN** the agent receives a receipt text with multiple items
- **THEN** it returns a structured response with all articles, estimated total, and currency

#### Scenario: Agent maps categories to enum values
- **WHEN** the agent processes receipt text containing various products
- **THEN** each article's `categorie` is one of: `alimentaire`, `boissons`, `hygiene`, `entretien`, `autre`

### Requirement: laravel/ai SDK configured with Groq provider
The system SHALL configure the `laravel/ai` SDK to use Groq as the AI provider.

- The SDK SHALL be configured via `config/ai.php`
- The Groq API key SHALL be read from `GROQ_API_KEY` environment variable
- The model SHALL be configurable (e.g., `llama-3.3-70b-versatile`)

#### Scenario: AI SDK uses Groq configuration
- **WHEN** the ExtracteurDepenses agent is invoked
- **THEN** it calls Groq API via the laravel/ai SDK with the configured model
- **AND** no raw HTTP client (Http::post, Guzzle, cURL) is used
