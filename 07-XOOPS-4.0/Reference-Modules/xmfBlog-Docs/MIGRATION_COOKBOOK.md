# XMF Migration Cookbook for Existing XOOPS Modules

A practical, low-risk migration playbook to modernize legacy modules using the same patterns proven in `xmfblog`.

Goal:
- improve architecture and UX
- keep compatibility
- avoid big-bang rewrites

## Migration Principles

1. Preserve public entry points (`index.php`, `admin/*.php`, blocks).
2. Keep `XoopsObject` + handlers during migration.
3. Add new architecture behind existing behavior.
4. Ship in small reversible steps.
5. Use config toggles when introducing risky features.

---

## Phase 0: Baseline and Safety Nets

Before code changes:
1. Document current pages/flows and query params.
2. Capture DB schema and key templates.
3. List extension points used by third-party customizations.

Output checklist:
1. all main routes listed
2. all blocks listed
3. all major template variable names listed

---

## Phase 1: Add Container Without Changing Behavior

Action:
1. Add `ModuleBootstrap::boot()` (as in `BlogModule::boot()`).
2. Register db, handlers, config in container.
3. Replace inline handler creation with `$container->get(...)`.

Compatibility impact:
- none expected if service wiring mirrors current behavior.

Reference:
- [BlogModule.php](../../../modules/xmfblog/src/BlogModule.php)

---

## Phase 2: Stabilize Listing Pages

Action:
1. Replace ad hoc sort/page parsing with `TableStateManager::fromRequest()`.
2. Add sort allowlist.
3. Ensure count criteria equals list filters.

Why now:
1. immediate reliability win
2. low risk

References:
- [index.php](../../../modules/xmfblog/index.php)
- [category.php](../../../modules/xmfblog/category.php)

---

## Phase 3: Introduce Caching for Read Paths

Action:
1. Add list and sidebar caching with `remember()`.
2. Add block-level caching.
3. Introduce tag names for grouped invalidation.

Suggested tags:
1. `post_list`
2. `category_sidebar`

References:
- [index.php](../../../modules/xmfblog/index.php)
- [blog_blocks.php](../../../modules/xmfblog/blocks/blog_blocks.php)

---

## Phase 4: Add Events Around Writes

Action:
1. Dispatch domain events on create/update/delete.
2. Register listeners in bootstrap.
3. Move side effects from controllers into listeners.

Immediate use case:
1. cache invalidation

Reference:
- [PostApiController.php](../../../modules/xmfblog/src/PostApiController.php)
- [PostEvent.php](../../../modules/xmfblog/src/PostEvent.php)

---

## Phase 5: Add Queue for Non-Critical Work

Action:
1. Register `queue` service.
2. Ensure queue table exists.
3. Push job from event listener.

Reference:
- [PostViewedAnalyticsJob.php](../../../modules/xmfblog/src/PostViewedAnalyticsJob.php)

Migration-safe examples:
1. analytics updates
2. email notifications
3. background indexing

---

## Phase 6: Add API in Stages

Stage A: read-only
1. Add `api.php` and controller index/show.
2. Keep public GET.

Stage B: write endpoints
1. Add middleware auth for writes.
2. Add payload validation.
3. Emit same domain events as UI writes.

References:
- [api.php](../../../modules/xmfblog/api.php)
- [AuthMiddleware.php](../../../modules/xmfblog/src/AuthMiddleware.php)

---

## Phase 7: Modernize Entities Gradually

Action:
1. Keep `initVar()` fields unchanged first.
2. Add traits one-by-one where valuable.
3. Add property hooks field-by-field.

Do not:
1. remove `setVar/getVar` compatibility in one release.

Reference:
- [Post.php](../../../modules/xmfblog/src/Post.php)

---

## Phase 8: UI Modernization with Template Compatibility

Action:
1. Keep existing template file names initially.
2. Refactor markup/CSS internals for responsive modern layout.
3. Keep key assigned variable names stable during transition.

References:
- [xmfblog_index.tpl](../../../modules/xmfblog/templates/xmfblog_index.tpl)
- [style.css](../../../modules/xmfblog/assets/css/style.css)

---

## Phase 9: Language and Config Migration

Action:
1. Add new language constants.
2. Keep old constants where possible.
3. Add config toggles for new behavior.

Reference:
- [main.php](../../../modules/xmfblog/language/english/main.php)
- [xoops_version.php](../../../modules/xmfblog/xoops_version.php)

---

## Common Migration Scenarios

### Scenario A: Legacy module with heavy `CriteriaCompo`

Plan:
1. keep criteria logic
2. introduce `TableStateManager` for input parsing
3. add cache on top

### Scenario B: Module with direct SQL in pages

Plan:
1. move SQL access into handlers/repository
2. keep controller outputs unchanged
3. add tests around old routes

### Scenario C: No API today

Plan:
1. read-only API first
2. auth middleware second
3. write endpoints third

---

## Release-by-Release Rollout Example

Release 1:
1. container bootstrap
2. safe list paging/sorting
3. cache for list/blocks

Release 2:
1. domain events
2. cache invalidation listeners
3. read-only API

Release 3:
1. write API + middleware
2. async queue job
3. UI refresh

Release 4:
1. entity typing enhancements
2. additional advanced features

---

## Migration Acceptance Checklist

1. Existing routes still work.
2. Existing templates still render.
3. Existing database fields still supported.
4. Pagination is correct under all filters.
5. Caches invalidate after writes.
6. API write endpoints are protected.
7. No module-level regressions in blocks/admin/main pages.

---

## Recommended Developer Workflow

1. Apply one phase.
2. Test manually in XOOPS.
3. Run static/lint/test pipeline.
4. Ship small release.
5. Repeat.

This is how you modernize safely without breaking downstream modules.
