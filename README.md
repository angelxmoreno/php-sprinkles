# PHPSprinkles Monorepo

This monorepo holds the shared PHPSprinkles framework, framework-owned PHPSprinkles plugins, optional standalone plugins, and the API apps that run on top of them.

## Architecture Rule

- If it is true for every API, it belongs in `PHPSprinkles`.
- If it is domain-specific, it belongs in the app.
- If it is a reusable capability for every app, it usually belongs in a framework plugin loaded by `BaseApplication`.
- If it is selective or intended for standalone release later, it belongs in top-level `plugins/`.

## Architecture Summary

The platform is intentionally layered:

1. Domain app
2. PHPSprinkles framework
3. PHPSprinkles framework plugins
4. Optional standalone plugins

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
- framework-owned plugins that every app should inherit automatically

It is not a standalone business app, and it is not where domain-specific logic should live.

## What Apps Are

Each app under `apps/` is a runnable API server.

Apps should stay thin. They provide:
- domain models
- domain controllers/resources
- domain-specific routes
- a small number of domain services

## Plugin Boundaries

There are two plugin buckets.

Framework plugins under `framework/PHPSprinkles/plugins/` are for reusable capabilities that every app should get automatically through the shared framework.

Examples:
- request ID handling
- auth wiring
- JWT support
- other universal API platform capabilities

Top-level plugins under `plugins/` are for capabilities that are not universal across every app, or that may later be released independently.

Examples:
- app-specific integrations
- selective platform modules
- future standalone packages

## Plugin Development Workflow

If something is a framework-wide plugin, create it directly in `framework/PHPSprinkles/plugins/` from the beginning.

If something is optional or a standalone candidate, create it directly in top-level `plugins/`.

Preferred workflow:

1. Decide whether the plugin is framework-owned or standalone
2. Create it in the correct directory from day one
3. Give it its own `composer.json`, `src/`, `config/`, and `tests/`
4. Build and test it there
5. Wire framework-owned plugins through the framework only when they are ready to be shared

Why:
- it keeps ownership correct from day one
- it avoids path and namespace churn later
- it reinforces the rule that universal plugin behavior should not leak into app-level wiring

Future tooling need:
- add scaffolding commands for plugin creation
- intended shape:

```bash
sprinkles build:framework-plugin PHPSprinklesRequestId
sprinkles build:plugin SomeStandalonePlugin
```

- these commands do not exist yet and should be added later

## Current Direction

The chosen model is:
- shared runtime code lives in `framework/PHPSprinkles`
- framework-wide plugin capabilities live in `framework/PHPSprinkles/plugins`
- top-level `plugins/` is reserved for selective or future standalone plugins
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
  plugins/
    OptionalStandalonePlugin/
  apps/
    api-server-red-dog/
    api-server-blue-snake/
  docs/
  tooling/
```

## Namespace Map

- `framework/PHPSprinkles/src` -> `PHPSprinkles\\`
- `framework/PHPSprinkles/plugins/<PluginName>/src` -> `<PluginName>\\`
- `plugins/<PluginName>/src` -> `<PluginName>\\`
- `apps/<app>/src` -> `App\\`

## BaseApplication Contract

`PHPSprinkles\BaseApplication` owns:
- standard plugin loading
- middleware defaults
- Authentication and Authorization defaults
- shared bootstrap and API wiring
- common service/container hooks

Framework-owned plugins should be loaded by the framework, not by individual apps.

Each app's local `App\Application` stays thin and should only add domain-specific routes, services, or config.

For the fuller architecture definition, see [docs/architecture.md](docs/architecture.md).

For planned auth, plugin, and future platform work, see [docs/platform-roadmap.md](docs/platform-roadmap.md).

For the planned cookbook-style documentation system that will record PHPSprinkles customizations over CakePHP, see [docs/customization-cookbook-plan.md](docs/customization-cookbook-plan.md).
