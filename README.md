# PHPSprinkles Monorepo

This monorepo holds the shared PHPSprinkles framework, reusable PHPSprinkles plugins, and the API apps that run on top of them.

## Architecture Rule

- If it is true for every API, it belongs in `PHPSprinkles`.
- If it is domain-specific, it belongs in the app.
- If it is a reusable capability, it belongs in a plugin loaded by `BaseApplication`.

## Architecture Summary

The platform is intentionally layered:

1. Domain app
2. PHPSprinkles framework
3. PHPSprinkles plugins

Each API app is a real CakePHP app, but all of them extend the same shared `BaseApplication` from PHPSprinkles.

That gives us:
- one place to wire shared API behavior
- one place to load standard plugins
- thin app-level code focused on domain resources and services

## What PHPSprinkles Is

`PHPSprinkles` is a shared API framework for building JSON-only CakePHP API servers.

It owns:
- the shared `BaseApplication`
- common API bootstrap and middleware wiring
- Authentication and Authorization defaults
- shared API conventions and error/response behavior

It is not a standalone business app, and it is not where domain-specific logic should live.

## What Apps Are

Each app under `apps/` is a runnable API server.

Apps should stay thin. They provide:
- domain models
- domain controllers/resources
- domain-specific routes
- a small number of domain services

## What Plugins Are

Plugins under `plugins/` are reusable capabilities that can be loaded once by the shared `BaseApplication`.

Examples:
- `PHPSprinklesAuth`
- `PHPSprinklesJWT`
- future cross-cutting integrations such as Convex

## Plugin Development Workflow

If something is a plugin, create it directly in `plugins/` from the beginning.

Do not scaffold it inside `framework/PHPSprinkles` and move it later.

Preferred workflow:

1. Create `plugins/<PluginName>/`
2. Give it its own `composer.json`, `src/`, `config/`, and `tests/`
3. Build and test it there
4. Wire it into the framework through Composer path repositories and `BaseApplication` only when it is ready to be shared

Why:
- it keeps ownership correct from day one
- it avoids path and namespace churn later
- it reinforces the rule that reusable capabilities belong in plugins, not in the framework

Future tooling need:
- add a scaffolding command for plugin creation
- intended shape:

```bash
sprinkles build:plugin PHPSprinklesRequestId
```

- that command does not exist yet and should be added later

## Current Direction

The chosen model is:
- shared runtime code lives in `framework/PHPSprinkles`
- shared capabilities live in `plugins/`
- all apps extend the shared `BaseApplication`
- apps keep the standard CakePHP `App\\` namespace
- domain code lives directly in each app's `src/`
- no copied base-app template as the primary runtime mechanism

## Physical Layout

```text
php-sprinkles-mono/
  framework/
    PHPSprinkles/
  plugins/
    PHPSprinklesAuth/
    PHPSprinklesJWT/
  apps/
    api-server-red-dog/
    api-server-blue-snake/
  docs/
  tooling/
```

## Namespace Map

- `framework/PHPSprinkles/src` -> `PHPSprinkles\\`
- `plugins/PHPSprinklesAuth/src` -> `PHPSprinklesAuth\\`
- `plugins/PHPSprinklesJWT/src` -> `PHPSprinklesJWT\\`
- `apps/<app>/src` -> `App\\`

## BaseApplication Contract

`PHPSprinkles\BaseApplication` owns:
- standard plugin loading
- middleware defaults
- Authentication and Authorization defaults
- shared bootstrap and API wiring
- common service/container hooks

Each app's local `App\Application` stays thin and should only add domain-specific routes, services, or config.

For the fuller architecture definition, see [docs/architecture.md](/Users/amoreno/Projects/PHPSprinkles/php-sprinkles-mono/docs/architecture.md).
