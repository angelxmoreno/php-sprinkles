# PHPSprinkles Implementation Plan

This is the concrete sequence for moving the monorepo from the current CakePHP skeleton state to the approved PHPSprinkles architecture.

## Current Status

- Phase 1: complete
- Phase 2: complete
- Phase 3: complete
- Phase 4: complete
- Phase 5: complete for the framework and first app

## Next Sequencing Rule

Future v1 work should proceed in vertical slices through `red-crm`, not as a single "all framework plugins first" block.

That means:
- keep infra-first framework work early when it does not depend on real data
- create real MVP resources in `red-crm`
- introduce model-dependent framework plugins such as `Crud`, `Search`, and `Trash` when those resources actually require them
- validate those framework capabilities through real app behavior instead of placeholder data
- treat `PHPSprinklesAuth` as its own framework-owned slice rather than a prerequisite for every other MVP feature

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

The plugin model has changed.

Universal framework capabilities should no longer default to top-level `plugins/`.

New direction:
- framework-owned plugins belong in `framework/PHPSprinkles/plugins`
- top-level `plugins/` is reserved for selective or future standalone plugins
- framework-owned plugins should be loaded by `PHPSprinkles\BaseApplication`
- apps should inherit those plugins automatically

The first concrete example of this model is `PHPSprinklesRequestId`, which now lives inside the framework as a framework-owned plugin.

Status: complete

Plugin development rule:
- create framework-owned plugins directly in `framework/PHPSprinkles/plugins/`
- create selective or standalone plugins directly in top-level `plugins/`

Future tooling note:
- add scaffolding commands for both plugin classes
- intended shape:

```bash
sprinkles build:framework-plugin PHPSprinklesRequestId
sprinkles build:plugin SomeStandalonePlugin
```

- these commands are not part of the current implementation

Verified outcome:
- `PHPSprinklesRequestId` now lives under `framework/PHPSprinkles/plugins/PHPSprinklesRequestId`
- the framework owns the plugin namespace and loading
- `red-crm` no longer points at a top-level `plugins/PHPSprinklesRequestId` path
- the top-level `plugins/` directory is reserved for selective or standalone work

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
7. Keep app `config/bootstrap.php` as a thin delegator:
   - app-owned `paths.php`
   - framework-owned shared bootstrap
   - no copied Cake bootstrap logic in each app

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
- `config/bootstrap.php` is a thin wrapper that delegates to the framework bootstrap
- the app consumes `phpsprinkles/framework` through a Composer path repository
- `./bin/cake --version` works
- `composer test` passes

## Phase 5: Verify the Model

1. Verify the framework tests still run.
2. Verify `apps/red-crm` boots through its local `Application.php`.
3. Verify shared framework changes flow into the app through inheritance instead of copied files.
4. Verify shared plugin loading works through the base application.

Note:
- framework-owned plugin verification is now proven with `PHPSprinklesRequestId`
- current framework/plugin tests are sufficient for shared wiring and middleware behavior during the platform buildout
- richer API integration tests should be added later at the app level, once `red-crm` exposes useful MVP endpoints that represent real-world HTTP scenarios

## Success Criteria

The implementation is correct when:
- `framework/PHPSprinkles` exists and no longer presents itself as `cakephp/app`
- at least one app extends `PHPSprinkles\BaseApplication`
- apps keep the standard `App\\` namespace
- domain code lives in app `src/`
- framework-owned capabilities are loadable from `framework/PHPSprinkles/plugins/`
- top-level `plugins/` is used only for selective or standalone plugin work
