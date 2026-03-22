# PHPSprinklesDebugPage

`PHPSprinklesDebugPage` is a framework-owned PHPSprinkles plugin.

Its purpose is intentionally small:
- provide a shared debug-only `/debug` route for PHPSprinkles apps
- return a JSON environment/setup status payload for API apps
- keep that developer-only page out of non-debug environments

It lives inside the framework because every PHPSprinkles API app should inherit
the same diagnostics endpoint automatically during development.
