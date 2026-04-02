---
title: "xmfBlog: Reference Module"
description: Architecture map of xmfBlog showing how it demonstrates XMF 2.0 patterns
created: 2026-04-02
updated: 2026-04-02
---

# xmfBlog Reference Module

> Article management system demonstrating 25+ XMF 2.0 components.

xmfBlog is the primary reference module for XOOPS 4.0. It covers backend architecture, async processing, REST API design, and the broadest range of XMF framework integration.

**Source:** `htdocs/modules/xmfblog/`
**Requirements:** PHP 8.4+, XOOPS 2.5.11+

---

## Developer Documentation

Comprehensive guides written against xmfBlog's real source code:

| Document | What It Covers |
|---|---|
| **[TUTORIAL.md](xmfBlog-Docs/TUTORIAL.md)** | **50-chapter comprehensive guide** -- every XMF component explained with real code. Start here for deep learning. |
| [QUICKSTART.md](xmfBlog-Docs/QUICKSTART.md) | 10-step fast path from zero to working module |
| [ADVANCED_PATTERNS.md](xmfBlog-Docs/ADVANCED_PATTERNS.md) | Composition roots, cache strategy, event-driven architecture, API middleware |
| [MIGRATION_COOKBOOK.md](xmfBlog-Docs/MIGRATION_COOKBOOK.md) | Step-by-step playbook for modernizing legacy XOOPS modules |
| [FEATURE_CHECKLIST.md](xmfBlog-Docs/FEATURE_CHECKLIST.md) | Complete feature inventory with verification checklist |

The pages below are an **architecture map** -- use them to find the right source file, then read the docs above for the full explanation.

---

## Architecture

```
                    ┌─────────────────────────────────────────┐
                    │              BlogModule.php             │
                    │         (DI Container Bootstrap)        │
                    │          27 services registered         │
                    └────────────────┬────────────────────────┘
                                     │
          ┌──────────────────────────┼──────────────────────────┐
          │                          │                          │
    ┌─────▼──────┐           ┌──────▼───────┐          ┌──────▼───────┐
    │  Admin UI  │           │  Frontend    │          │   REST API   │
    │  admin/*.php│           │  index.php   │          │  api (via    │
    │  14 pages  │           │  5 pages     │          │  Pipeline)   │
    └─────┬──────┘           └──────┬───────┘          └──────┬───────┘
          │                          │                          │
          │                          │                  ┌───────▼────────┐
          │                          │                  │  Middleware    │
          │                          │                  │  RateLimit →   │
          │                          │                  │  Auth →        │
          │                          │                  │  Permission    │
          │                          │                  └───────┬────────┘
          │                          │                          │
          └──────────────────────────┼──────────────────────────┘
                                     │
                    ┌────────────────▼────────────────────────┐
                    │           Repositories                  │
                    │  PostRepository    CategoryRepository   │
                    │  (lifecycle hooks: audit, events, tags) │
                    └────────────────┬────────────────────────┘
                                     │
          ┌──────────────────────────┼──────────────────────────┐
          │                          │                          │
    ┌─────▼──────┐           ┌──────▼───────┐          ┌──────▼───────┐
    │  Entities  │           │   EventBus   │          │  XMF Infra   │
    │  Post      │           │  7 domain    │          │  Versioning  │
    │  Category  │           │  events      │          │  Audit       │
    │  (plain    │           │              │          │  Queue       │
    │   PHP 8.4) │           │              │          │  Cache       │
    └────────────┘           └──────────────┘          └──────────────┘
```

---

## XMF Component Map

Every XMF component xmfBlog uses, mapped to the source file that demonstrates it and the guide that explains the pattern.

### Core Architecture

| XMF Component | Source File | Guide |
|---|---|---|
| `Container` (DI) | `src/BlogModule.php` | [PSR-11 DI Guide](../Implementation-Guides/PSR-11-Dependency-Injection-Guide.md) |
| `EntityRepository` | `src/PostRepository.php` | [Repository & Query Patterns](../Implementation-Guides/Repository-Query-Patterns-Guide.md) |
| `#[Table]` / `#[Column]` attributes | `src/Post.php` | [Entity Mapping Guide](../Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md) |
| `EntityBridge` trait | `src/Post.php` | [Entity Mapping Guide](../Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md) |
| `Migration` / `MigrationRunner` | `src/BlogMigration.php` | [Entity Mapping Guide](../Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md) |
| `ModuleBootstrapInterface` | `src/BlogModule.php` | [Getting Started Tutorial](../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development.md) |

### Event System

| XMF Component | Source File | Guide |
|---|---|---|
| `EventBus` / `ListenerProvider` | `src/BlogModule.php` (registration) | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| `DomainEventTrait` | `src/Post.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| Domain events (7 total) | `src/Post*Event.php`, `src/Category*Event.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| `PreloadEventBridge` | `src/BlogModule.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |

### REST API & Middleware

| XMF Component | Source File | Guide |
|---|---|---|
| `ApiController` | `src/PostApiController.php` | [Error Handling Guide](../Implementation-Guides/Error-Handling-Validation-Guide.md) |
| `ApiResponse` | `src/PostApiController.php` | [Error Handling Guide](../Implementation-Guides/Error-Handling-Validation-Guide.md) |
| `Pipeline` / `MiddlewareInterface` | `src/AuthMiddleware.php` | [PSR-15 Middleware Guide](../Implementation-Guides/PSR-15-Middleware-Guide.md) |
| Rate limiting middleware | `src/ApiRateLimitMiddleware.php` | [Error Handling Guide](../Implementation-Guides/Error-Handling-Validation-Guide.md) |
| Permission middleware | `src/ApiPermissionMiddleware.php` | [Error Handling Guide](../Implementation-Guides/Error-Handling-Validation-Guide.md) |

### Async & Scheduling

| XMF Component | Source File | Guide |
|---|---|---|
| `Queue` / `QueueRunner` / `JobInterface` | `src/SendCommentNotificationJob.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| `#[QueuedJob]` attribute | `src/SendCommentNotificationJob.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| `TaskRegistry` / `TaskRunner` / `CronMatcher` | `src/ExpirePostsTask.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| `ScheduledTaskInterface` | `src/ExpirePostsTask.php`, `src/PruneAuditLogTask.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |

### Content & Media

| XMF Component | Source File | Guide |
|---|---|---|
| `MediaManager` / `ImageTransformer` | `src/PostRepository.php` (`handleImage()`) | -- |
| `LocalStorageAdapter` / `GdTransformerDriver` | `src/BlogModule.php` (registration) | -- |
| `TaxonomyManager` / `TaggableInterface` | `src/PostRepository.php` (`afterSave()`) | -- |
| `TagModuleBridge` | `src/PostRepository.php` (`syncToTagModule()`) | -- |
| `CommentHandler` / `CommentManager` | `src/BlogModule.php` (registration) | -- |

### Cross-Cutting

| XMF Component | Source File | Guide |
|---|---|---|
| `VersionManager` / `VersionAwareInterface` | `src/PostRepository.php` (`autoVersion()`) | -- |
| `AuditLogger` / `AuditableInterface` | `src/PostRepository.php` (`afterSave()`) | -- |
| `NotificationManager` / `InAppChannel` / `EmailChannel` | `src/SendCommentNotificationJob.php` | -- |
| `CacheManager` | `src/ApiRateLimitMiddleware.php` | -- |
| `ConfigManager` / `ConfigSchema` | `src/BlogModule.php` | -- |
| `AttributeValidator` | `src/BlogModule.php` | -- |
| `FormBuilder` | `src/BlogModule.php` (post/category forms) | -- |
| `ReportBuilder` | `src/BlogModule.php` | -- |
| `ItemPermission` | `src/BlogModule.php` | -- |
| `PluginManager` | `src/BlogModule.php` | -- |
| `ConventionRegistry` | `src/BlogModule.php` | -- |
| `ObjectStatus` enum | `src/Post.php`, `src/ExpirePostsTask.php` | -- |
| `ComparisonOperator` enum | Query builder usage | -- |

---

## Domain Events

| Event | Trigger | Dispatched From |
|---|---|---|
| `PostPublishedEvent` | Post saved with Published status | `PostRepository::afterSave()` |
| `PostUnpublishedEvent` | Existing post saved with non-Published status | `PostRepository::afterSave()` |
| `PostDeletedEvent` | Post deleted | `PostRepository::afterDelete()` |
| `PostEvent` | API create/update/delete | `PostApiController` |
| `CategoryCreatedEvent` | New category saved | `CategoryRepository::afterSave()` |
| `CategoryUpdatedEvent` | Existing category updated | `CategoryRepository::afterSave()` |
| `CategoryDeletedEvent` | Category deleted | `CategoryRepository::afterDelete()` |
| `CommentRejectedEvent` | Comment moderation rejection | Comment handler |

---

## Scheduled Tasks

| Task | Schedule | What It Does |
|---|---|---|
| `ExpirePostsTask` | `0 * * * *` (hourly) | Unpublishes posts past `date_expire` |
| `PruneOldVersionsTask` | `0 3 * * *` (daily 3 AM) | Cleans old version snapshots |
| `PruneAuditLogTask` | `0 4 * * 0` (weekly Sun 4 AM) | Trims old audit log entries |

---

## Database Schema

| Table | Purpose | Columns |
|---|---|---|
| `xmfblog_posts` | Blog articles | 27 columns (content, SEO, timestamps, audit, versioning) |
| `xmfblog_categories` | Hierarchical categories | 7 columns |
| `xmf_comments` | Shared multi-module comments | 11 columns |
| `xmf_versions` | Entity version snapshots | 7 columns |
| `xmf_queue_jobs` | Async job processing | 12 columns |
| `xmf_media` | Uploaded file metadata | 11 columns |
| `xmf_notifications` | In-app notifications | 8 columns |
| `xmf_audit_log` | Append-only activity log | 10 columns |
| `xmf_migrations` | Migration version tracking | 4 columns |
| `xmf_taxonomies` | Vocabulary definitions | 7 columns |
| `xmf_terms` | Terms within vocabularies | 7 columns |
| `xmf_term_items` | Term-to-item assignments | 4 columns |

---

## Key Source Files

If you only read five files, read these:

| File | Lines | Why |
|---|---|---|
| `src/BlogModule.php` | 471 | The complete DI container -- shows how every XMF component connects |
| `src/Post.php` | 198 | Entity pattern -- attributes, traits, interfaces, PHP 8.4 features |
| `src/PostRepository.php` | 354 | Lifecycle hooks -- the bridge between entities and framework services |
| `src/PostApiController.php` | 175 | API pattern -- validation, hydration, event dispatch |
| `src/SendCommentNotificationJob.php` | 68 | Async pattern -- queue jobs with `#[QueuedJob]` attribute |

---

## What xmfBlog Does NOT Demonstrate

These are covered by [xmfPortal](xmfPortal.md) instead:

- Widget system (registry, renderer, composer, areas)
- Rating system
- DataProvider pattern for content aggregation
- Dedicated event listener classes (xmfBlog uses lifecycle hooks)
- A/B testing via events
- Shortcode integration
- Cross-module content queries
