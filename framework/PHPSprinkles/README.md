# PHPSprinkles Framework

`PHPSprinkles` is the shared CakePHP API framework used by the apps in this monorepo.

It is not a domain app. It provides the shared runtime layer that every PHPSprinkles API server extends.

## Responsibilities

The framework owns:
- `PHPSprinkles\BaseApplication`
- shared middleware defaults
- shared bootstrap behavior
- shared route defaults
- shared API conventions
- shared runtime abstractions used by all apps

It should not own domain-specific models, controllers, routes, or services.

## How Apps Use It

Each runnable API app keeps its own local `App\Application`, but that class should extend `PHPSprinkles\BaseApplication`.

That gives every app:
- one shared place for middleware wiring
- one shared place for plugin loading
- one shared place for bootstrap wiring
- one shared place for common API behavior
- app-level freedom to add only domain-specific logic

PHPSprinkles apps should keep `config/bootstrap.php` thin:
- app-level `config/paths.php` stays app-owned
- the framework bootstrap is the default shared bootstrap
- app bootstraps should delegate to `PHPSprinkles\Bootstrap\Bootstrapper` instead of copying Cake bootstrap logic

PHPSprinkles app skeletons should default to fast local startup:
- root-level `.env`
- SQLite datasource for local development
- SQLite cache for local development
- a generated README that explains those defaults and the expected production overrides

App-level configuration should expose framework-managed override surfaces when apps are expected to tune them. For example, `Cors` policy is implemented by the framework but should still be visible in each app's `config/app.php` so app builders can discover and override it easily.

## Current Customizations

Today the framework adds these shared behaviors on top of baseline CakePHP:
- shared `PHPSprinkles\BaseApplication` as the standard app runtime entrypoint
- framework-managed plugin loading for universal platform capabilities
- framework-managed bootstrap defaults, including root-level `.env` loading and SQLite cache DSN registration
- `PHPSprinklesRequestId` for request/response request ID propagation
- `PHPSprinklesCors` for shared CORS policy and localhost-friendly development defaults
- `PHPSprinklesDebugPage` for a debug-only `/debug` JSON setup/status endpoint

The README should stay high level. Detailed behavior, rationale, and override guidance should live in the docs and the planned customization cookbook.

## Namespace

Framework code uses:

```php
PHPSprinkles\\
```

Runnable apps keep the standard CakePHP app namespace:

```php
App\\
```

## Layout

```text
framework/PHPSprinkles/
  composer.json
  config/
  src/
  tests/
```

## Current Role In The Monorepo

This package is the shared framework layer in the PHPSprinkles architecture:

1. domain app
2. PHPSprinkles framework
3. PHPSprinkles plugins

Shared capabilities that are optional or cross-cutting should live in plugins, not directly in the framework.
