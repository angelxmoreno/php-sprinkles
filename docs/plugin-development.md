# Plugin Development

This document defines how PHPSprinkles plugins should be classified, created, developed, and integrated.

## Plugin Classes

There are two plugin classes in this monorepo.

### Framework Plugins

Framework plugins live in:

```text
framework/PHPSprinkles/plugins/<PluginName>
```

Use this location when the capability:
- should exist in every API
- should be loaded by the framework once
- should populate automatically to all apps
- is modular enough to be cleaner as a plugin than as framework core

Examples:
- request ID handling
- auth wiring
- JWT support if it is universal

### Top-Level Plugins

Top-level plugins live in:

```text
plugins/<PluginName>
```

Use this location when the capability:
- is only needed by some apps
- is optional to the framework
- may later be released or versioned independently

Examples:
- selective third-party integrations
- app-specific shared modules
- plugins with a plausible standalone future

## Placement Rule

Use this order when deciding where new code belongs:

1. If it is inseparable from framework behavior, put it in `framework/PHPSprinkles/src`.
2. If it is true for every API but cleaner as a module, put it in `framework/PHPSprinkles/plugins`.
3. If it is selective or plausibly standalone, put it in top-level `plugins/`.
4. If it is domain-specific, put it in the app.

## Why This Boundary Exists

The goal is to keep universal platform behavior owned by the framework.

If a new framework capability should affect every app automatically, the framework should own both:
- the runtime loading
- the code location

That avoids per-app integration work and keeps the app layer thin.

Top-level `plugins/` still exists, but only for capabilities that are not universal or that may deserve an independent lifecycle later.

## Runtime vs Composer Resolution

CakePHP runtime plugin discovery and Composer dependency resolution are different concerns.

- CakePHP can load plugin code from configured plugin paths at runtime.
- Composer decides how packages are installed and resolved before runtime begins.

This matters because framework-owned plugins should behave like part of the shared framework, not like optional app-level dependencies.

## Development Flow

### Framework Plugin Flow

1. Create the plugin directly in `framework/PHPSprinkles/plugins/<PluginName>`.
2. Give it its own `composer.json`, `src/`, `config/`, and `tests/`.
3. Implement and test it inside that plugin directory.
4. Load it from `PHPSprinkles\BaseApplication`.
5. Verify a real app inherits the behavior automatically.

### Top-Level Plugin Flow

1. Create the plugin directly in `plugins/<PluginName>`.
2. Give it its own `composer.json`, `src/`, `config/`, and `tests/`.
3. Implement and test it there.
4. Integrate it explicitly where needed.

Do not create a plugin in one location and move it later unless you are intentionally extracting it.

## Framework Integration Rule

Framework plugins should be wired by the framework, not by individual apps.

That means:
- the framework decides which framework plugins are loaded
- `BaseApplication` is the shared integration point
- apps should inherit framework plugin behavior without app-level runtime changes

## Extraction Rule

A framework plugin can be promoted to top-level `plugins/` later if:
- it stops being universal
- it needs an independent release lifecycle
- it becomes useful outside PHPSprinkles core

That move should be treated as an extraction step, not the default way plugin work begins.

## Future Tooling

We should add scaffolding commands for both plugin classes.

Intended commands:

```bash
sprinkles build:framework-plugin PHPSprinklesRequestId
sprinkles build:plugin SomeStandalonePlugin
```

These commands do not exist yet.

When implemented, they should:
- create the plugin in the correct location
- write the initial `composer.json`
- create the standard directories
- add local test scaffolding
- preserve the chosen ownership model from day one

Framework plugin behavior that changes how all apps work should also be documented in the planned cookbook-style customization docs described in [customization-cookbook-plan.md](/Users/amoreno/Projects/PHPSprinkles/php-sprinkles-mono/docs/customization-cookbook-plan.md).
