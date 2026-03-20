# PHPSprinkles Platform Roadmap

This document captures the current direction for base users, auth, shared plugins, and future platform features.

It is intentionally a planning document. Some items are decisions, some are recommendations, and some remain pending evaluation.

## Current Decisions

### Auth Ownership

`PHPSprinklesAuth` will be the main framework plugin for:
- Authentication conventions
- Authorization conventions
- JWT conventions
- social login conventions
- shared auth-related helpers and integration points

Apps should override auth behavior through framework-defined hooks and configuration, not by bypassing the shared plugin.

### Users and OAuth Profiles

Base users should live with the auth system, not as a separate unrelated module.

Current direction:
- `users` is a base platform table
- `oauth_profiles` is a base platform table for social login identities
- both belong with `PHPSprinklesAuth`

Confirmed auth direction:
- local password auth is in scope from day one
- framework-owned registration endpoints/controllers are in scope
- JWT is in scope from day one

The exact schema and MVC surfaces are still to be designed.

## PHPSprinklesAuth v1

### Users Schema Direction

Current `users` fields:
- `name`
- `email`
- `password`
- `profile_image_url`
- `is_admin`
- `role`
- `is_active`

All models should extend a shared base model that already provides:
- `id`
- `created`
- `updated`
- `deleted`

Current decisions:
- emails must be unique
- `is_active` exists in v1
- `email_verified_at` does not exist in v1

Note:
- `role` and `is_admin` are both intentionally present for now
- `is_admin` is a fast prototyping and admin bypass flag
- normal authorization should rely on the regular authorization rules
- if `is_admin` is enabled for a user, it is treated as an explicit authorization skip

### Registration Behavior

Current v1 behavior:
- registration endpoints/controllers are owned by the framework
- registration is not public by default
- apps must be able to override whether registration is public
- newly registered accounts become active immediately
- login is allowed before email verification in v1

This means the first confirmed app-level auth override is:
- whether registration is public

Registration response behavior is still a backend concern even without sessions, because the API still has to decide whether registration returns:
- only the created user payload
- the created user plus a JWT
- or a response that requires a separate login call

This remains a small v1 behavior decision to make later.

### Password Rules

Current v1 direction:
- minimum length: 12
- maximum length: 24
- require uppercase and lowercase letters
- require numbers
- require symbols

### JWT Direction

Current v1 direction:
- issue JWTs on login
- use CakePHP Authentication components where possible for JWT auth support
- token lifetime should be configurable at the app level
- token claims should be configurable at the app level
- default signing strategy should be app-configurable env-driven `HS256`
- no refresh tokens in v1 yet
- revoked JWT support is deferred to v2

Signing strategy options to choose from later:
- symmetric signing such as `HS256`
  - simplest setup
  - one shared secret signs and verifies tokens
  - easiest for v1 if only the API issues and verifies tokens
- asymmetric signing such as `RS256` or `ES256`
  - private key signs, public key verifies
  - better separation if other services may verify tokens later
  - more operational overhead

Key storage options to choose from later:
- app environment variables
- app-level key files referenced from environment/config
- a secret manager outside the repo

Current decision:
- v1 uses simple app-configurable env-driven `HS256`
- each app controls its own signing secret through environment/config
- broader signing options can be added in v2

### Auth Actions

Current v1 auth actions:
- `login`
- `register`
- `me`
- `update`

These should be exposed by the auth plugin and can use Crud custom actions where that helps the API surface.

Current action semantics:
- `me` should return the authenticated user payload
- v1 `me` should return everything except the password
- v1 `update` should only allow changing `name`
- `login` should return the JWT plus the authenticated user payload
- `register` should return the JWT plus the created user payload

Auth and permission metadata means extra response data such as:
- current role
- `is_admin`
- authorization capabilities
- feature flags or other auth-derived state

Current direction:
- v1 `me` returns the base user payload only
- v2 should support app-level expansion of associated data, likely through a query name or framework hook

### CORS

CORS should be treated as framework middleware and configuration, not assumed to require a separate CakePHP community plugin.

If a wrapper is needed later, it should exist to standardize PHPSprinkles CORS behavior, not just to repackage a third-party dependency.

### Migrations and Bake

`Migrations` and `Bake` should be treated as app-level development tools, not framework runtime plugins.

Recommendation:
- each app should have them available in its own development environment
- the framework should define the expected usage and conventions
- future scaffolding should add them automatically when a new app is created

Reason:
- they are used from the app context
- Composer dev dependencies do not flow cleanly from framework to app the same way runtime behavior does
- they are tooling, not shared runtime platform behavior

## Near-Term Work

### Base Users

Define the base auth data model:
- `users`
- `oauth_profiles`

Design goals:
- support local auth plus social identity attachment
- keep the core user model generic enough for all apps
- allow apps to extend user-related behavior without replacing the whole system

### Shared Runtime Plugins To Add

These are strong candidates for framework-level standardization:
- Authentication
- Authorization
- JWT support
- Search
- soft delete support
- CORS middleware conventions
- Crud API conventions

Common but optional:
- queue/background job support

These should only become PHPSprinkles framework plugins when we are sure they are truly universal.

## V1 Implementation Order

This section is intentionally limited to the order for completing v1.

1. Framework plugin setup
   - standardize framework integration for `FriendsOfCake/crud`
   - standardize framework integration for `FriendsOfCake/search`
   - standardize framework integration for `UseMuffin/Trash`
   - standardize CORS middleware/config at the framework level

2. `PHPSprinklesAuth` foundation
   - create the framework-owned `PHPSprinklesAuth` plugin
   - define the base `users` schema
   - establish password hashing and email/password authentication
   - establish JWT generation and verification using app-configurable env-driven `HS256`

3. Auth API surface
   - implement framework-owned auth actions for `login`, `register`, `me`, and `update`
   - return JWT plus user payload from `login`
   - return JWT plus user payload from `register`
   - return the authenticated user payload from `me`
   - limit `update` to changing `name` in v1

4. App-level override surface
   - add the app-level registration-public toggle
   - add app-level JWT lifetime configuration
   - add app-level JWT claims configuration
   - keep the override surface minimal until real app needs emerge

5. End-to-end verification
   - prove framework-owned auth behavior loads automatically in `red-crm`
   - verify the framework plugins work together in one real app
   - verify the API contract for `login`, `register`, `me`, and `update`

## API Layer Direction

We are not committing to both `FriendsOfCake/crud` and `CakeDC/cakephp-api`.

Current decision:
- use `FriendsOfCake/crud` now
- treat it as the current API layer choice
- if its opinions start to fight PHPSprinkles, build a PHPSprinkles API plugin later

What we like about `Crud`:
- API-oriented listeners
- pagination helpers
- JSON API support
- search integration points

`CakeDC/cakephp-api` is not the chosen direction right now.

## Social Login Direction

Social login support is planned soon.

Current direction:
- build PHPSprinkles social login support ourselves
- use League OAuth2 client providers
- use `ADmad/cakephp-social-auth` as a reference source, not as the planned long-term dependency

Target providers:
- Facebook
- GitHub
- Google
- Instagram
- Apple
- TikTok
- Discord

Pending decisions:
- how provider identity data maps into `oauth_profiles`

## Future Plugin Ideas

### Expo Sync

Planned capability:
- offline-first mobile sync

Expected endpoints:
- `POST /sync/push`
- `GET /sync/pull`

Expected concerns:
- delta sync
- conflict resolution
- batch updates

This remains a future PHPSprinkles feature and is not part of the current implementation sequence.

## Pending Decisions

These still need explicit design work before implementation:
- exact `oauth_profiles` schema
- whether `Search`, `Queue`, and `SoftDelete` are truly universal framework plugins or only common options
- how JWT should be exposed in `PHPSprinklesAuth`
- the exact override hooks beyond public registration
- the exact app-level mechanism for expanding `/me` in v2

## Tooling Follow-Up

Future scaffolding should remember these categories.

Examples:
- `sprinkles build:app <name>` should add app-level dev tools like `Bake` and `Migrations`
- `sprinkles build:framework-plugin <name>` should create universal platform plugins
- `sprinkles build:plugin <name>` should create selective or standalone plugins
