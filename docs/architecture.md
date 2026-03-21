# PHPSprinkles Architecture

## Decisions Made

### 1. PHPSprinkles is a shared framework layer

`PHPSprinkles` is not a domain app and should never be treated as one.

It exists to provide a shared CakePHP API framework for many API servers that are otherwise structurally identical.

### 2. Every API app extends a shared `BaseApplication`

All API apps are real CakePHP apps.

Each app will have its own local `Application.php`, but that class should stay thin and extend the shared `BaseApplication` provided by PHPSprinkles.

This is the central mechanism for:
- shared plugin loading
- shared middleware wiring
- shared API bootstrap behavior
- shared Authentication and Authorization defaults
- shared API conventions across all apps

### 3. Apps are JSON API servers

Every app in this monorepo is expected to:
- serve JSON
- use Authentication
- use Authorization
- have a `users` table
- share the same API platform behavior

The main variation between apps is domain logic.

### 4. Shared capabilities live in plugins

Reusable cross-cutting capabilities should live in plugins and be loaded by the shared `BaseApplication`.

Examples:
- authentication support
- JWT support
- future integrations such as Convex
- other reusable infrastructure concerns

There are two plugin classes:
- framework-owned plugins under `framework/PHPSprinkles/plugins`
- selective or standalone plugins under top-level `plugins/`

### 5. We are not using a copied base app as the runtime model

We do not want a setup where new apps are copied from a base app and then drift over time.

If a shared change should affect all apps, it must live in shared runtime code, not in a copied template.

### 6. PHPSprinkles lives under `framework/`

The shared framework should live at:

```text
framework/PHPSprinkles
```

This replaces the earlier `packages/PHPSprinkles` idea.

### 7. Apps keep the standard CakePHP `App\\` namespace

Each runnable app should keep the normal CakePHP app namespace:

```text
App\\
```

We are not introducing custom per-app namespaces such as `RedDog\\` or `BlueSnake\\`.

### 8. Domain code lives directly in each app's `src/`

We are not creating an extra app-local domain plugin layer by default.

Domain controllers, models, routes, and service classes belong directly in the app's own `src/` and app config.

## Ownership Buckets

### Bucket 1: Framework Core

If it is true for every API, it belongs in PHPSprinkles.

This bucket should contain:
- `BaseApplication`
- shared API middleware setup
- shared bootstrap behavior
- shared request/response conventions
- shared error handling conventions
- shared base controllers and other app-wide framework abstractions

This bucket should not contain:
- client-specific models
- client-specific controllers
- client-specific routes
- client-specific service classes

Rule:
- if removing it would break the framework itself, it belongs in `framework/PHPSprinkles/src`

### Bucket 2: App

If it is domain-specific, it belongs in the app.

This bucket should contain:
- domain models
- domain controllers
- domain routes
- domain-specific service classes
- app-specific configuration that is not universally true for every API

Apps should remain thin and should avoid re-implementing shared platform behavior.

### Bucket 3: Framework Plugin

If it is modular, reusable, and universal across all apps, it belongs in a framework plugin loaded by `BaseApplication`.

This bucket should contain:
- reusable auth-related capabilities that every app gets
- JWT support if it is standard across the platform
- platform-wide infrastructure features such as request IDs
- reusable modules that are cleaner as plugins than as framework core

Framework plugins should live under:

```text
framework/PHPSprinkles/plugins/<PluginName>
```

They should:
- be loaded by the framework, not individual apps
- populate automatically to every app through the shared framework
- remain internal to PHPSprinkles unless there is a reason to extract them later

### Bucket 4: Top-Level Plugin

If it is selective, app-specific, or a candidate for independent release later, it belongs in top-level `plugins/`.

This bucket should contain:
- capabilities used by only some apps
- integrations not guaranteed to exist in every API
- plugins with a plausible independent lifecycle

Top-level plugins should live under:

```text
plugins/<PluginName>
```

These are not assumed to be loaded by every app.

## Plugin Development Workflow

Plugin placement depends on ownership.

Preferred development flow:
- put universal platform plugins directly in `framework/PHPSprinkles/plugins/`
- put optional or standalone plugins directly in top-level `plugins/`
- give it its own Composer package metadata
- develop and test it there
- load it through `BaseApplication` once it is ready to be shared across apps

Reasoning:
- correct ownership from the start
- no later path or namespace migration
- no app-level wiring for framework-owned plugin behavior
- better long-term extraction boundaries

## Runtime vs Dependency Resolution

CakePHP plugin discovery and Composer package resolution are separate concerns.

- CakePHP runtime plugin loading can be controlled through plugin paths and framework bootstrapping.
- Composer dependency resolution decides how code gets installed in the first place.

The new boundary exists because framework-owned plugins should behave as part of the framework at runtime and should not require per-app integration work.

## Layering Model

The chosen layering model is:

1. Domain app
2. PHPSprinkles framework
3. PHPSprinkles framework plugins
4. Optional standalone plugins

In practice, the app is the runnable entrypoint, but its runtime behavior should be heavily standardized by the PHPSprinkles framework and the plugins loaded through the shared `BaseApplication`.

## Physical Layout

```text
php-sprinkles-mono/
  framework/
    PHPSprinkles/
      composer.json
      src/
      config/
      plugins/
      tests/
  plugins/
    OptionalStandalonePlugin/
      composer.json
      src/
      config/
      tests/
  apps/
    api-server-red-dog/
      composer.json
      config/
      src/
      tests/
      webroot/
    api-server-blue-snake/
      composer.json
      config/
      src/
      tests/
      webroot/
```

## Namespace Map

- `framework/PHPSprinkles/src` -> `PHPSprinkles\\`
- `framework/PHPSprinkles/plugins/<PluginName>/src` -> `<PluginName>\\`
- `plugins/<PluginName>/src` -> `<PluginName>\\`
- `apps/<app>/src` -> `App\\`

## Composer Package Names

Recommended package names:

- `phpsprinkles/framework`
- `phpsprinkles/auth`
- `phpsprinkles/jwt`

Apps should depend on the framework. Framework-owned plugins should be brought in through the framework, while top-level plugins remain explicit dependencies only when they are actually selective or standalone.

## BaseApplication Contract

`PHPSprinkles\BaseApplication` should own:
- standard plugin loading
- middleware queue defaults
- Authentication wiring
- Authorization wiring
- JSON/API response conventions
- shared exception and error handling conventions
- shared bootstrap conventions
- common service/container registration hooks

Each app's local `App\Application` should:
- extend `PHPSprinkles\BaseApplication`
- stay minimal
- add only app-specific routes, services, or config
- avoid re-implementing shared middleware, bootstrap, or plugin wiring

Framework-owned plugins should be loaded here so every app inherits them automatically.

For the planned app-builder documentation system that will explain these framework-level customizations, see [customization-cookbook-plan.md](/Users/amoreno/Projects/PHPSprinkles/php-sprinkles-mono/docs/customization-cookbook-plan.md).

Recommended extension hooks in `BaseApplication`:
- `pluginList(): array`
- `middlewareConfig(MiddlewareQueue $middlewareQueue): MiddlewareQueue`
- `serviceConfig(ContainerInterface $container): void`
- `routes(RouteBuilder $routes): void`
- `bootstrapConfig(): void`

## Testing Direction

Testing should be layered the same way the runtime is layered.

Framework-level tests should verify:
- shared middleware and plugin behavior in isolation
- framework wiring and inherited middleware stack behavior
- shared runtime conventions that do not require app-specific routes

App-level tests should verify:
- real API endpoint behavior through CakePHP integration tests
- middleware, routing, controller, auth, and response behavior together
- app-consumable HTTP scenarios that match real usage

Current direction:
- keep framework and plugin unit/behavior tests in place now
- add richer API integration tests at the app level once `red-crm` has useful MVP endpoints worth exercising end to end

This means the main long-term API integration surface should live in runnable apps, not only in framework-local tests.

## Design Rule

- If it is true for every API, it belongs in `PHPSprinkles`.
- If it is domain-specific, it belongs in the app.
- If it is a reusable capability for every app, it belongs in a framework plugin loaded by `BaseApplication`.
- If it is selective or intended for standalone release later, it belongs in top-level `plugins/`.

This rule should be used whenever there is uncertainty about where new code belongs.

Sequencing rule:
- app-level MVP resources can be created before every future framework plugin is integrated
- framework-level capabilities should be introduced when a real app resource creates a concrete need for them
- do not invent temporary framework data just to prove a model-dependent plugin in isolation

In practice, this means framework ownership and app-first delivery are not in conflict:
- the app creates the need
- the framework owns the reusable capability
- the app proves the capability through a real endpoint or model flow

## Promotion / Extraction Rule

Framework plugins can later be promoted to top-level `plugins/` if they stop being universal, need an independent lifecycle, or become realistic standalone packages.

That move should be treated as an extraction step, not the default starting point.

## Open Follow-Up

The next implementation task is to align the current plugin layout with this architecture, especially where universal framework behavior is still living at the monorepo top level.
