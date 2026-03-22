# PHPSprinkles Architecture

## Core Model

PHPSprinkles is a shared CakePHP API framework, not a business app.

The platform is intentionally layered:

1. app
2. framework
3. framework-owned plugins
4. optional standalone plugins

Each runnable API stays a normal CakePHP app with the `App\\` namespace, but it
extends the shared `PHPSprinkles\BaseApplication`.

## Ownership Rules

- if it is true for every API, it belongs in `framework/PHPSprinkles`
- if it is modular and universal, it belongs in `framework/PHPSprinkles/plugins`
- if it is selective or could later stand alone, it belongs in top-level
  `plugins/`
- if it is domain-specific, it belongs in the app

This is the main placement rule for new code.

## App Shape

Apps should stay thin.

They should own:
- domain models
- domain controllers and routes
- domain services
- app-specific configuration

They should inherit:
- shared bootstrap behavior
- shared middleware
- framework-owned plugins
- shared API/runtime conventions

PHPSprinkles apps should keep `config/bootstrap.php` thin by loading app
`paths.php` and delegating to `PHPSprinkles\Bootstrap\Bootstrapper`.

## Framework Shape

The framework owns:
- `PHPSprinkles\BaseApplication`
- shared bootstrap behavior
- shared middleware defaults
- shared service/container hooks
- shared base model abstractions
- framework-owned plugin loading

Framework-owned plugins are loaded by the framework, not by apps.

## ORM Direction

Shared ORM behavior should live in framework base classes when it is truly
platform-wide.

Current direction:
- app table classes should extend `PHPSprinkles\Model\Table\AppTable`
- schema-driven conventions are preferred over repeated app setup
- example: a nullable datetime `deleted` column enables soft-delete behavior

The same pattern can later be applied to shared entity behavior through a
framework base entity class once there are enough repeated needs.

## Testing Direction

Testing should follow the same layering as runtime ownership.

- plugin tests should own plugin behavior
- framework tests should verify shared wiring
- app tests should verify real end-to-end HTTP behavior

Long-term API integration coverage should live mainly in runnable apps such as
`red-crm`, not only in framework-local tests.

## Related Docs

- execution order and next work: [roadmap.md](roadmap.md)
- plugin placement and lifecycle: [plugin-development.md](plugin-development.md)
- future customization docs system:
  [customization-cookbook-plan.md](customization-cookbook-plan.md)
