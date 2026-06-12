## Why

The application cannot receive or manage supplier receipts without a working CRUD layer. Currently there are no database tables, models, enums, routes, controllers, or views for receipts or expenses. This change establishes the entire foundation so users can submit receipt text, see it listed, view details, and delete receipts — with proper validation, authorization, and data isolation.

## What Changes

- Create `StatutRecu` and `CategorieDepense` enums with Eloquent casts
- Create `Recu` and `Depense` models with relationships and cascade delete
- Create migration for `recus` and `depenses` tables
- Create `StoreRecuRequest` form request for receipt text validation
- Create `RecuPolicy` for ownership-based authorization
- Create `RecuController` for full CRUD (index, create, store, show, destroy)
- Create Blade views for receipt listing, creation form, and detail page
- Register routes for receipt management under auth middleware
- Update `User` model with `recus()` relationship
- Add `DepenseController` for expense listing with category filter
- Create Blade view for expenses list with category filter dropdown
- Register expense routes under auth middleware
- Set up database-backed queue with `queue:table` migration

## Capabilities

### New Capabilities
- `receipt-management`: Full CRUD for authenticated user receipts — create, list, view, delete with validation, authorization, and status tracking
- `expense-tracking`: List and filter extracted expenses by category, scoped to authenticated user

### Modified Capabilities
<!-- No existing specs are being modified; both capabilities are new implementations. -->

## Impact

- New database tables: `recus`, `depenses`, `jobs`, `failed_jobs`
- New enums: `StatutRecu`, `CategorieDepense`
- New models: `Recu`, `Depense`
- New controller: `RecuController`, `DepenseController`
- New form request: `StoreRecuRequest`
- New policy: `RecuPolicy`
- New Blade views under `resources/views/recus/` and `resources/views/depenses/`
- Updated `User` model with relationship
- Updated `routes/web.php` with auth-protected resource routes
- Queue connection must be set to `database` in `.env`
