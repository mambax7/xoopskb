# XMF Advanced Patterns with `xmfblog`

This guide covers advanced implementation patterns for XOOPS module developers who already understand the basics.

Primary references:
- [BlogModule.php](../../../modules/xmfblog/src/BlogModule.php)
- [Post.php](../../../modules/xmfblog/src/Post.php)
- [index.php](../../../modules/xmfblog/index.php)
- [PostApiController.php](../../../modules/xmfblog/src/PostApiController.php)

## 1) Composition Root as Architecture Boundary

Pattern:
1. All service wiring happens in `BlogModule::boot()`.
2. Controllers/pages do not instantiate infra dependencies.

Advanced benefit:
1. Clear separation between object graph construction and runtime behavior.
2. Easier testing and selective substitution.

## 2) Typed Entities on Top of `XoopsObject`

Pattern in `Post`:
1. Keep `initVar()` schema compatibility.
2. Add traits for cross-cutting behavior.
3. Add property hooks as typed API layer.

Advanced recommendations:
1. Keep field invariants in setters (length, format).
2. Use enums for status fields.
3. Use dedicated JSON field helpers for array-like columns.

## 3) Lifecycle Hooks in Handlers

Pattern:
1. Use `beforeSave()` to centralize timestamp and invariant updates.
2. Avoid duplicating those mutations in each controller.

Where used:
- [PostRepository.php](../src/PostRepository.php) — DDD path (EntityRepository with lifecycle hooks)
- [CommentHandler.php](../src/CommentHandler.php) — Classic path (XoopsPersistableObjectHandler with HandlerTrait)

## 4) Stateful List Views Done Correctly

Pattern in `index.php`/`category.php`:
1. Parse sort/order/page/per_page via `TableStateManager::fromRequest()`.
2. Allowlist sort columns.
3. Build one criteria for data and a clone for count.

Why this is advanced:
1. Prevents subtle count/list drift.
2. Gives deterministic list behavior under user-supplied query params.

## 5) Cache Keys + Tags Strategy

Current strategy:
1. Composite key for list pages.
2. Dedicated keys for sidebar/trending.
3. Tag groups for invalidation: `post_list`, `category_sidebar`.

Advanced rules:
1. Include all query-shaping parameters in key.
2. Use events for invalidation; avoid ad hoc deletes everywhere.
3. Keep TTL bounded and documented.

## 6) Domain Events as Extension Points

Pattern:
1. UI/API actions dispatch events.
2. Listeners perform side effects (cache invalidation, queue push).

Used in:
- [PostEvent.php](../../../modules/xmfblog/src/PostEvent.php)
- listeners in [BlogModule.php](../../../modules/xmfblog/src/BlogModule.php)

Advanced evolution path:
1. Add search indexing listener.
2. Add webhook listener.
3. Add audit logging listener.

No controller rewrite needed.

## 7) Async Processing with Queue Jobs

Pattern:
1. Dot event `post.viewed` pushes `PostViewedAnalyticsJob`.
2. Job handles deferred side effects.

Reference:
- [PostViewedAnalyticsJob.php](../../../modules/xmfblog/src/PostViewedAnalyticsJob.php)

Advanced guidance:
1. Keep job payload small and serializable.
2. Make jobs idempotent where possible.
3. Use queue for non-critical latency-sensitive work.

## 8) API Layer: Middleware + Validation + Events

Pattern in `PostApiController`:
1. Validate payload before hydrate/fill.
2. Reuse base API controller for CRUD flow.
3. Dispatch domain events on mutation.

Pattern in `AuthMiddleware`:
1. Allow anonymous read methods.
2. enforce auth on writes.
3. Return pipeline-compatible short-circuit response shape.

References:
- [AuthMiddleware.php](../../../modules/xmfblog/src/AuthMiddleware.php)
- [api.php](../../../modules/xmfblog/api.php)

## 9) Progressive Enhancement UI Strategy

Pattern:
1. Server-rendered templates first.
2. Small JS only when needed (reply-to comment behavior).
3. No hard dependency on heavy frontend toolchains.

References:
- [xmfblog_view.tpl](../../../modules/xmfblog/templates/xmfblog_view.tpl)
- [style.css](../../../modules/xmfblog/assets/css/style.css)

## 10) Performance + Maintainability Pattern Set

Recommended defaults:
1. Cache list pages and blocks.
2. Event-driven cache invalidation.
3. Criteria-based filtered counts.
4. Strong API payload validation.
5. Clear language constants for all UI states.

## Advanced Review Checklist

1. Are all side effects outside controllers where possible?
2. Are events emitted for all write operations?
3. Are cache keys/invalidations complete?
4. Is sort/filter input allowlisted?
5. Are API write endpoints authenticated and validated?
6. Are module templates mobile-responsive and Smarty-safe?

## Continue

For migration of old modules, use:
- [MIGRATION_COOKBOOK.md](MIGRATION_COOKBOOK.md)
