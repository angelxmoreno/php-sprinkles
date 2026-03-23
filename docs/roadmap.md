# PHPSprinkles Roadmap

This is the current source of truth for what PHPSprinkles should do next and in
what order.

## Current State

Already proven:
- shared `PHPSprinkles\BaseApplication`
- thin app bootstrap via `PHPSprinkles\Bootstrap\Bootstrapper`
- framework-owned plugins for request IDs, CORS, and `/debug`
- local-dev defaults centered on root `.env`, SQLite database, and SQLite cache
- framework `AppTable` with schema-driven soft delete
- `red-crm` as the first thin runnable app
- `contacts` as the first real resource proving soft-delete behavior

## Next Work, In Order

### 1. Make `red-crm` useful through `contacts`

Build the first meaningful app slice around `contacts`.

Required outcomes:
- list contacts
- view a single contact
- create a contact
- update a contact
- delete a contact as a soft delete

This should be implemented in the app first, not by adding more framework
abstractions preemptively.

### 2. Add real app-level integration tests for `contacts`

Once `contacts` endpoints exist, add integration coverage for:
- happy-path CRUD behavior
- soft delete behavior
- hidden trashed rows in normal reads
- restore behavior if that becomes part of the app API

These tests should live in `red-crm`, because the app is the real HTTP unit.

### 3. Introduce shared CRUD conventions only if the app slice justifies them

After the first `contacts` slice is working, evaluate whether repeated API
patterns justify integrating `FriendsOfCake/crud`.

Rule:
- if plain Cake controllers remain clear and cheap, keep moving
- if repeated controller/API ceremony appears, add shared CRUD conventions then

This keeps the framework driven by proven app needs.

### 4. Add search when the contacts list actually needs it

Introduce `FriendsOfCake/search` only when `contacts` listing needs real
filtering or search behavior.

Expected trigger:
- multiple filter parameters
- repeated finder logic
- shared list/search conventions worth standardizing

Do not add Search before there is real list behavior to prove it.

### 5. Build `PHPSprinklesAuth`

Auth is the next major framework slice after `contacts` proves the first real
app resource pattern.

Scope for v1:
- framework-owned auth plugin
- base `users` table
- email/password login
- registration
- JWT issuance and verification
- `login`, `register`, `me`, and `update` actions
- app-level registration-public toggle
- app-level JWT config

The auth slice should be validated through real app behavior, not framework-only
tests.

### 6. Generate the app skeleton from proven conventions

Once the current `red-crm` setup feels stable, make it the default app skeleton.

The generated app should come with:
- root-level `.env`
- thin bootstrap via `PHPSprinkles\Bootstrap\Bootstrapper`
- SQLite database by default for local development
- SQLite cache by default for local development
- `database/` directory ready for local files
- app dev tools like Bake and Migrations
- a README that explains local defaults and production overrides

The goal is faster-than-RAD local startup.

### 7. Implement the customization cookbook

Once the current platform behavior is stable enough to document, add the planned
cookbook-style docs under `docs/cookbook/`.

That documentation should explain:
- what CakePHP does by default
- what PHPSprinkles changes
- why it changes it
- how app builders use or override it

## Ongoing Rules While Building

- build v1 in vertical slices through `red-crm`
- add framework capabilities when a real app resource creates the need
- prefer framework ownership for universal behavior
- prefer app ownership for domain behavior
- keep app bootstraps and app `Application` classes thin
- avoid introducing framework-wide magic before the app proves it is worth it

## Deferred / Not Immediate

These are still in view, but they are not next:
- optional standalone plugins
- social login providers
- offline sync / Expo work
- broader app generator tooling beyond the core skeleton
- cookbook publishing/static site tooling
