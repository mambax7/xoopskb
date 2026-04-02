# XMF Blog Developer Docs

This folder contains the complete developer documentation for building modern XOOPS modules with the new XMF, using `xmfblog` as the live reference implementation.

## Start Here

1. Full guided walkthrough: [TUTORIAL.md](TUTORIAL.md)
2. Fast onboarding path: [QUICKSTART.md](QUICKSTART.md)
3. Deep architecture patterns: [ADVANCED_PATTERNS.md](ADVANCED_PATTERNS.md)
4. Legacy module modernization playbook: [MIGRATION_COOKBOOK.md](MIGRATION_COOKBOOK.md)

## Which Document Should I Read?

1. New to XMF: start with `QUICKSTART.md`, then `TUTORIAL.md`.
2. Already shipping XOOPS modules: read `TUTORIAL.md` first.
3. Refactoring older modules safely: read `MIGRATION_COOKBOOK.md`.
4. Want higher architectural rigor: read `ADVANCED_PATTERNS.md`.

## Reference Module Files

These files are in your XOOPS installation under `modules/xmfblog/`:

Architecture bootstrap:
- `src/BlogModule.php`

Frontend controllers:
- `index.php`
- `category.php`

API:
- `api.php`
- `src/PostApiController.php`
- `src/AuthMiddleware.php`

Events and queue:
- `src/PostPublishedEvent.php`
- `src/PostDeletedEvent.php`
- `src/SendCommentNotificationJob.php`

UI layer:
- `templates/xmfblog_index.tpl`
- `templates/xmfblog_category.tpl`
- `templates/xmfblog_view.tpl`
- `assets/css/style.css`

See also: [xmfBlog Architecture Map](../xmfBlog.md) for a complete source file inventory.

## Compatibility Targets

- PHP: `8.4+`
- Smarty: `5`
- XOOPS compatibility: preserved through progressive migration patterns
