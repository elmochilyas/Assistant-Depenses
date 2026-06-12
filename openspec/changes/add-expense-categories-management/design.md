## Context

Currently, users can view and filter expenses by category via the expense list page. However, there is no dedicated view that aggregates spending per category. The `CategorieDepense` enum is the single source of truth for categories, and categories are inferred by the AI extraction agent, not managed by users. This design adds a category overview page and integrates category summary data into the existing expense list without changing the data model, the enum, or the AI pipeline.

## Goals / Non-Goals

**Goals:**
- Provide a dedicated category overview page showing each `CategorieDepense` value with total expense count and total amount spent
- Add a category summary section (sidebar or inline) to the expense index page
- Keep all queries scoped to the authenticated user's data only
- Use eager loading and aggregation queries to avoid N+1

**Non-Goals:**
- Allow users to create, edit, or delete categories (categories remain enum-driven)
- Change the `CategorieDepense` enum or its values
- Modify the AI extraction contract or the `ExtraireDepensesDuRecu` Job
- Add new database migrations or model changes

## Decisions

| Decision | Rationale |
|----------|-----------|
| **Reuse `DepenseController`** for category overview rather than creating a separate controller | Avoids unnecessary abstraction; the controller already handles expense-related views and can expose a `categories()` method. Consistent with "keep controllers thin, not proliferate them." |
| **Use Eloquent `selectRaw` with `SUM` and `COUNT` grouped by `categorie`** for the overview query | Single query aggregates all categories without loading individual expense records. No N+1 risk. The query is: `Depense::whereHas('recu', fn($q) => $q->where('user_id', $userId))->selectRaw('categorie, COUNT(*) as total_count, SUM(prix_unitaire * quantite) as total_amount')->groupBy('categorie')->get()`. |
| **Use `CategorieDepense::cases()` to ensure all enum values appear** even if a category has zero expenses | Prevents empty categories from being missing on the overview page. Merge query results with enum cases in PHP. |
| **Category summary on expense index** via a `withCount` or a scoped aggregation loaded alongside the expense list | A second lightweight query executed once per page load, not per row. Acceptable as it is a single aggregated query. |
| **Route: `/depenses/categories`** (GET) for the overview page | Follows RESTful conventions, namespaced under depenses since categories are a sub-resource of expenses. |
| **No policy changes needed** | Authorization is data-scoped: the queries already filter by `auth()->user()` via the `Depense` model's relationship chain. No new model or resource to authorize. |

## Risks / Trade-offs

- **[Performance] Aggregation query on large datasets** → Mitigation: The single aggregated query is efficient (one pass over expenses table per user). Add a composite index on `(categorie)` if needed. User-level data is naturally bounded by the user's receipt volume.
- **[UX] Enum-driven categories cannot be customized per user** → Mitigation: This is by design per AGENTS.md. The AI extraction contract mandates specific category values. If user-specific categories are needed, that would be a separate change.
- **[Maintenance] Category labels are duplicated in enum `label()` method** → Mitigation: The `CategorieDepense::label()` method is the single source of truth for display labels. All views call `$categorie->label()`. No hardcoded display strings in Blade templates.
