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

Reusable cross-cutting capabilities should live in PHPSprinkles plugins and be loaded by the shared `BaseApplication`.

Examples:
- authentication support
- JWT support
- future integrations such as Convex
- other reusable infrastructure concerns

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

### 9. Shared Users MVC lives in PHPSprinkles

Users is a platform-level concern for this system and should live in PHPSprinkles, not in a separate Users plugin.

## Ownership Buckets

### Bucket 1: PHPSprinkles

If it is true for every API, it belongs in PHPSprinkles.

This bucket should contain:
- `BaseApplication`
- shared API middleware setup
- shared bootstrap behavior
- shared request/response conventions
- shared error handling conventions
- shared Users MVC that every app uses
- shared base controllers and other app-wide framework abstractions

This bucket should not contain:
- client-specific models
- client-specific controllers
- client-specific routes
- client-specific service classes

### Bucket 2: App

If it is domain-specific, it belongs in the app.

This bucket should contain:
- domain models
- domain controllers
- domain routes
- domain-specific service classes
- app-specific configuration that is not universally true for every API

Apps should remain thin and should avoid re-implementing shared platform behavior.

### Bucket 3: Plugin

If it is a reusable capability, it belongs in a plugin loaded by `BaseApplication`.

This bucket should contain:
- reusable auth-related capabilities
- JWT support
- third-party integrations that may be enabled platform-wide
- reusable infrastructure or platform modules that are not domain-specific

Plugins should not become a dumping ground for domain logic that belongs in one specific app.

## Layering Model

The chosen layering model is:

1. Domain app
2. PHPSprinkles framework
3. PHPSprinkles plugins

In practice, the app is the runnable entrypoint, but its runtime behavior should be heavily standardized by the PHPSprinkles framework and the plugins loaded through the shared `BaseApplication`.

## Physical Layout

```text
php-sprinkles-mono/
  framework/
    PHPSprinkles/
      composer.json
      src/
      config/
      tests/
  plugins/
    PHPSprinklesAuth/
      composer.json
      src/
      config/
      tests/
    PHPSprinklesJWT/
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
- `plugins/PHPSprinklesAuth/src` -> `PHPSprinklesAuth\\`
- `plugins/PHPSprinklesJWT/src` -> `PHPSprinklesJWT\\`
- `apps/<app>/src` -> `App\\`

## Composer Package Names

Recommended package names:

- `phpsprinkles/framework`
- `phpsprinkles/auth`
- `phpsprinkles/jwt`

Apps should depend on the framework and the shared plugins they use via Composer path repositories during monorepo development.

## BaseApplication Contract

`PHPSprinkles\BaseApplication` should own:
- standard plugin loading
- middleware queue defaults
- Authentication wiring
- Authorization wiring
- JSON/API response conventions
- shared exception and error handling conventions
- shared bootstrap conventions
- shared Users MVC availability
- common service/container registration hooks

Each app's local `App\Application` should:
- extend `PHPSprinkles\BaseApplication`
- stay minimal
- add only app-specific routes, services, or config
- avoid re-implementing shared middleware, bootstrap, or plugin wiring

Recommended extension hooks in `BaseApplication`:
- `pluginList(): array`
- `middlewareConfig(MiddlewareQueue $middlewareQueue): MiddlewareQueue`
- `serviceConfig(ContainerInterface $container): void`
- `routes(RouteBuilder $routes): void`
- `bootstrapConfig(): void`

## Design Rule

- If it is true for every API, it belongs in `PHPSprinkles`.
- If it is domain-specific, it belongs in the app.
- If it is a reusable capability, it belongs in a plugin loaded by `BaseApplication`.

This rule should be used whenever there is uncertainty about where new code belongs.

## Open Follow-Up

The next implementation task is to restructure the current monorepo to match this architecture and prove it with the first runnable app.
