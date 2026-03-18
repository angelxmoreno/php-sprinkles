# PHPSprinkles Implementation Plan

This is the concrete sequence for moving the monorepo from the current CakePHP skeleton state to the approved PHPSprinkles architecture.

## Phase 1: Restructure the Monorepo

1. Move `packages/PHPSprinkles` to `framework/PHPSprinkles`.
2. Move `packages/plugins` to `plugins`.
3. Remove the old `packages/` container once the new layout is in place.
4. Keep the existing docs under the monorepo root `docs/`.

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
   - shared Users MVC

## Phase 3: Normalize Shared Plugins

1. Rename placeholder plugin directories to the chosen convention:
   - `plugins/PHPSprinklesAuth`
   - `plugins/PHPSprinklesJWT`
2. Give each plugin its own `composer.json`, `src/`, `config/`, and `tests/`.
3. Wire shared plugin loading through `PHPSprinkles\BaseApplication`.

## Phase 4: Create the First Thin App

1. Create the first runnable app under `apps/`.
2. Give the app its own `composer.json`, `config/`, `src/`, `tests/`, and `webroot/`.
3. Keep the app namespace as `App\\`.
4. Implement a tiny local `App\Application` that extends `PHPSprinkles\BaseApplication`.
5. Keep domain code in the app's `src/`.

## Phase 5: Verify the Model

1. Verify the framework tests still run.
2. Verify the first app boots through its local `Application.php`.
3. Verify shared framework changes flow into the app through inheritance instead of copied files.
4. Verify shared plugin loading works through the base application.

## Success Criteria

The implementation is correct when:
- `framework/PHPSprinkles` exists and no longer presents itself as `cakephp/app`
- at least one app extends `PHPSprinkles\BaseApplication`
- apps keep the standard `App\\` namespace
- domain code lives in app `src/`
- shared Users MVC lives in PHPSprinkles
- shared capabilities are loadable from `plugins/`
