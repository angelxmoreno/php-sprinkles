# PHPSprinklesCors

`PHPSprinklesCors` is a framework-owned PHPSprinkles plugin.

Its purpose is intentionally small:
- provide a correct global CORS policy for PHPSprinkles APIs
- use CakePHP 5's native CORS builder instead of custom header assembly
- make local SPA development easy without opening production by default

It lives inside the framework because every API should inherit the same CORS
posture automatically.

## Defaults

- `debug=true`: common localhost origins are allowed automatically
- `debug=false`: no CORS allow headers are emitted unless the app configures
  `Cors.allowOrigin`

## Development Scope

This plugin should be fully developed inside its own directory:
- middleware implementation
- plugin hook wiring
- plugin-local tests

It should be wired by the framework, not by individual apps.
