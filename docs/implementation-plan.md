# PHPSprinkles Implementation Plan

This is the concrete sequence for moving the monorepo from the current CakePHP skeleton state to the approved PHPSprinkles architecture.

## Current Status

- Phase 1: complete
- Phase 2: complete
- Phase 3: deferred until real shared plugins exist
- Phase 4: complete
- Phase 5: pending after the first app exists

## Phase 1: Restructure the Monorepo

1. Move `packages/PHPSprinkles` to `framework/PHPSprinkles`.
2. Move `packages/plugins` to `plugins`.
3. Remove the old `packages/` container once the new layout is in place.
4. Keep the existing docs under the monorepo root `docs/`.

Status: complete

## Phase 2: Convert PHPSprinkles Into the Shared Framework

1. Change `framework/PHPSprinkles/composer.json` from the Cake skeleton defaults to PHPSprinkles naming:
   - package name -> `phpsprinkles/framework`
   - namespace -> `PHPSprinkles\\`
   - description -> shared PHPSprinkles API framework
2. Replace `App\\` autoloading inside the framework with `PHPSprinkles\\`.
3. Introduce `PHPSprinkles\BaseApplication`.
4. Move framework-owned runtime behavior into PHPSprinkles:
   - shared middleware setup
   - shared bootstrap wiring
   - shared API conventions

Status: complete

## Phase 3: Normalize Shared Plugins

1. Rename placeholder plugin directories to the chosen convention:
   - `plugins/PHPSprinklesAuth`
   - `plugins/PHPSprinklesJWT`
2. Give each plugin its own `composer.json`, `src/`, `config/`, and `tests/`.
3. Wire shared plugin loading through `PHPSprinkles\BaseApplication`.

Status: deferred

Reason:
- there are no real shared plugins to normalize yet
- plugin structure should be revisited once the first concrete shared capability exists

## Phase 4: Create the First Thin App

Target app:

```text
apps/red-crm
```

1. Create the first runnable app under `apps/` as `red-crm`.
2. Give the app its own `composer.json`, `config/`, `src/`, `tests/`, and `webroot/`.
3. Keep the app namespace as `App\\`.
4. Implement a tiny local `App\Application` that extends `PHPSprinkles\BaseApplication`.
5. Keep domain code in the app's `src/`.
6. Reuse as much shared framework configuration as possible instead of copying framework-owned behavior into the app.

Note for later:
- add a scaffolding command to generate the bare minimum for a new app
- intended shape:

```bash
sprinkles build:app red-crm
```

- this is not part of Phase 4 implementation right now, but the `red-crm` work should keep that future generator in mind

Status: complete

Verified outcome:
- `apps/red-crm` exists as the first thin runnable app
- it keeps the `App\\` namespace
- `App\Application` extends `PHPSprinkles\BaseApplication`
- the app consumes `phpsprinkles/framework` through a Composer path repository
- `./bin/cake --version` works
- `composer test` passes

## Phase 5: Verify the Model

1. Verify the framework tests still run.
2. Verify `apps/red-crm` boots through its local `Application.php`.
3. Verify shared framework changes flow into the app through inheritance instead of copied files.
4. Verify shared plugin loading works through the base application.

## Success Criteria

The implementation is correct when:
- `framework/PHPSprinkles` exists and no longer presents itself as `cakephp/app`
- at least one app extends `PHPSprinkles\BaseApplication`
- apps keep the standard `App\\` namespace
- domain code lives in app `src/`
- shared capabilities are loadable from `plugins/`
