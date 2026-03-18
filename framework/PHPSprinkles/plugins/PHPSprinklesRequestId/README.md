# PHPSprinklesRequestId

`PHPSprinklesRequestId` is a framework-owned PHPSprinkles plugin.

Its purpose is intentionally small:
- attach a request ID to every request/response flow
- reuse an incoming `X-Request-Id` when present
- generate one when missing

It lives inside the framework because every API should inherit this behavior automatically.

## Development Scope

This plugin should be fully developed inside its own directory:
- middleware implementation
- plugin hook wiring
- plugin-local tests

It should be wired by the framework, not by individual apps.
