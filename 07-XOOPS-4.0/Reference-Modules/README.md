---
title: Reference Modules
description: Two production modules that demonstrate XMF 2.0 capabilities from different angles
created: 2026-04-02
updated: 2026-04-02
---

# Reference Modules

XOOPS 4.0 ships with two reference modules that demonstrate XMF 2.0 from complementary angles. Together they cover the full framework surface. Neither is a toy -- both are production-ready, installable modules.

## Two Modules, Two Perspectives

| | **xmfBlog** | **xmfPortal** |
|---|---|---|
| **What it is** | Article management system | Page builder with widget composition |
| **Best for learning** | Backend architecture, async processing, API design | Widget system, event listeners, cross-module data |
| **XMF components used** | 25+ | 12+ |
| **Domain complexity** | Higher (posts, categories, tags, comments, media) | Lower (pages, sections) |
| **Unique features** | REST API + middleware pipeline, queue jobs, scheduled tasks, notifications, media management, taxonomy, plugins | Widget registry + renderer, rating system, DataProviders, A/B testing, shortcodes |

### Shared Patterns (demonstrated by both)

Both modules implement the same core XMF patterns, so you can compare how each applies them:

- Entity classes with `#[Table]` and `#[Column]` attributes
- PHP 8.4 `public private(set)` asymmetric visibility
- `EntityRepository` with `beforeSave()` / `afterSave()` / `afterDelete()` lifecycle hooks
- Domain events via `DomainEventTrait` + `EventBus`
- `Container` service registration with singletons
- `VersionAwareInterface` for automatic versioning
- `AuditableInterface` for audit trail logging
- `ModuleBootstrapInterface` for centralized initialization

## How to Use These Docs

**Start with the module overview page** -- it maps every source file to the XMF feature it demonstrates and links to the Implementation Guide that explains the pattern.

**Don't read these docs instead of the code** -- the source is the single source of truth. These pages tell you *where to look* and *why it's built that way*.

| I want to... | Start here |
|---|---|
| Understand the overall architecture | [xmfBlog overview](xmfBlog.md) or [xmfPortal overview](xmfPortal.md) |
| Learn a specific XMF pattern | [Implementation Guides](../Implementation-Guides/Implementation-Guides.md) |
| Build a module step by step | [Getting Started Tutorial](../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development.md) |
| Walk through real code | [Anatomy of an XMF Module](../Tutorials/Anatomy-of-an-XMF-Module.md) |
| Add a REST API | [REST API Tutorial](../Tutorials/Adding-REST-API-to-Your-Module.md) |

## Source Code

| Module | Location |
|--------|----------|
| xmfBlog | `htdocs/modules/xmfblog/` |
| xmfPortal | `htdocs/modules/xmfportal/` |

Both modules follow the same directory convention:

```
modules/{name}/
├── src/                  # PHP classes (PSR-4 namespace)
│   ├── {Name}Module.php  # Bootstrap + DI container
│   ├── {Entity}.php      # Domain entities
│   ├── {Entity}Repository.php
│   └── ...
├── admin/                # Admin panel pages
├── templates/            # Smarty templates
├── blocks/               # XOOPS block definitions
├── sql/                  # Database schema
├── language/english/     # Language constants
├── assets/               # CSS, JS, images
├── autoload.php          # PSR-4 autoloader
└── xoops_version.php     # Module manifest
```
