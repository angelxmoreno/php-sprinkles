# Plugin Development

## Plugin Buckets

There are two plugin classes in this monorepo.

### Framework-Owned Plugins

Location:

```text
framework/PHPSprinkles/plugins/<PluginName>
```

Use this when the capability:
- should exist in every app
- should be loaded by the framework
- is cleaner as a module than as framework core

Examples:
- request ID propagation
- CORS conventions
- auth and JWT, if they stay universal

### Top-Level Plugins

Location:

```text
plugins/<PluginName>
```

Use this when the capability:
- is selective
- is not guaranteed for every app
- may later deserve an independent lifecycle

## Placement Rule

Decide in this order:

1. inseparable framework behavior -> `framework/PHPSprinkles/src`
2. universal modular behavior -> `framework/PHPSprinkles/plugins`
3. selective or extractable behavior -> `plugins/`
4. domain behavior -> app

## Development Rule

Create plugins in their final ownership location from day one.

Do not start in one location and move later unless you are intentionally
extracting the plugin.

Framework plugins should be wired by `PHPSprinkles\BaseApplication`, not by each
app.

## Future Tooling

Planned scaffolding commands:

```bash
sprinkles build:framework-plugin <name>
sprinkles build:plugin <name>
```

These do not exist yet.

Framework plugin behavior that changes how all apps work should also be
documented in the future cookbook system described in
[customization-cookbook-plan.md](customization-cookbook-plan.md).
