# XMF Quickstart for XOOPS Developers

A beginner-friendly path to build a modern XOOPS module with the new XMF, using `xmfblog` as the exact reference.

Target stack:
- PHP `8.4+`
- Smarty `5`
- XOOPS module compatibility preserved

## Goal

In one short implementation cycle, you will:
1. Bootstrap a module with container-based services.
2. Add one entity + handler.
3. Render one frontend listing page.
4. Add safe pagination/sorting.
5. Add caching.
6. Add a simple API endpoint.

---

## Step 1: Copy the Baseline Module Layout

Use `xmfblog` as your baseline structure:

- `xoops_version.php`
- `header.php`, `footer.php`
- `index.php`
- `src/`
- `templates/`
- `language/english/`

Reference: `modules/xmfblog/`

Why: this keeps module loading, templates, and config behavior consistent with existing XOOPS conventions.

---

## Step 2: Create a Composition Root

Create a module bootstrap class (pattern: `BlogModule::boot()`):

Reference: [BlogModule.php](../../../modules/xmfblog/src/BlogModule.php)

Register services in one place:
1. `db`
2. `events`, `event_bus`
3. `config`
4. `cache`
5. `your_handler`
6. `your_repo`

Use in pages:
- `header.php` boots the container and helper.
- Frontend pages use `$container->get('service_name')` to access services.

> **Architectural note:** XOOPS 2.5.x uses a page-controller architecture where each PHP file (`index.php`, `category.php`) is a standalone entry point — there are no controller classes to inject into. Using `$container->get()` in page files is the composition root for that entry point. The API layer demonstrates the target pattern: `PostApiController` receives dependencies via constructor injection through the middleware pipeline. When XOOPS 4.0 adds a router/dispatcher, frontend pages will follow the same constructor injection pattern.

---

## Step 3: Create Your First Entity

Start with one `XoopsObject` model.

Reference: [Post.php](../../../modules/xmfblog/src/Post.php)

Beginner-safe approach:
1. Keep `initVar()` fields as usual.
2. Add only one modern enhancement first (typed property hook or one trait).
3. Keep `setVar/getVar` support for compatibility.

---

## Step 4: Add a Repository

XMF supports two persistence paths:

**Classic path:** Create `YourEntityHandler` extending `\XoopsPersistableObjectHandler` with `HandlerTrait`.
Reference: [CommentHandler.php](../src/CommentHandler.php) — for XoopsObject-based entities.

**DDD path (recommended for new modules):** Create `YourEntityRepository` extending `Xmf\Repository\EntityRepository`.
Reference: [PostRepository.php](../src/PostRepository.php) — for plain PHP entities with `#[Column]` attributes.

Do this:
1. Extend `EntityRepository` and pass `($db, YourEntity::class, $eventBus)` to the parent.
2. Override `beforeSave()` for timestamps and auto-versioning.
3. Override `afterSave()` for audit logging and domain events.

---

## Step 5: Build the First Page (`index.php`)

Reference: [index.php](../../../modules/xmfblog/index.php)

Use:
1. request input (`op`, `page`, filters)
2. handler/repository query
3. assign data to Smarty
4. set `template_main`

Minimum output variables:
- `items`
- `total`
- `page`
- `total_pages`

---

## Step 6: Add Safe Pagination + Sorting

Use `TableStateManager::fromRequest()`.

Pattern from `xmfblog`:
1. default sort + order
2. allowlisted sortable fields
3. per-page max cap

This prevents malformed sort input and keeps behavior predictable.

---

## Step 7: Add Caching

Reference:
- [index.php](../../../modules/xmfblog/index.php)
- [blog_blocks.php](../../../modules/xmfblog/blocks/blog_blocks.php)

Use cache `remember()` around expensive listing queries.

Basic key pattern:
- `list:{filter}:{sort}:{order}:{page}:{perPage}`

---

## Step 8: Add One Block

Create a simple “recent items” block first.

Reference: [blog_blocks.php](../../../modules/xmfblog/blocks/blog_blocks.php)

Good block rules:
1. small query
2. cached result
3. minimal template logic

---

## Step 9: Add a Minimal API

References:
- [api.php](../../../modules/xmfblog/api.php)
- [PostApiController.php](../../../modules/xmfblog/src/PostApiController.php)

Start with `GET` only. Keep it read-only first.

Then add middleware and write methods later.

---

## Step 10: Upgrade Templates and CSS

References:
- [xmfblog_index.tpl](../../../modules/xmfblog/templates/xmfblog_index.tpl)
- [style.css](../../../modules/xmfblog/assets/css/style.css)

Quick wins:
1. CSS variables
2. responsive grid
3. clean card layout
4. keep Smarty logic simple

---

## Quickstart Completion Checklist

1. Module boots via container.
2. One entity + repository works.
3. `index.php` lists data with safe paging.
4. Cache is active for list view.
5. One block renders and is cached.
6. One read API route returns JSON.
7. Smarty template renders correctly in XOOPS.

---

## Next Step

After this quickstart, continue with:
- [ADVANCED_PATTERNS.md](ADVANCED_PATTERNS.md)
- [MIGRATION_COOKBOOK.md](MIGRATION_COOKBOOK.md)
