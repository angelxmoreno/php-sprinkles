# PHPSprinklesRequestId

`PHPSprinklesRequestId` is the first real shared plugin in the monorepo.

Its purpose is intentionally small:
- attach a request ID to every request/response flow
- reuse an incoming `X-Request-Id` when present
- generate one when missing

It exists to validate the shared plugin workflow before larger plugins such as auth or JWT are introduced.

## Development Scope

This plugin should be fully developed inside its own directory:
- middleware implementation
- plugin hook wiring
- plugin-local tests

It should only be wired into the framework once the plugin itself is stable.
