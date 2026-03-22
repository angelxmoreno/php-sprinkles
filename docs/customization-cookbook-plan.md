# PHPSprinkles Customization Cookbook Plan

This document defines the documentation system we should add for recording every intentional PHPSprinkles customization over baseline CakePHP.

This is a planning document only.

It does not create the cookbook yet. It defines the structure, rules, and rollout so implementation can happen later without re-deciding the shape.

## Purpose

PHPSprinkles already owns shared framework behavior that differs from stock CakePHP.

Examples:
- shared `BaseApplication` wiring
- framework-owned plugin loading
- request ID conventions
- CORS conventions
- future auth and JWT conventions

Those changes need one canonical documentation surface for app builders.

The goal is not to rewrite the CakePHP cookbook.

The goal is to make it easy to answer:
- what CakePHP would normally do here
- what PHPSprinkles changes
- why PHPSprinkles changes it
- how an app builder should use or override that behavior

## Audience

Primary audience:
- app builders working on APIs that extend PHPSprinkles

Secondary audience:
- framework contributors who need a stable place to record new customizations

This means the docs should explain behavior and usage first, then point to implementation details only where needed.

## Scope

The cookbook should document PHPSprinkles customizations only.

It is not intended to become:
- a full PHPSprinkles manual
- a replacement for CakePHP documentation
- a dump of internal notes or architecture history

If CakePHP behavior is unchanged, the cookbook should link out to CakePHP rather than restating the entire topic.

## Delivery Format

The initial implementation should be repo-authored Markdown under `docs/`.

The structure should be compatible with a future static documentation site, but v1 should not require a generator, build step, or publishing platform.

Implications:
- keep documents readable directly in GitHub
- use stable filenames and section names
- organize content so it can later map cleanly to a static site nav
- avoid generator-specific markup in v1

## Information Architecture

The cookbook should live under:

```text
docs/cookbook/
```

Planned entry documents:
- `docs/cookbook/index.md`
- `docs/cookbook/customizations-index.md`
- `docs/cookbook/conventions.md`

Planned organization model:
- feature-led, not Cake-topic-led

That means the main navigation should be based on PHPSprinkles capabilities such as:
- `BaseApplication`
- middleware stack
- framework-owned plugins
- `PHPSprinklesRequestId`
- `PHPSprinklesCors`
- future auth plugin behavior

This is the chosen model because app builders are trying to understand PHPSprinkles behavior, not browse the full CakePHP surface area.

## Required Page Template

Each customization entry should use the same minimum structure.

Required sections:
1. What CakePHP does by default
2. What PHPSprinkles changes
3. Why PHPSprinkles changes it
4. How to use it as an app builder
5. How to override or extend it when applicable
6. Relevant config keys
7. Relevant framework or plugin ownership
8. Relevant code entrypoints
9. Related CakePHP docs

This template is intentionally repetitive.

The repetition is useful because it makes the divergence from CakePHP explicit and keeps pages scannable.

## Documentation Rule

Any new framework-level customization must ship with cookbook documentation in the same PR.

That means:
- add a new cookbook entry when introducing a new customization
- update an existing cookbook entry when changing documented behavior
- do not merge framework-level behavior changes without documenting the resulting platform behavior

This rule applies to:
- `framework/PHPSprinkles/src`
- `framework/PHPSprinkles/plugins/*`
- shared runtime conventions inherited automatically by apps

This rule does not require cookbook pages for ordinary domain-app behavior.

## Relationship To Existing Docs

Existing docs already have useful roles:
- `docs/architecture.md` explains structural ownership and layering
- `docs/plugin-development.md` explains plugin placement and lifecycle
- `docs/platform-roadmap.md` captures future platform direction
- `docs/implementation-plan.md` captures execution sequencing

The cookbook should not replace those documents.

Instead:
- architecture docs explain where behavior belongs
- roadmap docs explain what is planned
- the cookbook explains implemented customizations and how app builders consume them

When the cookbook is implemented, these documents should link into it where relevant instead of absorbing cookbook content directly.

## Initial Seed Topics

When implementation starts, the first cookbook entries should cover the customizations that already exist in code or are explicitly framework-owned:
- shared `BaseApplication`
- framework-managed plugin loading
- shared middleware stack
- `PHPSprinklesRequestId`
- `PHPSprinklesCors`

Future additions should include:
- auth conventions
- JWT conventions
- registration/public-auth overrides
- any shared API response or error conventions once standardized

## Rollout Plan

The future implementation should happen in this order:

1. Create the `docs/cookbook/` structure and index pages.
2. Add the conventions page and required page template.
3. Seed the cookbook with entries for the currently implemented customizations.
4. Link the cookbook from the monorepo README and framework README.
5. Update contributor/review expectations so framework-level customizations require cookbook updates in the same PR.

## Success Criteria

The cookbook initiative is successful when:
- an app builder can identify PHPSprinkles-specific behavior without reading framework source first
- every current framework-level customization has a cookbook entry
- contributors know where to document new customizations
- the docs read cleanly in the repo today and can migrate to a static site later without major restructuring

## Non-Goals For This Planning Phase

This planning phase does not:
- create `docs/cookbook/`
- write the first cookbook entries
- introduce a static site generator
- rewrite the current architecture or plugin docs into cookbook format

Those changes belong to the later cookbook implementation phase.
