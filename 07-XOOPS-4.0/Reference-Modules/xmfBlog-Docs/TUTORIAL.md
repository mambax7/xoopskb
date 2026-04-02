# XMF Architecture Guide for XOOPS Module Developers

A hands-on tutorial using the **XMF Blog Module** (`xmfblog`) as a working reference implementation. Every code example comes from real, tested module code.

---

## Table of Contents

1. [Introduction: Why XMF?](#1-introduction-why-xmf)
2. [Architecture Overview](#2-architecture-overview)
3. [Getting Started: The Container](#3-getting-started-the-container)
4. [Defining Entities with Object Traits](#4-defining-entities-with-object-traits)
5. [Handlers and Lifecycle Hooks](#5-handlers-and-lifecycle-hooks)
6. [The Repository Pattern](#6-the-repository-pattern)
7. [Querying Data with QueryBuilder](#7-querying-data-with-querybuilder)
8. [Type-Safe Enums](#8-type-safe-enums)
9. [The Event System](#9-the-event-system)
10. [Configuration Management](#10-configuration-management)
11. [Caching](#11-caching)
12. [Middleware Pipeline and HTTP](#12-middleware-pipeline-and-http)
13. [Building a REST API](#13-building-a-rest-api)
14. [Permissions](#14-permissions)
15. [SEO Meta Generation](#15-seo-meta-generation)
16. [Presentation: Forms, Tables, and State](#16-presentation-forms-tables-and-state)
17. [Database Migrations](#17-database-migrations)
18. [Value Objects](#18-value-objects)
19. [Legacy Compatibility: The Preload Bridge](#19-legacy-compatibility-the-preload-bridge)
20. [Service Manager](#20-service-manager)
21. [Content Versioning](#21-content-versioning)
22. [Media Management](#22-media-management)
23. [Queue and Async Jobs](#23-queue-and-async-jobs)
24. [Notifications](#24-notifications)
25. [Audit Logging](#25-audit-logging)
26. [Report Builder](#26-report-builder)
27. [Import/Export Pipelines](#27-importexport-pipelines)
28. [Domain Events](#28-domain-events)
29. [Scheduled Tasks](#29-scheduled-tasks)
30. [Plugin System](#30-plugin-system)
31. [Convention Registry](#31-convention-registry)
32. [Migration Runner](#32-migration-runner)
33. [Time and Code Savings](#33-time-and-code-savings)
34. [Putting It All Together: Module Bootstrap](#34-putting-it-all-together-module-bootstrap)
35. [Frontend Pages](#35-frontend-pages)
36. [Admin Panel](#36-admin-panel)
37. [Blocks](#37-blocks)
38. [Module Cloning](#38-module-cloning)
39. [Block Management](#39-block-management)
40. [Testdata Management](#40-testdata-management)
41. [Testing Your Module](#41-testing-your-module)
42. [Component Reference Table](#42-component-reference-table)
43. [Migration Path from Legacy Code](#43-migration-path-from-legacy-code)
44. [Taxonomy System](#44-taxonomy-system)
45. [Two-Path Entity Architecture](#45-two-path-entity-architecture)
46. [Command Bus (CQRS-lite)](#46-command-bus-cqrs-lite)
47. [Enhanced Value Objects](#47-enhanced-value-objects)
48. [Pagination](#48-pagination)
49. [Multi-Tenancy](#49-multi-tenancy)
50. [Wave 8 Enhancements — xmfblog Module Updates](#50-wave-8-enhancements--xmfblog-module-updates)

---

## 1. Introduction: Why XMF?

XOOPS modules have traditionally relied on `XoopsObject`, `XoopsPersistableObjectHandler`, `CriteriaCompo`, and scattered global functions. This works, but it creates problems as modules grow:

- **No separation of concerns** — handlers mix persistence, validation, and business logic
- **No type safety** — everything is strings and arrays
- **No testability** — global state and tight coupling make unit testing impractical
- **No modern patterns** — no dependency injection, no events, no middleware

XMF solves these problems by introducing **141+ architectural components** that layer cleanly on top of the existing XOOPS foundation. Nothing is removed or replaced — XMF extends what already works.

### Design Principles

1. **Non-breaking** — Every XMF component works alongside existing XOOPS code. You can adopt one component at a time.
2. **Trait-based composition** — Entity behaviors are composed via PHP traits, not deep inheritance chains.
3. **Constructor injection** — Dependencies are wired through a DI container, never through globals.
4. **Backward compatible** — `QueryBuilder::fromCriteria()` bridges old code to new. `PreloadEventBridge` converts legacy preloads to modern events.
5. **PHP 8.4+** — Takes full advantage of enums, readonly classes, property hooks, and typed properties.

### What You'll Learn

This tutorial walks through the XMF Blog Module — a complete, installable XOOPS module with:
- Posts with 10 entity traits (change tracking, SEO, JSON fields, multilingual, audit trail, versioning, media, domain events)
- Hierarchical categories with tree operations
- Nested comments with spam filtering plugin
- REST API with middleware pipeline
- Admin panel with auto-generated forms, tables, reports, and audit log
- Image upload with server-side crop/resize via MediaManager
- Async job processing via Queue (comment notifications)
- Multi-channel notifications (email + in-app)
- Append-only audit trail for all entity changes
- Declarative reports with CSV export via ReportBuilder
- CSV/JSON import/export pipelines
- Domain events for post lifecycle (published, deleted)
- Scheduled tasks (auto-expire posts, prune old versions/audit entries)
- Attribute-driven plugin system with event listeners
- Database migrations with version tracking
- Full integration test suite

Every section explains **what** the component does, **why** it exists, and shows **exactly how** the blog module uses it.

---

## 2. Architecture Overview

XMF components are organized in four tiers, ordered by dependency:

```
┌─────────────────────────────────────────────────────────┐
│  TIER 3: Infrastructure                                 │
│  CacheManager, Migration, PreloadEventBridge,           │
│  PluginManager, ServiceManager, Queue, QueueRunner,     │
│  MediaManager, NotificationManager, AuditLogger,        │
│  ReportBuilder, ImportExport, Versioning, Webhook,      │
│  Scheduler, Validation                                  │
├─────────────────────────────────────────────────────────┤
│  TIER 2: Extended (XOOPS-aware)                         │
│  10 Object Traits, Repository, HandlerTrait,            │
│  TreeHandlerTrait, TemporalQueryTrait,                  │
│  SmartForm, ObjectTable, TableStateManager,             │
│  ApiController, MetaGenerator, ItemPermission,          │
│  FieldDefinitionReader, ConfigManager, FiscalPeriod     │
├─────────────────────────────────────────────────────────┤
│  TIER 1: Core (Zero-Dependency Foundations)             │
│  Container, EventBus, ListenerProvider, Pipeline,       │
│  QueryBuilder, Enums, Value Objects, Validation Rules   │
└─────────────────────────────────────────────────────────┘
```

**Tier 1** has no XOOPS dependencies and can be tested in isolation.
**Tier 2** builds on `XoopsObject` and `XoopsPersistableObjectHandler`.
**Tier 3** depends on both Tier 1 and 2.
**Tier 4** orchestrates everything.

The blog module uses components from all four tiers. Here is how data flows through the architecture:

```
Browser Request
    │
    ▼
header.php ──► Container::getInstance()
    │              │
    ▼              ▼
index.php     BlogModule::boot()
    │          registers all services:
    │          db, events, config, cache,
    │          repos, permissions,
    │          pipeline, api_controller
    │
    ├──► $postRepo->query()
    │        builds fluent SQL query (prefixed table)
    │
    ├──► PostRepository::findAll($query)
    │        executes query + lifecycle hooks
    │
    ├──► MetaGenerator (SEO meta tags)
    │
    ├──► EventBus::dispatch(DotEvent)
    │        notifies all listeners
    │
    └──► Smarty template renders HTML
```

---

## 3. Getting Started: The Container

### The Problem

In traditional XOOPS modules, getting a database connection looks like this:

```php
// Legacy approach — global state, no testability
$db = XoopsDatabaseFactory::getDatabaseConnection();
$handler = new MyArticleHandler($db);
```

Every file that needs the handler creates its own instance. There's no central place to configure or swap implementations.

### The Solution: Dependency Injection Container

XMF's `Container` is a lightweight singleton that manages all your module's services:

```php
use Xmf\Container\Container;

// BlogModule::boot() — called once at module startup
$container = Container::getInstance();

$container->singleton('db', function () {
    return \XoopsDatabaseFactory::getDatabaseConnection();
});

$container->singleton('post_repo', function (Container $c) {
    return new PostRepository($c->get('db'), Post::class, $c->get('event_bus'));
});
```

**`singleton()`** ensures the factory runs only once. Every subsequent `$container->get('post_repo')` returns the same instance:

```php
$repo1 = $container->get('post_repo');
$repo2 = $container->get('post_repo');
// $repo1 === $repo2 (same object)
```

**`factory()`** creates a new instance each time:

```php
$container->factory('new_post', function (Container $c) {
    return new Post();
});
```

**`has()`** checks registration:

```php
if ($container->has('post_repo')) {
    $repo = $container->get('post_repo');
}
```

### Why This Matters

1. **Single source of truth** — all wiring happens in `BlogModule::boot()`, not scattered across files
2. **Lazy loading** — the database connection isn't created until something actually needs it
3. **Testability** — you can swap `'db'` with a test double without touching any other code
4. **Dependency chain** — the container resolves nested dependencies automatically via the `Container $c` parameter

### Blog Module Usage

The blog module registers 13+ services in its bootstrap (`src/BlogModule.php`):

```php
class BlogModule
{
    public static function boot(): Container
    {
        $container = Container::getInstance();

        // Infrastructure
        $container->singleton('db', fn() => \XoopsDatabaseFactory::getDatabaseConnection());

        // Events
        $container->singleton('events', fn() => new ListenerProvider());
        $container->singleton('event_bus', fn(Container $c) =>
            new EventBus($c->get('events'))
        );

        // Configuration
        $container->singleton('config', function () {
            $config = new ConfigManager();
            $config->defineSchema(
                new ConfigSchema('posts.per_page', 'int', 10),
                new ConfigSchema('cache.ttl', 'int', 3600),
                new ConfigSchema('api.enabled', 'bool', true),
                // ...
            );
            $config->load([
                'posts.per_page' => 10,
                'cache.ttl'      => 3600,
                'api.enabled'    => true,
            ]);
            return $config;
        });

        // Repositories (EntityRepository — DDD path)
        $container->singleton('post_repo', fn(Container $c) =>
            new PostRepository($c->get('db'), Post::class, $c->get('event_bus'))
        );
        $container->singleton('category_repo', fn(Container $c) =>
            new CategoryRepository($c->get('db'), Category::class, $c->get('event_bus'))
        );

        // API (depends on repo + pipeline)
        $container->singleton('api_controller', fn(Container $c) =>
            new PostApiController($c->get('post_repo'), $c->get('pipeline'))
        );

        return $container;
    }
}
```

Then `header.php` boots the container once, and every page uses it:

```php
// header.php
$container = \XmfBlog\BlogModule::boot();
```

```php
// index.php
$repo = $container->get('post_repo');
$posts = $repo->findAll();
```

---

## 4. Defining Entities with Object Traits

### The Problem

A typical XOOPS entity requires manually calling `initVar()` for every field, and there's no standard way to handle common patterns like change tracking, SEO fields, audit trails, or JSON storage. Every module reinvents these patterns.

### The Solution: Composable Traits + PHP 8.4 Property Hooks

XMF provides **9 traits** that you can mix into any `XoopsObject` subclass, plus **PHP 8.4 property hooks** that give entities a typed, modern API while keeping full backward compatibility with `setVar()`/`getVar()`.

| Trait | Purpose | Key Methods |
|-------|---------|-------------|
| `ChangeTrackingTrait` | Tracks which fields changed since load | `isDirty()`, `getChangedFields()`, `getDiff()`, `resetChangeTracking()` |
| `XmfObjectTrait` | Type-coerced value retrieval | `getTypedVar($key, DataType)` |
| `AccessorsTrait` | Formatted timestamps, usernames, truncation | `getTimestampFormatted()`, `getUsernameFor()`, `getTruncated()` |
| `AuditTrailTrait` | Soft-delete with row_flag status | `isActive()`, `markDefunct()`, `initAuditVars()` |
| `CommonFieldsTrait` | Standard fields: uid, title, status, dates, weight | `initCommonVar($field)`, `initCommonFields()` (abstract) |
| `SeoFieldsTrait` | SEO meta: title, description, keywords, short_url | `initSeoVars()`, `initSeoFields()` (abstract) |
| `JsonFieldsTrait` | JSON encode/decode for text columns | `getJsonField()`, `setJsonField()`, `mergeJsonField()` |
| `TreeTrait` | Hierarchical parent/child relationships | `isRoot()`, `isLeaf()`, `getParentId()`, `getChildren()` |
| `MultiLingualTrait` | Per-language field variants (title_fr, etc.) | `getLocalizedVar()`, `setLocalizedVar()`, `getAvailableLanguages()` |

### PHP 8.4 Property Hooks — The Modern Entity Pattern

XMF entities use three PHP 8.4 features to create a typed facade over `XoopsObject::$vars`:

1. **Property hooks** (`get`/`set`) — read/write through `getVar()`/`setVar()` transparently
2. **Asymmetric visibility** (`public private(set)`) — primary keys are readable but not publicly writable
3. **`#[Field]` attribute** — machine-readable metadata for forms, tables, and introspection

The `initVar()` calls remain in the constructor to register fields with XoopsObject. Property hooks are the typed access layer on top. Both `$post->title = 'x'` and `$post->setVar('title', 'x')` work — full backward compatibility.

> **Important:** Always use `set { }` block form for set hooks, NOT `set =>` arrow form. The arrow form implicitly assigns the return value to backing storage, which fails because `setVar()` returns void.

### Blog Module: The Post Entity

The `Post` class uses **7 traits simultaneously** with PHP 8.4 property hooks:

```php
namespace XmfBlog;

use Xmf\Attribute\Field;
use Xmf\Enum\DataType;
use Xmf\Enum\ObjectStatus;
use Xmf\Object\AccessorsTrait;
use Xmf\Object\AuditTrailTrait;
use Xmf\Object\ChangeTrackingTrait;
use Xmf\Object\CommonFieldsTrait;
use Xmf\Object\JsonFieldsTrait;
use Xmf\Object\MultiLingualTrait;
use Xmf\Object\SeoFieldsTrait;
use Xmf\Object\XmfObjectTrait;

class Post extends \XoopsObject
{
    use ChangeTrackingTrait, XmfObjectTrait, AccessorsTrait;
    use AuditTrailTrait, CommonFieldsTrait, SeoFieldsTrait;
    use JsonFieldsTrait, MultiLingualTrait;

    // PHP 8.4 Property Hooks — typed facade over XoopsObject vars

    #[Field(column: 'post_id', type: DataType::Integer, label: 'ID')]
    public private(set) int $id {
        get => (int) $this->getVar('post_id', 'n');
        set { $this->setVar('post_id', $value); }
    }

    #[Field(column: 'title', type: DataType::TxtBox, maxLength: 255, required: true, label: 'Title')]
    public string $title {
        get => (string) $this->getVar('title', 'n');
        set {
            if (strlen($value) > 255) {
                throw new \InvalidArgumentException('Title too long');
            }
            $this->setVar('title', $value);
        }
    }

    #[Field(column: 'status', type: DataType::Integer, label: 'Status')]
    public ObjectStatus $status {
        get => ObjectStatus::from((int) $this->getVar('status', 'n'));
        set { $this->setVar('status', $value->value); }
    }

    #[Field(column: 'body', type: DataType::TxtArea, label: 'Body')]
    public string $body {
        get => (string) $this->getVar('body', 'n');
        set { $this->setVar('body', $value); }
    }

    // ... more properties for every field ...

    // Constructor — registers fields with XoopsObject
    public function __construct()
    {
        $this->initCommonFields();   // uid, title, status, dates, weight
        $this->initSeoFields();      // meta_title, meta_description, etc.
        $this->initAuditFields();    // row_flag, row_uid, row_dt

        // Blog-specific fields use DataType enum instead of XOBJ_DTYPE_* constants
        $this->initVar('category_id', DataType::Integer->value, 0);
        $this->initVar('body', DataType::TxtArea->value, '');
        $this->initVar('excerpt', DataType::TxtBox->value, '');
        $this->initVar('featured_image', DataType::TxtBox->value, '');
        $this->initVar('tags_json', DataType::TxtArea->value, '');
        $this->initVar('title_fr', DataType::TxtBox->value, '');
    }

    // Concrete trait helpers replace boilerplate
    protected function initCommonFields(): void
    {
        $this->initVar('post_id', DataType::Integer->value);
        $this->initCommonVar('uid');        // registers uid field
        $this->initCommonVar('title');      // registers title field
        $this->initCommonVar('status');     // registers status with Draft default
        $this->initCommonVar('date_created');
        $this->initCommonVar('date_updated');
        $this->initCommonVar('weight');
    }

    protected function initSeoFields(): void
    {
        $this->initSeoVars();  // registers all 4 SEO fields at once
    }

    protected function initAuditFields(): void
    {
        $this->initAuditVars(); // registers row_flag, row_uid, row_dt
    }
}
```

### Using Property Hooks in Practice

#### Modern Typed Access

Property hooks give you typed, validated access to entity fields:

```php
$post = new Post();

// Property hooks — typed, validated, IDE-friendly
$post->title = 'Hello World';          // string, validated (max 255 chars)
$post->status = ObjectStatus::Published; // enum, not raw int
$post->categoryId = 3;                 // int, type-safe
echo $post->title;                     // 'Hello World'

// Legacy setVar/getVar still works too
$post->setVar('title', 'Hello World');
$post->getVar('title');                // 'Hello World'
```

#### Change Tracking Works Through Property Hooks

Property hooks call `$this->setVar()` internally, which goes through `ChangeTrackingTrait::setVar()`, so change tracking fires automatically:

```php
$post = new Post();
$post->title = 'Hello World';      // triggers ChangeTrackingTrait::setVar()
$post->body = 'Content here.';

$post->isDirty();           // true
$post->getChangedFields();  // ['title', 'body']
$post->getDiff();           // ['title' => ['old' => '', 'new' => 'Hello World'], ...]

$post->resetChangeTracking();
$post->isDirty();           // false
```

**Why this matters:** The `Repository::save()` method uses `getChangedFields()` to generate `UPDATE` statements that only touch modified columns, not the entire row.

#### `#[Field]` Attribute for Introspection

The `#[Field]` attribute provides machine-readable metadata for auto-generating forms and tables:

```php
use Xmf\Reflection\FieldDefinitionReader;

$reader = new FieldDefinitionReader();
$fields = $reader->readFromAttributes(Post::class);
// [
//   'post_id' => ['property' => 'id', 'type' => DataType::Integer, 'maxLength' => 0, ...],
//   'title'   => ['property' => 'title', 'type' => DataType::TxtBox, 'maxLength' => 255, ...],
//   ...
// ]

// Legacy introspection still works too:
$fields = $reader->readFromObject(new Post());
```

#### JSON Fields

Store structured data (tags, settings, metadata) in a text column:

```php
$post->setJsonField('tags_json', ['php', 'xoops', 'tutorial']);
$tags = $post->getJsonField('tags_json');  // ['php', 'xoops', 'tutorial']

// Merge additional tags
$post->mergeJsonField('tags_json', ['xmf', 'blog']);
// Now: ['php', 'xoops', 'tutorial', 'xmf', 'blog']
```

Internally, `setJsonField()` calls `json_encode()` with `JSON_THROW_ON_ERROR`, and `getJsonField()` validates with `json_validate()` (PHP 8.3) before decoding.

#### Multilingual Fields

Store translations as field variants — `title_fr`, `title_de`, etc.:

```php
$post->title = 'Getting Started';                                 // default (English)
$post->setLocalizedVar('title', 'Premiers pas', 'fr');           // sets title_fr

$post->getLocalizedVar('title', 'fr');  // 'Premiers pas'
$post->getLocalizedVar('title', 'en');  // 'Getting Started' (falls back to base field)
$post->getAvailableLanguages();         // ['fr']
```

#### Audit Trail (Soft Delete)

Every entity with `AuditTrailTrait` has a `row_flag` field that tracks its lifecycle:

```php
$post->isActive();      // true (row_flag = 'A')
$post->markDefunct();   // sets row_flag to 'D' (Defunct)
$post->isActive();      // false

// Query only active records
$query = QueryBuilder::for('xmfblog_posts')
    ->where('row_flag', ComparisonOperator::Equal, 'A');
```

This pattern replaces `DELETE FROM` with soft-delete — no data is ever lost.

#### Typed Value Retrieval

With property hooks, you get typed values directly. For legacy code, `getTypedVar()` is still available:

```php
// Modern: property hooks return the correct type
$post->weight;  // 42 (int)

// Legacy: getTypedVar casts explicitly
$post->getTypedVar('weight', DataType::Integer);  // 42 (int)
```

### Blog Module: Category and Comment Entities

Categories use `TreeTrait` + PHP 8.4 property hooks:

```php
class Category extends \XoopsObject
{
    use ChangeTrackingTrait;
    use TreeTrait;

    #[Field(column: 'category_id', type: DataType::Integer, label: 'ID')]
    public private(set) int $id {
        get => (int) $this->getVar('category_id', 'n');
        set { $this->setVar('category_id', $value); }
    }

    #[Field(column: 'name', type: DataType::TxtBox, maxLength: 255, required: true, label: 'Name')]
    public string $name {
        get => (string) $this->getVar('name', 'n');
        set { $this->setVar('name', $value); }
    }

    // ... more property hooks ...

    public function __construct()
    {
        $this->initVar('category_id', DataType::Integer->value);
        $this->initVar('parent_id', DataType::Integer->value, 0);
        $this->initVar('name', DataType::TxtBox->value, '');
        // ...
    }
}
```

```php
$root = new Category();
$root->parentId = 0;       // property hook
$root->isRoot();            // true

$child = new Category();
$child->parentId = 1;       // property hook
$child->isRoot();            // false
$child->getParentId();       // 1
```

Comments also use `TreeTrait` for nested/threaded replies:

```php
class Comment extends \XoopsObject
{
    use ChangeTrackingTrait;
    use TreeTrait;

    #[Field(column: 'comment_id', type: DataType::Integer, label: 'ID')]
    public private(set) int $id {
        get => (int) $this->getVar('comment_id', 'n');
        set { $this->setVar('comment_id', $value); }
    }

    #[Field(column: 'content', type: DataType::TxtArea, required: true, label: 'Content')]
    public string $content {
        get => (string) $this->getVar('content', 'n');
        set { $this->setVar('content', $value); }
    }

    #[Field(column: 'status', type: DataType::Integer, label: 'Status')]
    public ObjectStatus $status {
        get => ObjectStatus::from((int) $this->getVar('status', 'n'));
        set { $this->setVar('status', $value->value); }
    }

    // ... more property hooks ...

    public function __construct()
    {
        $this->initVar('comment_id', DataType::Integer->value);
        $this->initVar('post_id', DataType::Integer->value, 0);
        $this->initVar('parent_id', DataType::Integer->value, 0);  // for threading
        $this->initVar('author_name', DataType::TxtBox->value, '');
        $this->initVar('content', DataType::TxtArea->value, '');
        // ...
    }
}
```

---

## 5. Handlers and Lifecycle Hooks

### The Problem

`XoopsPersistableObjectHandler` provides CRUD methods (`insert`, `get`, `delete`, `getObjects`), but there's no clean way to run code before or after these operations. Developers resort to overriding `insert()` with copy-paste boilerplate.

### The Solution: Lifecycle Hooks

XMF provides two paths for lifecycle hooks:

**Path 1 (Classic):** `HandlerTrait` adds lifecycle hooks to `XoopsPersistableObjectHandler` subclasses. Used for entities based on `XoopsObject` (e.g., Comment in xmfblog).

**Path 2 (DDD):** `EntityRepository` provides the same lifecycle hooks for pure PHP entities with `#[Column]` attributes. Used for entities like Post and Category in xmfblog.

The blog module uses **Path 2** for Post and Category — they are plain PHP entities persisted via `EntityRepository` subclasses:

```php
namespace XmfBlog;

use Xmf\Repository\EntityRepository;

class PostRepository extends EntityRepository
{
    protected function beforeSave(object $entity): bool
    {
        /** @var Post $entity */
        $now = time();
        $entity->dateUpdated = $now;
        if ($entity->isNew()) {
            $entity->dateCreated = $now;
        } else {
            $this->autoVersion($entity); // snapshot before overwriting
        }
        return true;  // return false to abort the save
    }

    protected function afterSave(object $entity, bool $isNew): void
    {
        // Audit logging, domain events, tag sync...
    }

    // Also available:
    // protected function beforeDelete(object $entity): bool { return true; }
    // protected function afterDelete(object $entity): void { }
}
```

**Why hooks instead of manual pre/post logic in each controller?**
- Hooks are called by the Repository, keeping controller code clean
- `beforeSave` can abort the operation by returning `false`
- `afterSave` is guaranteed to run only on successful persistence
- The pattern is consistent across all repositories

For **classic-path** entities (like Comment), the same hooks work via `HandlerTrait`:

```php
class CommentHandler extends \XoopsPersistableObjectHandler
{
    use HandlerTrait;

    public function __construct(\XoopsDatabase $db)
    {
        parent::__construct($db, 'xmf_comments', \Xmf\Comment\Comment::class, 'comment_id', 'content');
    }

    protected function beforeSave(object $entity): bool
    {
        $entity->setVar('date_updated', time());
        if ($entity->isNew()) {
            $entity->setVar('date_created', time());
        }
        return true;
    }
}
```

### TemporalQueryTrait: Publish Windows

Blog posts often have a publish date and an optional expiration date. `TemporalQueryTrait` generates the correct `WHERE` clause for classic-path handlers:

```php
// For classic-path handlers that use TemporalQueryTrait:
$criteria = $handler->getPublishedCriteria();
// Generates: WHERE date_publish <= NOW AND (date_expire = 0 OR date_expire > NOW)
$publishedPosts = $handler->getObjects($criteria);
```

For DDD-path repositories like `PostRepository`, use QueryBuilder directly:

```php
$postRepo = $container->get('post_repo');
$query = $postRepo->query()
    ->where('status', ComparisonOperator::Equal, ObjectStatus::Published->value)
    ->where('row_flag', ComparisonOperator::Equal, 'A');
$publishedPosts = $postRepo->findAll($query);
```

### TreeHandlerTrait: Hierarchical Data

For classic-path handlers, `TreeHandlerTrait` adds tree traversal methods. For DDD-path entities like Category, tree operations are handled via direct SQL queries in the repository or admin pages:

```php
$categoryRepo = $container->get('category_repo');

// Fetch all categories and build tree in PHP
$allCategories = $categoryRepo->findAll();
// Sort/nest by parent_id for display
```

---

## 6. The Repository Pattern

### The Problem

Handlers mix query building, persistence, and business logic. Code like this is scattered everywhere:

```php
// Legacy: direct handler calls in page files
$handler = xoops_getModuleHandler('article', 'myblog');
$criteria = new CriteriaCompo(new Criteria('status', '1'));
$criteria->setSort('date_created');
$criteria->setOrder('DESC');
$criteria->setLimit(10);
$articles = $handler->getObjects($criteria);
```

### The Solution: Repository

`Repository` wraps a handler with a clean, consistent data-access interface:

```php
use Xmf\Repository\Repository;

// Registered in the container
$container->singleton('post_repo', function (Container $c) {
    return new Repository($c->get('post_handler'), $c->get('db'));
});
```

Now your page code is handler-agnostic:

```php
$repo = $container->get('post_repo');

// CRUD operations
$post = $repo->find(42);                    // find by ID
$allPosts = $repo->findAll();               // find all
$post = $repo->save($post);                 // insert or update
$repo->delete(42);                          // delete by ID
$count = $repo->count();                    // count all
$exists = $repo->exists(42);               // check existence

// With QueryBuilder (see next section)
$query = QueryBuilder::for('xmfblog_posts')
    ->where('status', ComparisonOperator::Equal, 1)
    ->limit(10);

$posts = $repo->findAll($query);
$single = $repo->findOne($query);
```

**Why Repository over direct handler calls?**
- Uniform API across all entities
- Accepts `QueryBuilder` for type-safe queries (instead of raw `CriteriaCompo`)
- `save()` leverages `ChangeTrackingTrait` for partial updates
- Easy to mock in tests — depends on `RepositoryInterface`

---

## 7. Querying Data with QueryBuilder

### The Problem

`CriteriaCompo` is verbose, untyped, and easy to get wrong:

```php
// Legacy: string-based, error-prone
$criteria = new CriteriaCompo(new Criteria('status', '1'));
$criteria->add(new Criteria('category_id', '5'));
$criteria->add(new Criteria('date_publish', time(), '<='));
$criteria->add(new CriteriaCompo(
    new Criteria('date_expire', '0'),
    new Criteria('date_expire', time(), '>'),
    'OR'
));
$criteria->setSort('date_created');
$criteria->setOrder('DESC');
$criteria->setLimit(10);
$criteria->setStart(0);
```

### The Solution: Fluent QueryBuilder

```php
use Xmf\Enum\ComparisonOperator;
use Xmf\Enum\ObjectStatus;
use Xmf\Query\QueryBuilder;

$query = QueryBuilder::for('xmfblog_posts')
    ->select('post_id', 'title', 'status', 'date_created')
    ->where('status', ComparisonOperator::Equal, ObjectStatus::Published->value)
    ->where('row_flag', ComparisonOperator::Equal, 'A')
    ->whereIn('category_id', [1, 2, 3])
    ->orderBy('date_created', 'DESC')
    ->limit(10, 0);

$sql = $query->toSql();         // complete SQL string
$bindings = $query->getBindings(); // named parameters for PDO
```

#### Nested Conditions

```php
use Xmf\Enum\QueryCondition;

$query = QueryBuilder::for('xmfblog_posts')
    ->where('status', ComparisonOperator::Equal, 1)
    ->whereNested(function (QueryBuilder $q) {
        $q->where('title', ComparisonOperator::Like, '%php%');
        $q->where('body', ComparisonOperator::Like, '%php%');
    }, QueryCondition::Or);

// WHERE status = :p_status_1 AND (title LIKE :p_title_2 OR body LIKE :p_body_3)
```

#### Backward Compatibility Bridge

Existing code using `CriteriaCompo` can be converted automatically:

```php
// Legacy criteria
$criteria = new CriteriaCompo(new Criteria('status', '1'));
$criteria->setSort('weight');
$criteria->setOrder('ASC');
$criteria->setLimit(5);

// Convert to QueryBuilder
$query = QueryBuilder::fromCriteria('xmfblog_posts', $criteria);
```

This means you can migrate incrementally — old code keeps working while new code uses the fluent API.

### TypedCriteriaItem

For individual criteria with bind-parameter safety:

```php
use Xmf\Query\TypedCriteriaItem;

// Equality
$item = new TypedCriteriaItem('status', 1, ComparisonOperator::Equal);
$item->renderPrepared();  // "status = :p_status_1"
$item->getBindParams();   // [':p_status_1' => 1]

// NULL checks (no bind params needed)
$item = new TypedCriteriaItem('deleted_at', null, ComparisonOperator::IsNull);
$item->renderPrepared();  // "deleted_at IS NULL"

// IN clause (multiple bind params)
$item = new TypedCriteriaItem('id', [1, 2, 3], ComparisonOperator::In);
$item->renderPrepared();  // "id IN (:p_id_1, :p_id_2, :p_id_3)"
```

---

## 8. Type-Safe Enums

### The Problem

Magic numbers everywhere: `status = 1`, `sort = 'DESC'`, `operator = '<='`. No IDE autocomplete, no validation, easy to misuse.

### The Solution: Backed Enums

XMF provides **8 enums** covering the most common constants:

#### ObjectStatus — Entity Lifecycle

```php
use Xmf\Enum\ObjectStatus;

ObjectStatus::Draft;      // value: 0
ObjectStatus::Published;  // value: 1
ObjectStatus::Pending;    // value: 2
ObjectStatus::Rejected;   // value: 3
ObjectStatus::Archived;   // value: 4

ObjectStatus::Published->label();     // "Published"
ObjectStatus::Published->isVisible(); // true
```

Used in entity definitions with `DataType` enum and property hooks:

```php
// In constructor — register with XoopsObject
$this->initCommonVar('status');  // uses DataType::Integer, defaults to Draft

// Property hook — typed access via ObjectStatus enum
public ObjectStatus $status {
    get => ObjectStatus::from((int) $this->getVar('status', 'n'));
    set { $this->setVar('status', $value->value); }
}

// Usage: $post->status = ObjectStatus::Published;
```

#### ComparisonOperator — SQL Operators

```php
use Xmf\Enum\ComparisonOperator;

ComparisonOperator::Equal;        // '='
ComparisonOperator::Like;         // 'LIKE'
ComparisonOperator::In;           // 'IN'
ComparisonOperator::IsNull;       // 'IS NULL'
ComparisonOperator::Between;      // 'BETWEEN'

// Convert string to enum
ComparisonOperator::resolve('>=');  // ComparisonOperator::GreaterOrEqual
```

#### RowStatus — Audit Trail Flags

```php
use Xmf\Enum\RowStatus;

RowStatus::Active;   // 'A' — normal record
RowStatus::Defunct;  // 'D' — soft-deleted
RowStatus::Suspend;  // 'S' — temporarily disabled

RowStatus::Active->isUsable();   // true
RowStatus::Defunct->isUsable();  // false
```

#### DataType — Field Type Mapping

```php
use Xmf\Enum\DataType;

DataType::Integer->phpType();    // 'int'
DataType::TxtBox->phpType();     // 'string'
DataType::Json->phpType();       // 'array'
DataType::Image->isFileBased();  // true
DataType::DateTime->isTemporal(); // true
```

#### Other Enums

- **`SortOrder`**: `Asc`, `Desc`
- **`QueryCondition`**: `And`, `Or`, `Xor`
- **`ServiceMode`**: `Exclusive`, `Multiple`

**Why enums instead of constants?**
- Type safety — you can't pass `"invalid"` where `ObjectStatus` is expected
- IDE autocomplete and refactoring support
- Built-in methods like `label()`, `isVisible()`, `resolve()`
- PHP guarantees exhaustive match in switch/match expressions

---

## 9. The Event System

### The Problem

XOOPS has a preload system where classes define `eventCoreHeaderStart()` methods. It works but it's implicit, hard to discover, and tightly coupled to naming conventions.

### The Solution: PSR-14 Compatible Events

XMF provides a proper event bus with typed events, priority ordering, and wildcard matching.

#### Defining Domain Events

Events are readonly classes implementing `EventInterface`:

```php
namespace XmfBlog;

use Xmf\Event\EventInterface;

readonly class PostEvent implements EventInterface
{
    public function __construct(
        public string $action,    // 'created', 'updated', 'deleted'
        public int $postId,       // the post's ID
        public ?array $diff = null, // changed fields
    ) {
    }
}
```

#### Registering Listeners

```php
$provider = $container->get('events');  // ListenerProvider

// Listen for PostEvent (any action)
$provider->addListener(PostEvent::class, function (PostEvent $e) {
    if ($e->action === 'created') {
        // Send notification email
        // Update RSS feed
        // Clear cache
    }
});

// Higher priority fires first (default is 50)
$provider->addListener(PostEvent::class, function (PostEvent $e) {
    // This runs BEFORE the listener above
    logAction('post.' . $e->action, $e->postId);
}, priority: 100);
```

#### Dispatching Events

```php
$bus = $container->get('event_bus');  // EventBus

// After saving a post
$bus->dispatch(new PostEvent('created', $post->id));

// With change diff
$diff = $post->getDiff();  // from ChangeTrackingTrait
$bus->dispatch(new PostEvent('updated', $postId, $diff));
```

#### String-Based Events with DotEvent

For simple notifications where a full event class is overkill:

```php
use Xmf\Event\DotEvent;

// Dispatch
$bus->dispatch(new DotEvent('post.published', ['id' => 42]));
$bus->dispatch(new DotEvent('cache.cleared', ['module' => 'xmfblog']));

// Listen
$provider->addListener(DotEvent::class, function (DotEvent $e) {
    if ($e->name === 'post.published') {
        $postId = $e->args['id'];
        // Update sitemap, notify subscribers, etc.
    }
});
```

The blog module dispatches a `DotEvent` when a post is viewed on the frontend (`index.php`):

```php
$bus = $container->get('event_bus');
$bus->dispatch(new DotEvent('post.viewed', ['id' => $postId]));
```

**Why events?**
- **Decoupling** — the post doesn't need to know about caching, notifications, or RSS
- **Extensibility** — other modules can listen for your events without modifying your code
- **Testability** — dispatch events in tests and verify listeners fire correctly

---

## 10. Configuration Management

### The Problem

XOOPS module configs are defined in `xoops_version.php` and accessed via `$helper->getConfig('key')`. There's no validation, no type casting, no defaults for nested values.

### The Solution: ConfigManager with Schemas

Define what your configuration looks like, then load and validate it:

```php
use Xmf\Config\ConfigManager;
use Xmf\Config\ConfigSchema;

$config = new ConfigManager();

// Define expected keys with types and defaults
$config->defineSchema(
    new ConfigSchema('module.name', 'string', 'XMF Blog'),
    new ConfigSchema('posts.per_page', 'int', 10),
    new ConfigSchema('cache.ttl', 'int', 3600),
    new ConfigSchema('api.enabled', 'bool', true),
    new ConfigSchema('comments.enabled', 'bool', true),
    new ConfigSchema('comments.moderation', 'bool', false),
);

// Load actual values (from xoopsModuleConfig or any source)
$config->load([
    'posts.per_page' => 15,
    'cache.ttl' => 1800,
    'api.enabled' => true,
]);

// Access with dot-notation
$config->get('posts.per_page');      // 15 (loaded value)
$config->get('cache.ttl');           // 1800 (loaded value)
$config->get('module.name');         // 'XMF Blog' (default)
$config->get('unknown.key');         // null
```

Schemas can also enforce constraints:

```php
new ConfigSchema(
    key: 'posts.per_page',
    type: 'int',
    default: 10,
    required: true,                    // throws if missing
    allowedValues: [5, 10, 15, 20, 50] // restrict to these values
);
```

**Why ConfigManager?**
- Type-safe defaults — no more `(int)($config['per_page'] ?? 10)` scattered everywhere
- Validation at boot time — bad config values are caught immediately
- Environment overrides — `load($config, $envOverrides)` for per-environment settings
- Single access point — `$config->get('key')` everywhere

---

## 11. Caching

### The Problem

XOOPS has basic caching, but no module-scoped caching, no tag-based invalidation, and no cache-aside pattern.

### The Solution: CacheManager

```php
use Xmf\Cache\CacheManager;
use Xmf\Cache\CacheBackendInterface;

$cache = new CacheManager();

// Register one or more backends
$cache->registerBackend('memory', new InMemoryCache());
$cache->registerBackend('file', new FileCacheBackend('/tmp/cache'));
$cache->setDefault('memory');

// Create a module-scoped cache (prefixes all keys)
$blogCache = $cache->forModule('xmfblog');
```

#### Basic Operations

```php
$blogCache->set('post:42', $postData, ttl: 3600);
$post = $blogCache->get('post:42');
$blogCache->delete('post:42');
```

#### Cache-Aside Pattern (Remember)

The most common caching pattern — check cache, compute if missing, store:

```php
$post = $blogCache->remember('post:42', 3600, function () use ($repo) {
    return $repo->find(42);  // only runs on cache miss
});
```

#### Tag-Based Invalidation

Group related cache entries and invalidate them together:

```php
// Tag entries
$blogCache->set('post:1', $data1);
$blogCache->tag('post:1', 'posts');

$blogCache->set('post:2', $data2);
$blogCache->tag('post:2', 'posts');

// Invalidate all "posts" entries at once
$blogCache->invalidateTag('posts');
```

This is used when a category is renamed (invalidate all posts in that category) or when cache needs to be cleared after bulk operations.

The blog module registers a memory-backed, module-scoped cache:

```php
$container->singleton('cache', function () {
    $cache = new CacheManager();
    $cache->registerBackend('memory', new InMemoryCache());
    $cache->setDefault('memory');
    return $cache->forModule('xmfblog');
});
```

In production, you'd swap `InMemoryCache` with a file-based or Redis backend.

---

## 12. Middleware Pipeline and HTTP

### The Problem

Authentication checks, rate limiting, and input validation are duplicated in every controller method. There's no standard way to compose these cross-cutting concerns.

### The Solution: Middleware Pipeline

XMF's `Pipeline` processes requests through a chain of middleware:

```php
use Xmf\Http\Pipeline;
use Xmf\Http\MiddlewareInterface;
```

#### Defining Middleware

Each middleware implements a single method:

```php
namespace XmfBlog;

use Xmf\Http\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(array $request, callable $next): array
    {
        if (!isset($request['user'])) {
            return ['error' => 'Unauthorized', 'status' => 401];
        }

        return $next($request);  // pass to next middleware
    }
}
```

The key insight: middleware can **short-circuit** by returning early (the 401 response above) or **pass through** by calling `$next($request)`.

#### Building a Pipeline

```php
$pipeline = new Pipeline();
$pipeline->pipe(new AuthMiddleware());
$pipeline->pipe(new RateLimitMiddleware());
$pipeline->pipe(new ValidationMiddleware());

// The final handler runs only if all middleware pass
$result = $pipeline->handle($request, function (array $request): array {
    return ['status' => 200, 'data' => doWork($request)];
});
```

Middleware executes in order: Auth → RateLimit → Validation → Handler.
If any middleware returns early, the rest are skipped.

The blog module pipes the `AuthMiddleware` into the API controller's pipeline:

```php
$container->singleton('pipeline', function () {
    $pipeline = new Pipeline();
    $pipeline->pipe(new AuthMiddleware());
    return $pipeline;
});

$container->singleton('api_controller', function (Container $c) {
    return new PostApiController($c->get('post_repo'), $c->get('pipeline'));
});
```

---

## 13. Building a REST API

### The Problem

Building REST endpoints in XOOPS means writing manual JSON responses, routing logic, and error handling for every action.

### The Solution: ApiController + ApiResponse

#### ApiResponse — Structured JSON Responses

```php
use Xmf\Api\ApiResponse;

$response = new ApiResponse(
    success: true,
    data: ['id' => 1, 'title' => 'Hello'],
    message: 'Post created',
    statusCode: 201,
    meta: ['page' => 1, 'total' => 42]
);

echo json_encode($response);
// {"success":true,"data":{"id":1,"title":"Hello"},"message":"Post created","meta":{"page":1,"total":42}}
```

#### ApiController — CRUD Scaffolding

Extend `ApiController` and implement three methods:

```php
namespace XmfBlog;

use Xmf\Api\ApiController;

class PostApiController extends ApiController
{
    // What table this controller manages
    protected function tableName(): string
    {
        return 'xmfblog_posts';
    }

    // Create a new entity from request data
    protected function hydrate(array $data): object
    {
        $post = new Post();
        $post->title = $data['title'] ?? '';
        $post->body = $data['body'] ?? '';
        $post->excerpt = $data['excerpt'] ?? '';
        if (isset($data['category_id'])) {
            $post->categoryId = (int) $data['category_id'];
        }
        return $post;
    }

    // Apply partial updates to an existing entity
    protected function fill(object $entity, array $data): void
    {
        if (isset($data['title'])) {
            $entity->title = $data['title'];
        }
        if (isset($data['body'])) {
            $entity->body = $data['body'];
        }
    }
}
```

You get five endpoints for free:

| Method | Action | ApiController Method |
|--------|--------|---------------------|
| `GET /api.php` | List all posts | `index($request)` |
| `GET /api.php?id=42` | Get single post | `show($request)` |
| `POST /api.php` | Create post | `store($request)` |
| `PUT /api.php?id=42` | Update post | `update($request)` |
| `DELETE /api.php?id=42` | Delete post | `destroy($request)` |

#### The API Entry Point

The blog module's `api.php` routes HTTP methods to controller actions:

```php
$api = $container->get('api_controller');

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$request = [
    'method' => $method,
    'id'     => $id,
    'data'   => $data,
    'user'   => $_SESSION['xoopsUserId'] ?? null,
];

$response = match ($method) {
    'GET'    => $id !== null ? $api->show($request) : $api->index($request),
    'POST'   => $api->store($request),
    'PUT'    => $api->update($request),
    'DELETE' => $api->destroy($request),
    default  => new ApiResponse(false, null, 'Method not allowed', 405),
};

http_response_code($response->statusCode);
header('Content-Type: application/json');
echo json_encode($response);
```

---

## 14. Permissions

### The Problem

XOOPS group permissions work but the API is low-level: you call `gpermHandler->checkRight()` directly, manage module IDs manually, and there's no convenient item-permission abstraction.

### The Solution: ItemPermission

```php
use Xmf\Permissions\ItemPermission;

$perm = new ItemPermission('xmfblog', 'post_view');

// Grant "post_view" permission on item 42 to groups [1, 2, 3]
$perm->savePermissions(42, [1, 2, 3], 'post_view');

// Check if group 1 can view item 42
$canView = $perm->accessGranted('post_view', 42, 1);  // true

// Remove all permissions for item 42
$perm->deletePermissions(42);
```

The blog module registers permissions in the container:

```php
$container->singleton('permission', function () {
    return new ItemPermission('xmfblog', 'post_view');
});
```

---

## 15. SEO Meta Generation

### The Problem

Every module that cares about SEO manually strips HTML tags, truncates descriptions, and extracts keywords. This logic is duplicated and often buggy.

### The Solution: MetaGenerator

```php
use Xmf\Seo\MetaGenerator;

$meta = new MetaGenerator(
    title: $post->metaTitle ?: $post->title,
    body: $post->metaDescription ?: $post->body,
    shortUrl: $post->shortUrl ?: 'post-' . $postId,
);

// Generate clean meta values
$pageTitle = $meta->generateTitle();         // returns title as-is
$desc = $meta->generateDescription(160);     // strips HTML, collapses whitespace, truncates to 160 chars
$keywords = $meta->generateKeywords(10);     // extracts 10 most frequent words (≥4 chars)
```

Used in the blog's frontend `index.php` when viewing a single post:

```php
$xoopsTpl->assign('xoops_pagetitle', $meta->generateTitle());
$xoTheme->addMeta('meta', 'description', $meta->generateDescription(160));
$xoTheme->addMeta('meta', 'keywords', implode(', ', $meta->generateKeywords(10)));
```

---

## 16. Presentation: Forms, Tables, and State

### SmartForm — Auto-Generated Forms

`SmartForm` extends `XoopsThemeForm` with a definition-driven API:

```php
use Xmf\Presentation\SmartForm;

$post = new Post();
$post->setNew();

$form = new SmartForm($post);  // title auto-set to "Add Post" or "Edit Post"

$form->addFieldsFromDefinition([
    'title'       => ['type' => 'text', 'caption' => 'Title', 'required' => true],
    'body'        => ['type' => 'textarea', 'caption' => 'Body'],
    'excerpt'     => ['type' => 'text', 'caption' => 'Excerpt'],
    'status'      => ['type' => 'select', 'caption' => 'Status', 'options' => [
        ObjectStatus::Draft->value     => ObjectStatus::Draft->label(),
        ObjectStatus::Published->value => ObjectStatus::Published->label(),
        ObjectStatus::Archived->value  => ObjectStatus::Archived->label(),
    ]],
    'category_id' => ['type' => 'hidden', 'caption' => 'Category'],
]);

echo $form->render();
```

Supported field types: `text`, `textarea`, `select`, `hidden`. Each creates the appropriate `XoopsForm*` element, pre-populated from the entity's current values.

### ObjectTable — Auto-Generated Tables

```php
use Xmf\Presentation\ObjectTable;

$table = new ObjectTable($handler, ['post_id', 'title', 'status', 'date_created']);
echo $table->render();
// Generates <table> with headers and rows from handler->getObjects()
```

### TableStateManager — Sort, Page, Filter State

Captures and applies sort/pagination/filter state from request parameters:

```php
use Xmf\Enum\SortOrder;
use Xmf\Presentation\TableStateManager;

$state = new TableStateManager(
    sortField: 'date_created',
    sortOrder: SortOrder::Desc,
    currentPage: 1,
    perPage: 20,
    filters: ['status' => '1'],
);

// Convert to XOOPS Criteria for handler queries
$criteria = $state->toCriteria();

// Or parse from request
$state = TableStateManager::fromRequest(['sortField' => 'date_created', 'perPage' => 20]);
```

### FieldDefinitionReader — Introspection

Read field metadata from any `XoopsObject`:

```php
use Xmf\Reflection\FieldDefinitionReader;

$reader = new FieldDefinitionReader();
$fields = $reader->readFromObject($post);
// ['post_id' => ['value' => ..., 'data_type' => 2, ...], 'title' => [...], ...]

$types = $reader->getFieldTypes($post);
// ['post_id' => 2, 'title' => 3, 'body' => 4, ...]
```

This is used internally by `SmartForm` to auto-detect field types, and can be used for dynamic admin interfaces.

---

## 17. Database Migrations

### The Problem

XOOPS modules use a `sql/mysql.sql` file for installation, but there's no versioned migration system for updates. Module upgrades require manual SQL or fragile version-checking code.

### The Solution: Migration + MigrationRunner

#### Defining a Migration

```php
namespace XmfBlog;

use Xmf\Database\Migration;

class BlogMigration extends Migration
{
    public function version(): string
    {
        return '1.0.0';
    }

    public function up(\XoopsDatabase $db): void
    {
        $this->createTable($db, 'xmfblog_posts', [
            'post_id'      => 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
            'title'        => 'VARCHAR(255) NOT NULL DEFAULT \'\'',
            'body'         => 'TEXT',
            'status'       => 'TINYINT NOT NULL DEFAULT 0',
            'date_created' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            // ...
        ]);

        $this->addIndex($db, 'xmfblog_posts', 'idx_status', ['status']);
        $this->addIndex($db, 'xmfblog_posts', 'idx_publish', ['date_publish', 'date_expire']);

        $this->createTable($db, 'xmfblog_categories', [/* ... */]);
        $this->createTable($db, 'xmfblog_comments', [/* ... */]);
    }

    public function down(\XoopsDatabase $db): void
    {
        $db->exec('DROP TABLE IF EXISTS ' . $db->prefix('xmfblog_comments'));
        $db->exec('DROP TABLE IF EXISTS ' . $db->prefix('xmfblog_categories'));
        $db->exec('DROP TABLE IF EXISTS ' . $db->prefix('xmfblog_posts'));
    }
}
```

Helper methods available in `Migration`:
- `createTable($db, $table, $columns)` — generates `CREATE TABLE`
- `addColumn($db, $table, $column, $definition)` — generates `ALTER TABLE ADD`
- `dropColumn($db, $table, $column)` — generates `ALTER TABLE DROP`
- `addIndex($db, $table, $name, $columns)` — generates `CREATE INDEX`

All methods use `$db->prefix()` automatically.

#### Running Migrations

```php
use Xmf\Database\MigrationRunner;

$runner = new MigrationRunner($db);

// Apply pending migrations
$applied = $runner->migrate('xmfblog', [
    new BlogMigration(),
    new AddCommentsTableMigration(),  // version 1.1.0
]);
// Returns: ['1.0.0', '1.1.0']

// Rollback last migration
$rolledBack = $runner->rollback('xmfblog', [
    new AddCommentsTableMigration(),
    new BlogMigration(),
], steps: 1);
// Returns: ['1.1.0']
```

The runner tracks applied versions in an `xmf_migrations` table and skips already-applied migrations.

---

## 18. Value Objects

Value objects are immutable, self-validating types for common data:

### Email

```php
use Xmf\ValueObject\Email;

$email = new Email('author@example.com');
(string)$email;     // 'author@example.com'
$email->domain();   // 'example.com'

new Email('invalid');  // throws InvalidArgumentException
```

### Money

```php
use Xmf\ValueObject\Money;

$price = new Money(1999, 'USD');    // $19.99
$price->formatted();                // '19.99 USD'

$total = $price->add(new Money(501, 'USD'));  // $25.00
$total->amount;                     // 2500

json_encode($price);  // {"amount":1999,"currency":"USD"}
```

### Status

Wraps any backed enum with a type-safe API:

```php
use Xmf\ValueObject\Status;
use Xmf\Enum\ObjectStatus;

$status = new Status(ObjectStatus::Published);
$status->is(ObjectStatus::Published);  // true
$status->is(ObjectStatus::Draft);      // false
$status->label();                      // 'Published'
```

**Why value objects?** They prevent invalid data from entering your system. An `Email` is always valid. `Money` arithmetic always matches currencies. These guarantees propagate through your entire codebase.

---

## 19. Legacy Compatibility: The Preload Bridge

### The Problem

Existing XOOPS modules use preload classes with `eventMethodName()` conventions. You can't require every module to rewrite their event handling overnight.

### The Solution: PreloadEventBridge

The bridge automatically converts legacy preload methods to modern `DotEvent` listeners:

```php
use Xmf\Legacy\Event\PreloadEventBridge;

$bridge = new PreloadEventBridge($listenerProvider);

// Legacy preload class
class MyModulePreload {
    public function eventCoreOutputStart(array $args = []) {
        // This existing code keeps working
    }
    public function eventModuleLoaded(array $args = []) {
        // This too
    }
}

// Register it — methods are auto-converted to dot-notation listeners
$bridge->registerPreload(new MyModulePreload());
```

Now these fire automatically:

```php
$bus->dispatch(new DotEvent('core.output.start'));  // calls eventCoreOutputStart()
$bus->dispatch(new DotEvent('module.loaded'));       // calls eventModuleLoaded()
```

The conversion rule: `eventCoreOutputStart` → remove `event` → split on capitals → lowercase → join with dots → `core.output.start`.

This bridge is intentionally in `Xmf\Legacy\Event` and marked `@deprecated` — it exists to ease migration, not as a permanent solution. New code should use typed events or `DotEvent` directly.

---

## 20. Service Manager

### The Problem

XOOPS 2.6 introduced a service system, but there's no standard way for modules to register and discover service providers with priority.

### The Solution: ServiceManager

```php
use Xmf\Service\ServiceManager;

$sm = new ServiceManager();

// Register a search provider (priority 10 = high)
$sm->registerProvider('search', new MySearchProvider(), 10);

// Register a fallback (priority 0 = low)
$sm->registerProvider('search', new BasicSearchProvider(), 0);

// Get the highest-priority provider
$provider = $sm->getProvider('search');
// Returns MySearchProvider (priority 10 wins)

// Unknown services return a NullServiceProvider (swallows all calls)
$null = $sm->getProvider('nonexistent');
$null->anyMethod();  // returns null, no errors
```

This lets modules offer and consume services without hard dependencies.

---

## 21. Content Versioning

### The Problem

Every XOOPS content module (Publisher, News, WF-Channel) re-invents revision tracking. Some use duplicate rows, some use serialized columns, none share a common approach. Rolling back a bad edit means restoring from database backups.

### The Solution: VersionableTrait + VersionManager

XMF provides a two-part versioning system:

1. **`VersionableTrait`** — added to the entity, declares which fields to snapshot
2. **`VersionManager`** — standalone service that stores snapshots in the `xmf_versions` table

#### Making an Entity Versionable

Add `VersionableTrait` and implement `VersionAwareInterface`:

```php
use Xmf\Versioning\VersionableTrait;
use Xmf\Versioning\VersionAwareInterface;

class Post extends \XoopsObject implements VersionAwareInterface
{
    use VersionableTrait;

    public function __construct()
    {
        // ... other initVar calls ...
        $this->initVersionFields(); // adds version_id, version_parent_id, version_label
    }
}
```

`getVersionableFields()` (from the trait) automatically returns ALL fields except the version metadata fields. The snapshot captures a complete picture of the entity at that point in time.

#### Creating Snapshots

```php
use Xmf\Versioning\VersionManager;

$vm = new VersionManager($db, $eventBus);

// Manual snapshot (e.g., before a user edit)
$snapshot = $vm->createVersion($post, 'Published version 2');
// Returns a VersionSnapshot with id, versionNumber, data[], createdAt
```

#### Auto-Versioning on Save

The blog module auto-versions on every update via `PostRepository::beforeSave()`:

```php
class PostRepository extends EntityRepository
{
    protected function beforeSave(object $entity): bool
    {
        /** @var Post $entity */
        $now = time();
        $entity->dateUpdated = $now;
        if ($entity->isNew()) {
            $entity->dateCreated = $now;
        } else {
            $this->autoVersion($entity); // snapshot before overwriting
        }
        return true;
    }

    private function autoVersion(object $entity): void
    {
        if (!$entity instanceof VersionAwareInterface) {
            return;
        }
        $container = Container::getInstance();
        if ($container->has('version_manager')) {
            $vm = $container->get('version_manager');
            $vm->createVersion($entity, 'Auto-save before update ' . date('Y-m-d H:i:s'));
        }
    }
}
```

#### Version History

```php
// Get all versions for a post (newest first)
$history = $vm->getHistory(Post::class, $postId, limit: 50);
// Returns VersionSnapshot[] with id, versionNumber, label, data, createdAt

// Get a specific version
$snapshot = $vm->getVersion($versionId);

// Count versions
$count = $vm->getVersionCount(Post::class, $postId);
```

#### Comparing Versions (Diff)

```php
// Compare two snapshots
$diff = $vm->diff($snapshot1->id, $snapshot2->id);
// Returns VersionDiff with changed fields, old/new values
```

The admin `versions.php` page renders a side-by-side diff table with color-coded changes (red for old, green for new).

#### Restoring Versions

```php
// Restore a snapshot onto the entity
$vm->restore($oldSnapshot, $post);
$handler->insert($post); // persist the restored state
```

The `restore()` method calls `setVar()` for each field in the snapshot. It does NOT persist — that follows the Data Mapper pattern. The admin page creates a safety snapshot of the current state before restoring.

#### Pruning Old Versions

```php
// Keep only the 10 most recent versions
$deleted = $vm->prune(Post::class, $postId, keepLast: 10);
```

#### Version Events

VersionManager dispatches events through the EventBus:
- `VersionCreated` — after a snapshot is saved
- `VersionRestored` — after a restore
- `VersionPruned` — after old versions are deleted

These can trigger notifications, cache invalidation, or audit logging.

### Admin UI: Version History Panel

The blog module's post edit form includes a version history panel (`admin/post.php`). It displays a table of past versions with:
- Version number and label
- Creation date
- Radio buttons for selecting two versions to compare
- "Compare Selected" and "Compare with Current" buttons
- "Restore" links for each version

The comparison page (`admin/versions.php`) renders:
- Side-by-side diff table with changed fields highlighted
- Unchanged fields in a collapsible `<details>` section
- Restore buttons for either version

---

## 22. Media Management

XMF's `MediaManager` provides a complete file upload, storage, and image processing pipeline. Instead of writing ~150 lines of manual upload/resize code, you get a clean API.

### Container Registration

```php
// In BlogModule::boot()
$container->singleton('image_transformer', fn() =>
    new ImageTransformer(new GdTransformerDriver())
);
$container->singleton('media_storage', fn() =>
    new LocalStorageAdapter($uploadDir, $uploadUrl)
);
$container->singleton('media_manager', fn(Container $c) =>
    new MediaManager(
        $c->get('db'),
        $c->get('media_storage'),
        $c->get('image_transformer'),
        $c->get('event_bus'),   // optional: dispatches MediaUploaded events
        $c->get('cache'),       // optional: caches lookups
    )
);
```

### Entity Integration — MediaTrait

```php
class Post extends \XoopsObject implements MediaAwareInterface
{
    use MediaTrait;  // Adds media_id field + getMediaId()/setMediaId()

    public function __construct()
    {
        // ...
        $this->initMediaField();  // registers media_id initVar
    }
}
```

### Upload with Resize/Crop (admin/post.php)

```php
if (!empty($_FILES['featured_upload']['tmp_name'])) {
    $mediaManager = $container->get('media_manager');
    $mediaEntry = $mediaManager->uploadFromFiles('featured_upload', 'xmfblog');

    if ($mediaEntry !== null) {
        // Server-side resize to max 1200x800
        $transformer = $container->get('image_transformer');
        $transformer->resize($mediaEntry->path, 1200, 800);

        $post->setVar('media_id', $mediaEntry->id);
        $post->setVar('featured_image', $mediaEntry->url);
    }
}
```

### Retrieving Media (frontend)

```php
$mediaId = (int) $post->getVar('media_id', 'n');
if ($mediaId > 0) {
    $media = $mediaManager->findById($mediaId);
    $imageUrl = $media?->url;
}
```

**Time saved:** ~150 lines of manual upload/GD handling → ~15 lines with MediaManager.

---

## 22a. Image Size Registry, EXIF Normalization & Format Conversion (Wave 7a)

Wave 7a extends the Media subsystem with three capabilities that modern CMS platforms consider standard: declarative image sizes, automatic EXIF fix on upload, and WebP/AVIF format conversion.

### Image Size Registry — Declare Once, Generate on Demand

Instead of hardcoding resize dimensions in every module, register named sizes at boot time. MediaManager generates derivatives lazily on first access and caches them.

```php
use Xmf\Media\ImageSizeConfig;
use Xmf\Media\ImageSizeRegistry;

// In BlogModule::boot() — register sizes for this module
$registry = ImageSizeRegistry::getInstance();
$registry->register(new ImageSizeConfig('card',   400, 300, 'crop'));
$registry->register(new ImageSizeConfig('hero',  1200, null, 'fit', null, 'webp'));
$registry->register(new ImageSizeConfig('thumb',  150, 150, 'crop', 85));
```

**ImageSizeConfig parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `name` | string | — | Unique identifier (e.g., `'card'`, `'hero_banner'`) |
| `width` | int | — | Target width in pixels |
| `height` | int\|null | null | Target height (null = proportional) |
| `mode` | string | `'fit'` | `'fit'` (maintain ratio) or `'crop'` (exact dimensions) |
| `quality` | int\|null | null | Output quality (null = driver default) |
| `format` | string\|null | null | Convert to format: `'webp'`, `'avif'`, `'jpeg'`, etc. |

**Using derivatives in templates:**

```php
// In admin/post.php or frontend — get a derivative URL
$cardUrl = $mediaManager->getDerivativeUrl($mediaItem, 'card');
// Returns: https://example.com/uploads/derivatives/42_card.jpg

$heroUrl = $mediaManager->getDerivativeUrl($mediaItem, 'hero');
// Returns: https://example.com/uploads/derivatives/42_hero.webp
// (auto-converted to WebP because the size config specifies format: 'webp')
```

The derivative is generated the first time it's requested, then served from cache on subsequent calls. The `DerivativeGenerated` event fires when a new derivative is created, enabling downstream processing (e.g., CDN purge).

### Container Registration with Registry

```php
// In BlogModule::boot()
$container->singleton('size_registry', function () {
    $registry = ImageSizeRegistry::getInstance();
    $registry->register(new ImageSizeConfig('card',  400, 300, 'crop'));
    $registry->register(new ImageSizeConfig('hero', 1200, null, 'fit', null, 'webp'));
    $registry->register(new ImageSizeConfig('thumb', 150, 150, 'crop'));
    return $registry;
});

$container->singleton('media_manager', fn(Container $c) =>
    new MediaManager(
        $c->get('db'),
        $c->get('media_storage'),
        $c->get('image_transformer'),
        $c->get('event_bus'),
        $c->get('cache'),
        $c->get('size_registry'),  // NEW: enables getDerivativeUrl()
    )
);
```

### Auto EXIF Normalization

Phone camera uploads embed EXIF orientation tags (values 2-8) that cause images to display rotated in browsers. **Wave 7a fixes this automatically on upload** — no module code needed.

When `MediaManager::upload()` processes an image file, it calls `ImageTransformer::normalize()` which:
1. Reads the EXIF orientation tag (JPEG only, guarded by `function_exists('exif_read_data')`)
2. Applies the required rotation/flip (GD `imagerotate()` + `imageflip()`)
3. Re-saves the image — which naturally strips all EXIF metadata (privacy win)
4. Returns the corrected dimensions

This happens transparently. Existing upload code gains EXIF normalization without changes.

You can also call `normalize()` directly for batch processing:

```php
$transformer = $container->get('image_transformer');
$result = $transformer->normalize('/path/to/photo.jpg', '/path/to/photo.jpg'); // in-place
// $result = ['width' => 3024, 'height' => 4032, 'orientation' => 6]
// Image was rotated 270° (portrait orientation) and EXIF stripped
```

### Format Conversion (WebP/AVIF)

Convert images between formats using the GD driver:

```php
$transformer = $container->get('image_transformer');

// Convert JPEG to WebP
$result = $transformer->convert('/source.jpg', '/output.webp', 'webp', 80);
// $result = ['width' => 800, 'height' => 600, 'format' => 'webp']

// Check format support at runtime
if ($transformer->isFormatSupported('avif')) {
    $transformer->convert('/source.jpg', '/output.avif', 'avif');
}
```

**Supported formats:**

| Format | Support |
|--------|---------|
| `jpeg` / `jpg` | Always available |
| `png` | Always available |
| `gif` | Always available |
| `webp` | PHP 7.1+ with GD WebP support (almost universal) |
| `avif` | PHP 8.1+ with GD AVIF support (depends on build) |

Use `isFormatSupported()` to check runtime availability before converting. The `convert()` method throws `\RuntimeException` for unsupported formats.

**Time saved:** ~200 lines of manual EXIF handling + format detection + derivative management → 3 lines with ImageSizeRegistry.

---

## 23. Queue and Async Jobs

The Queue system lets you defer expensive operations (emails, API calls, image processing) to background workers.

### Container Registration

```php
$container->singleton('queue', fn(Container $c) =>
    new Queue($c->get('db'), $c->get('event_bus'))
);
$container->singleton('queue_runner', fn(Container $c) =>
    new QueueRunner($c->get('db'), $c, $c->get('event_bus'))
);
```

### Defining a Job

```php
use Xmf\Queue\Attribute\QueuedJob;
use Xmf\Queue\JobInterface;

#[QueuedJob(queue: 'notifications', retries: 3, backoff: 30)]
final class SendCommentNotificationJob implements JobInterface
{
    public function __construct(
        private readonly int $commentId,
        private readonly int $postId,
        private readonly string $authorName,
    ) {}

    public function handle(\Xmf\Container\Container $c): void
    {
        $nm = $c->get('notification_manager');
        $handler = $c->get('post_handler');
        $post = $handler->get($this->postId);

        $nm->send(
            userId: (int) $post->getVar('uid'),
            payload: new NotificationPayload(
                subject: 'New comment on: ' . $post->getVar('title'),
                body: $this->authorName . ' commented on your post.',
                type: 'new_comment',
            ),
        );
    }
}
```

### Pushing a Job

```php
// In comment submission handler
$queue->push(new SendCommentNotificationJob($commentId, $postId, $authorName));
```

### Processing Jobs

Jobs are processed by `QueueRunner`, typically via XOOPS cron:

```php
$runner = $container->get('queue_runner');
$runner->processQueue('notifications', batchSize: 10);
```

**Time saved:** ~100 lines of custom async logic → ~20 lines with Queue.

---

## 24. Notifications

`NotificationManager` provides multi-channel notification delivery with async support via Queue.

### Container Registration

```php
$container->singleton('notification_manager', function (Container $c) {
    $nm = new NotificationManager($c->get('event_bus'), $c->get('queue'));
    // Register delivery channels
    $nm->registerChannel('in_app', new InAppChannel($c->get('db')));
    $nm->registerChannel('email', new EmailChannel(
        fn(int $uid): string => (new \XoopsUser($uid))->getVar('email')
    ));
    return $nm;
});
```

### Sending a Notification

```php
$nm->send(
    userId: $postAuthorId,
    payload: new NotificationPayload(
        subject: 'New comment on your post',
        body: 'Someone commented on "' . $postTitle . '"',
        type: 'new_comment',
    ),
);
```

### Dashboard Unread Count

```php
$unreadNotifications = $nm->countUnread((int) $GLOBALS['xoopsUser']->getVar('uid'));
```

**Time saved:** ~100 lines of custom email/notification logic → ~15 lines with NotificationManager.

---

## 25. Audit Logging

`AuditLogger` provides an append-only activity trail for entity changes. Every create, update, and delete is recorded with actor, IP, and change details.

### Container Registration

```php
$container->singleton('audit_logger', fn(Container $c) =>
    new AuditLogger($c->get('db'), $c->get('event_bus'))
);
```

### Entity Interface — AuditableInterface

```php
class Post extends \XoopsObject implements AuditableInterface
{
    public function getAuditEntityType(): string { return 'post'; }
    public function getAuditEntityId(): int { return (int) $this->getVar('post_id', 'n'); }
}
```

### Automatic Logging in Handler Hooks

```php
protected function afterSave(object $entity, bool $isNew): void
{
    $container = Container::getInstance();
    if (!$container->has('audit_logger')) return;

    $auditLogger = $container->get('audit_logger');
    $actorId = isset($GLOBALS['xoopsUser']) ? (int) $GLOBALS['xoopsUser']->getVar('uid') : 0;

    // Get changed fields from ChangeTrackingTrait
    $newValues = method_exists($entity, 'getDirtyFields') ? $entity->getDirtyFields() : [];

    $auditLogger->log(
        actorId: $actorId,
        actorIp: $_SERVER['REMOTE_ADDR'] ?? '',
        entityType: $entity->getAuditEntityType(),
        entityId: $entity->getAuditEntityId(),
        action: $isNew ? 'create' : 'update',
        newValues: json_encode($newValues, JSON_THROW_ON_ERROR),
    );
}
```

### Admin Audit Log Viewer (admin/audit.php)

```php
$query = new AuditQuery(
    entityType: $filterEntity !== '' ? $filterEntity : null,
    action: $filterAction !== '' ? $filterAction : null,
    module: 'xmfblog',
    limit: 100,
);
$entries = $auditLogger->query($query);
// Render entries table with date, actor, entity type/id, action, changes
```

**Time saved:** ~200 lines of custom audit table/query code → ~10 lines with AuditLogger.

---

## 26. Report Builder

`ReportBuilder` generates declarative reports from table data, with optional CSV export.

### Defining a Report

```php
use Xmf\Enum\AggregateFunction;
use Xmf\Report\ReportBuilder;
use Xmf\Report\ReportColumn;
use Xmf\Report\ReportDefinition;

// Posts by Status — count posts grouped by status
$statusDef = new ReportDefinition('Posts by Status', 'xmfblog_posts');
$statusDef->addColumn(new ReportColumn('status', 'Status'));
$statusDef->addColumn(new ReportColumn('post_id', 'Total', AggregateFunction::Count));
$statusDef->groupBy('status');

$result = $reportBuilder->execute($statusDef, $db);
// $result->rows = [['status' => 1, 'post_id' => 42], ...]
// $result->columns = [ReportColumn, ReportColumn]
```

### CSV Export

```php
$exportResult = $reportBuilder->export($statusDef, new CsvFormat(), $db);
header('Content-Type: text/csv');
echo $exportResult->output;
```

### Dashboard Integration

The admin dashboard uses `ReportBuilder` for all statistics — replacing raw SQL queries with declarative report definitions.

**Time saved:** ~80 lines of raw SQL + manual HTML table → ~20 lines with ReportBuilder.

---

## 27. Import/Export Pipelines

`ImportPipeline` and `ExportPipeline` handle data exchange in CSV or JSON format with field mapping.

### Export

```php
use Xmf\ImportExport\CsvFormat;
use Xmf\ImportExport\ExportPipeline;

$extractClosure = function (object $post): array {
    return [
        'title'       => $post->getVar('title', 'n'),
        'body'        => $post->getVar('body', 'n'),
        'category_id' => $post->getVar('category_id', 'n'),
        'status'      => $post->getVar('status', 'n'),
    ];
};

$pipeline = new ExportPipeline(new CsvFormat(), null, $extractClosure);
$result = $pipeline->run($posts);
// $result->output contains the CSV string
```

### Import

```php
use Xmf\ImportExport\FieldMap;
use Xmf\ImportExport\ImportPipeline;

$fieldMap = new FieldMap();
$fieldMap->addMapping('title', 'title');
$fieldMap->addMapping('body', 'body');
$fieldMap->addMapping('category_id', 'category_id');

$persistClosure = function (array $row) use ($handler): bool {
    $post = new Post();
    $post->setNew();
    foreach ($row as $key => $value) {
        $post->setVar($key, $value);
    }
    return $handler->insert($post) !== false;
};

$pipeline = new ImportPipeline(new CsvFormat(), $fieldMap, null, $persistClosure);
$result = $pipeline->run($csvString);
// $result->successCount, $result->failureCount, $result->totalRows
```

**Time saved:** ~300 lines of custom CSV parsing/generation → ~30 lines with pipelines.

---

## 28. Domain Events

Domain events let entities record significant lifecycle changes. Events are dispatched after persistence via `Repository::save()`.

### DomainEventTrait on Entity

```php
class Post extends \XoopsObject implements DomainEventAwareInterface
{
    use DomainEventTrait;  // Adds recordEvent() and releaseEvents()
}
```

### Recording Events in Handler

```php
protected function afterSave(object $entity, bool $isNew): void
{
    // Record PostPublishedEvent when status changes to Published
    if ((int) $entity->getVar('status') === ObjectStatus::Published->value) {
        $entity->recordEvent(new PostPublishedEvent(
            postId: (int) $entity->getVar('post_id'),
            title: (string) $entity->getVar('title'),
            authorId: (int) $entity->getVar('uid'),
        ));
    }
}
```

### Event Flushing

Events are flushed automatically when using `Repository::save()`:

```php
$repo->save($post);  // Handler hooks fire → events recorded → Repository flushes via EventBus
```

This is why admin approve/publish operations use `$repo->save()` instead of `$handler->insert()`.

### Event Classes

```php
final readonly class PostPublishedEvent
{
    public function __construct(
        public int $postId,
        public string $title,
        public int $authorId,
    ) {}
}
```

---

## 29. Scheduled Tasks

The Scheduler system runs periodic tasks via XOOPS cron using standard cron expressions.

### Defining a Task

```php
use Xmf\Plugin\ScheduledTaskInterface;

final class ExpirePostsTask implements ScheduledTaskInterface
{
    public function __construct(private readonly \Xmf\Container\Container $container) {}

    public function getName(): string { return 'xmfblog.expire_posts'; }
    public function getSchedule(): string { return '0 * * * *'; }  // hourly

    public function execute(): void
    {
        $db = $this->container->get('db');
        $now = time();
        $published = \Xmf\Enum\ObjectStatus::Published->value;
        $draft = \Xmf\Enum\ObjectStatus::Draft->value;

        $sql = 'UPDATE ' . $db->prefix('xmfblog_posts')
             . ' SET status = ' . $draft
             . ' WHERE status = ' . $published
             . ' AND date_expire > 0 AND date_expire < ' . $now;
        $db->exec($sql);
    }
}
```

### Registration

```php
$container->singleton('task_registry', function (Container $c) {
    $registry = new TaskRegistry($c->get('cron_matcher'));
    $registry->add(new PruneOldVersionsTask($c));    // Daily 3am
    $registry->add(new ExpirePostsTask($c));          // Hourly
    $registry->add(new PruneAuditLogTask($c));        // Weekly Sunday 4am
    return $registry;
});
```

xmfblog includes three scheduled tasks:
- **PruneOldVersionsTask** — deletes version snapshots older than 90 days (daily)
- **ExpirePostsTask** — sets published posts past `date_expire` to Draft (hourly)
- **PruneAuditLogTask** — prunes audit log entries older than 90 days (weekly)

**Time saved:** ~120 lines of custom cron script → ~30 lines with TaskRegistry.

---

## 30. Plugin System

`PluginManager` discovers and loads plugin classes from a `plugins/` directory. Plugins can listen to events via `#[EventListener]` attributes.

### Registration

```php
$container->singleton('plugin_manager', function (Container $c) {
    $pm = new PluginManager($c->get('event_bus'));
    $pm->discover(__DIR__ . '/../plugins');  // scans for PluginInterface classes
    $pm->bootAll();
    return $pm;
});
```

### MarkdownFormatterPlugin

```php
// plugins/MarkdownFormatterPlugin.php (unnamespaced)
class MarkdownFormatterPlugin implements PluginInterface
{
    public function initialize(): void {}
    public function boot(): void {}

    public function formatBody(string $body): string
    {
        // Convert **bold**, *italic*, ## headings, [links](url)
        $body = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $body);
        $body = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $body);
        // ... more conversions
        return $body;
    }
}
```

### SpamFilterPlugin with Event Listener

```php
use Xmf\Plugin\Attribute\EventListener;
use XmfBlog\CommentCreatedEvent;

class SpamFilterPlugin implements PluginInterface
{
    #[EventListener(CommentCreatedEvent::class, priority: 100)]
    public function onCommentCreated(CommentCreatedEvent $event): void
    {
        $blacklist = ['viagra', 'casino', 'lottery'];
        foreach ($blacklist as $word) {
            if (stripos($event->content, $word) !== false) {
                // Mark comment as rejected
                // ...
            }
        }
    }
}
```

### Frontend Integration

```php
// Apply plugin formatting to post body
foreach ($pluginManager->getPlugins() as $plugin) {
    if (method_exists($plugin, 'formatBody')) {
        $formattedBody = $plugin->formatBody($formattedBody);
    }
}
```

---

## 31. Convention Registry

`ConventionRegistry` standardizes naming patterns across modules.

```php
$container->singleton('conventions', function () {
    $registry = new ConventionRegistry();
    $registry->register('xmfblog');
    return $registry;
});

// Usage:
$naming = $registry->naming('xmfblog');
$naming->tableName('posts');      // 'xmfblog_posts'
$naming->templateName('index');   // 'xmfblog_index.tpl'
$naming->constantName('TITLE');   // '_MI_XMFBLOG_TITLE'
```

---

## 32. Migration Runner

`MigrationRunner` provides version-tracked database migrations with rollback support.

### Migration Class

```php
class BlogMigration extends Migration
{
    public function version(): string { return '1.1.0'; }

    public function up(\XoopsDatabase $db): void
    {
        // Create module tables
        $this->createTable($db, 'xmfblog_posts', [...]);
        $this->addIndex($db, 'xmfblog_posts', 'idx_status', ['status']);

        // Create XMF infrastructure tables
        $db->exec(Queue::createTableSql($db->prefix('')));
        $db->exec(MediaManager::createTableSql($db->prefix('')));
        $db->exec(InAppChannel::createTableSql($db->prefix('')));
        $db->exec(AuditLogger::createTableSql($db->prefix('')));
    }

    public function down(\XoopsDatabase $db): void
    {
        $db->exec('DROP TABLE IF EXISTS ' . $db->prefix('xmfblog_posts'));
        // ...
    }
}
```

### Running Migrations

```php
$runner = $container->get('migration_runner');
$runner->migrate(new BlogMigration(), $db);
```

Each migration version is tracked in the `xmf_migrations` table. Running the same migration twice is a no-op.

---

## 33. Time and Code Savings

Here is a feature-by-feature comparison showing the reduction in boilerplate code when using XMF components vs. writing everything manually:

| Feature | Manual Code | With XMF | Savings |
|---------|------------|----------|---------|
| **Media upload + resize** | ~150 lines (GD, move_uploaded_file, path handling) | ~15 lines (MediaManager + ImageTransformer) | **90%** |
| **Dashboard reports** | ~80 lines (raw SQL + GROUP BY + HTML table) | ~20 lines (ReportBuilder + ReportDefinition) | **75%** |
| **Audit trail** | ~200 lines (custom table, INSERT, admin viewer) | ~10 lines (AuditLogger + AuditQuery) | **95%** |
| **CSV import/export** | ~300 lines (fgetcsv, fputcsv, validation, error handling) | ~30 lines (ImportPipeline + ExportPipeline) | **90%** |
| **Notifications** | ~100 lines (custom email, storage, unread count) | ~15 lines (NotificationManager + channels) | **85%** |
| **Scheduled tasks** | ~120 lines (custom cron script, locking, logging) | ~30 lines (TaskRegistry + ScheduledTaskInterface) | **75%** |
| **Async jobs** | ~100 lines (custom job table, runner, retry logic) | ~20 lines (Queue + JobInterface) | **80%** |
| **Entity CRUD** | ~270 lines (switch/case, forms, validation) | ~40 lines (ResourceController) | **85%** |
| **Domain events** | ~80 lines (custom event dispatch, listener registration) | ~10 lines (DomainEventTrait + EventBus) | **87%** |
| **Spam filtering** | ~60 lines (custom filter logic, config, hooks) | ~20 lines (SpamFilterPlugin + #[EventListener]) | **67%** |
| **Content versioning** | ~200 lines (custom snapshot table, diff, restore) | ~20 lines (VersionableTrait + VersionManager) | **90%** |
| **Database migrations** | ~100 lines (version tracking, SQL execution) | ~15 lines (Migration + MigrationRunner) | **85%** |
| **TOTAL** | **~1,860 lines** | **~245 lines** | **~87%** |

The xmfblog module demonstrates all 38+ XMF features in approximately **2,500 lines of module code** — a codebase that would require **~8,000+ lines** without the framework.

---

## 34. Putting It All Together: Module Bootstrap

Here is the complete `BlogModule::boot()` method showing how all components wire together:

```php
class BlogModule
{
    public static function boot(): Container
    {
        // 1. Get the container singleton
        $container = Container::getInstance();

        // 2. Register infrastructure
        $container->singleton('db', fn() =>
            \XoopsDatabaseFactory::getDatabaseConnection()
        );

        // 3. Register event system
        $container->singleton('events', fn() =>
            new ListenerProvider()
        );
        $container->singleton('event_bus', fn(Container $c) =>
            new EventBus($c->get('events'))
        );

        // 4. Register configuration with schemas
        $container->singleton('config', function () {
            $config = new ConfigManager();
            $config->defineSchema(
                new ConfigSchema('posts.per_page', 'int', 10),
                new ConfigSchema('cache.ttl', 'int', 3600),
                new ConfigSchema('api.enabled', 'bool', true),
                new ConfigSchema('comments.enabled', 'bool', true),
            );
            $config->load([/* values from xoopsModuleConfig */]);
            return $config;
        });

        // 5. Register cache with module scope
        $container->singleton('cache', function () {
            $cache = new CacheManager();
            $cache->registerBackend('memory', new InMemoryCache());
            $cache->setDefault('memory');
            return $cache->forModule('xmfblog');
        });

        // 6. Register repositories (EntityRepository — DDD path)
        $container->singleton('post_repo', fn(Container $c) =>
            new PostRepository($c->get('db'), Post::class, $c->get('event_bus'))
        );
        $container->singleton('category_repo', fn(Container $c) =>
            new CategoryRepository($c->get('db'), Category::class, $c->get('event_bus'))
        );

        // 8. Register permissions
        $container->singleton('permission', fn() =>
            new ItemPermission('xmfblog', 'post_view')
        );

        // 9. Register HTTP pipeline with middleware
        $container->singleton('pipeline', function () {
            $pipeline = new Pipeline();
            $pipeline->pipe(new AuthMiddleware());
            return $pipeline;
        });

        // 10. Register API controller (depends on repo + pipeline)
        $container->singleton('api_controller', fn(Container $c) =>
            new PostApiController($c->get('post_repo'), $c->get('pipeline'))
        );

        // 11. Register service manager
        $container->singleton('service_manager', fn() =>
            new ServiceManager()
        );

        // 12. Register legacy bridge
        $container->singleton('preload_bridge', fn(Container $c) =>
            new PreloadEventBridge($c->get('events'))
        );

        return $container;
    }
}
```

Notice the dependency chain: `api_controller` → `post_repo` → `post_handler` → `db`. The container resolves these lazily — nothing is created until first accessed.

---

## 35. Frontend Pages

### Post Listing (index.php, op=list)

The main page demonstrates `QueryBuilder`, `Repository`, and XOOPS Smarty templates working together:

```php
require_once __DIR__ . '/header.php';

$perPage = (int)($helper->getConfig('posts_per_page') ?? 10);
$page    = max(1, \Xmf\Request::getInt('page', 1));
$catId   = \Xmf\Request::getInt('category_id', 0);

// Build type-safe query
$query = QueryBuilder::for('xmfblog_posts')
    ->where('status', ComparisonOperator::Equal, ObjectStatus::Published->value)
    ->where('row_flag', ComparisonOperator::Equal, 'A')
    ->orderBy('date_created', 'DESC')
    ->limit($perPage, ($page - 1) * $perPage);

if ($catId > 0) {
    $query->where('category_id', ComparisonOperator::Equal, $catId);
}

$repo  = $container->get('post_repo');
$posts = $repo->findAll($query);

$xoopsTpl->assign('posts', $posts);
```

### Single Post View

Demonstrates `MetaGenerator`, `EventBus`, and view-count tracking:

```php
case 'view':
    $postId = \Xmf\Request::getInt('id', 0);
    $post = $repo->find($postId);

    // Increment view count via property hooks
    $post->viewCount = $post->viewCount + 1;
    $repo->save($post);

    // SEO meta tags via property hooks
    $meta = new MetaGenerator(
        $post->metaTitle ?: $post->title,
        $post->metaDescription ?: $post->body,
        $post->shortUrl ?: 'post-' . $postId,
    );
    $xoopsTpl->assign('xoops_pagetitle', $meta->generateTitle());

    // Dispatch event for analytics/caching
    $bus->dispatch(new DotEvent('post.viewed', ['id' => $postId]));
```

---

## 36. Admin Panel

The admin panel demonstrates the full XOOPS admin architecture with `Xmf\Module\Admin`, dashboard statistics, CRUD pages, permissions, version history, and moderation workflows.

### Admin Menu Structure

The menu is defined in `admin/menu.php` using `$adminmenu[]` entries:

```php
$pathIcon32 = \Xmf\Module\Admin::iconUrl('', '32');
$adminmenu = [];

$adminmenu[] = [
    'title' => _MI_XMFBLOG_ADMIN_DASHBOARD,
    'link'  => 'admin/index.php',
    'icon'  => $pathIcon32 . '/home.png',
];
$adminmenu[] = [
    'title' => _MI_XMFBLOG_ADMIN_POSTS,
    'link'  => 'admin/post.php',
    'icon'  => $pathIcon32 . '/content.png',
];
// ... Categories, Comments, Permissions, Blocks Admin, Clone, About
```

The key pattern: `$pathIcon32` resolves to the standard XOOPS admin icon directory. Every menu entry has `title`, `link`, and `icon`.

### Dashboard (admin/index.php)

The dashboard uses `Xmf\Module\Admin::addInfoBox()` for statistics:

```php
$adminObject = ModuleAdmin::getInstance();

// Count posts by status using GROUP BY
$sql = "SELECT status, COUNT(*) AS cnt FROM `{$postTable}` GROUP BY status";
$result = $db->query($sql);
while (($row = $db->fetchArray($result)) !== false) {
    // Accumulate by status using ObjectStatus enum
}

// Add info boxes with color coding
$adminObject->addInfoBox(_AM_XMFBLOG_DASH_POSTS);
$adminObject->addInfoBoxLine(
    _AM_XMFBLOG_DASH_POSTS,
    '<span class="bold">' . _AM_XMFBLOG_DASH_TOTAL . '</span>',
    (string) $totalPosts,
    'Green'
);
$adminObject->addInfoBoxLine(
    _AM_XMFBLOG_DASH_POSTS,
    '<span class="bold">' . _AM_XMFBLOG_DASH_PENDING . '</span>',
    (string) $pendingPosts,
    $pendingPosts > 0 ? 'Red' : 'Green'  // Red when items need attention
);

$adminObject->displayNavigation('index.php');
$adminObject->displayIndex();
```

### Post Management (admin/post.php)

Post management includes listing, edit/add forms, status workflows, bulk operations, and version history:

```php
// List view with status badges and action buttons
foreach ($posts as $post) {
    $statusBadge = match (ObjectStatus::tryFrom($statusVal)) {
        ObjectStatus::Published => '<span class="xmf-badge xmf-badge--success">Published</span>',
        ObjectStatus::Pending   => '<span class="xmf-badge xmf-badge--warning">Pending</span>',
        ObjectStatus::Draft     => '<span class="xmf-badge xmf-badge--default">Draft</span>',
        default                 => '<span class="xmf-badge">Unknown</span>',
    };
}

// Status-conditional action buttons
// Pending posts: Approve + Reject
// Draft posts: Publish
// Published posts: Unpublish

// Bulk operations with checkbox column
case 'bulk':
    $selectedIds = \Xmf\Request::getArray('post_ids', []);
    $bulkAction = \Xmf\Request::getString('bulk_action', '');
    foreach ($selectedIds as $id) {
        // Apply publish/unpublish/delete to each
    }
```

### Version History in Post Edit

The post edit form includes a version history panel:

```php
// Load version history for this post
$vm = $container->get('version_manager');
$history = $vm->getHistory(Post::class, $postId, 20);

// Render version table with radio buttons for comparison
foreach ($history as $snapshot) {
    echo '<tr>';
    echo '<td><input type="radio" name="v1" value="' . $snapshot->id . '"></td>';
    echo '<td><input type="radio" name="v2" value="' . $snapshot->id . '"></td>';
    echo '<td>' . $snapshot->versionNumber . '</td>';
    echo '<td>' . htmlspecialchars($snapshot->label) . '</td>';
    echo '</tr>';
}
```

### Comments Admin (admin/comments.php)

Demonstrates moderation workflow with approve/reject/delete operations:

```php
case 'approve':
    $comment->setVar('status', ObjectStatus::Published->value);
    $handler->insert($comment);
    redirect_header('comments.php', 2, _AM_XMFBLOG_COMMENT_APPROVED);

case 'reject':
    $comment->setVar('status', ObjectStatus::Rejected->value);
    $handler->insert($comment);
    redirect_header('comments.php', 2, _AM_XMFBLOG_COMMENT_REJECTED);
```

### Permissions Admin (admin/permissions.php)

Uses `XoopsGroupPermForm` for module-level permissions:

```php
$permForm = new \XoopsGroupPermForm(
    _AM_XMFBLOG_PERM_VIEW_DESC,
    $module->getVar('mid'),
    'xmfblog_read',
    _AM_XMFBLOG_PERM_VIEW,
);
$permForm->addItem(1, _AM_XMFBLOG_PERM_VIEW);
echo $permForm->render();
```

Four permission tabs: View, Submit, Auto-Approve, Administration.

### Admin CSS Pattern

Admin pages load custom CSS via `Utility::addAdminAssets()`:

```php
class Utility
{
    public static function addAdminAssets(): void
    {
        $theme = $GLOBALS['xoTheme'] ?? null;
        if ($theme !== null) {
            $theme->addStylesheet(XOOPS_URL . '/modules/xmfblog/assets/css/admin.css');
        }
    }
}
```

Called after `xoops_cp_header()` in every admin page.

---

## 37. Blocks

Blocks demonstrate `Repository` and `QueryBuilder` in a compact context:

```php
function xmfblog_block_recent_show(array $options): array|false
{
    $limit = (int)($options[0] ?? 5);
    $container = \XmfBlog\BlogModule::boot();

    $repo = $container->get('post_repo');

    $query = QueryBuilder::for('xmfblog_posts')
        ->where('status', ComparisonOperator::Equal, ObjectStatus::Published->value)
        ->where('row_flag', ComparisonOperator::Equal, 'A')
        ->orderBy('date_created', 'DESC')
        ->limit($limit);

    $posts = $repo->findAll($query);

    if (empty($posts)) {
        return false;
    }

    $block = ['posts' => []];
    foreach ($posts as $post) {
        $block['posts'][] = [
            'id'    => $post->id,
            'title' => $post->title,
            'date'  => formatTimestamp($post->dateCreated, 's'),
        ];
    }
    return $block;
}
```

---

## 38. Module Cloning

### The Problem

Creating a new XOOPS module from scratch is time-consuming. Often you want to clone an existing module as a starting point, changing only the directory name and internal references.

### The Solution: Cloner Utility

The blog module includes a cloner (`src/Cloner.php`) accessible from `admin/clone.php`:

```php
class Cloner
{
    public static function cloneFileFolder(
        string $srcPath,
        string $dstPath,
        string $moduleDirname,
        string $newDirname,
    ): bool {
        // Recursively copy directory tree
        // In text files (PHP, TPL, SQL, CSS, JS, HTML), replace dirname in 3 variants:
        //   lowercase: xmfblog → newmodule
        //   UPPERCASE: XMFBLOG → NEWMODULE
        //   Ucfirst:   XmfBlog → Newmodule (for class names)
        // Binary files are copied without replacement
    }
}
```

The admin page validates the new module name (alphanumeric + underscores/hyphens), checks the target directory doesn't exist, performs the clone, and optionally generates a logo using GD.

---

## 39. Block Management

### The Problem

Managing blocks (visibility, position, cache, group permissions) typically requires navigating to XOOPS core admin. Module developers want to offer block management within their own admin panel.

### The Solution: Blocksadmin Class

The blog module includes a `Blocksadmin` class (`src/Blocksadmin.php`) that provides:

- **Block listing** with side/position radios, visibility toggles, group checkboxes, cache time
- **Block editing** with `XoopsThemeForm`
- **Block cloning** (duplicate a block with new settings)
- **Block reordering** (bulk weight update)

Accessible from `admin/blocksadmin.php`, it routes operations: `list`, `edit`, `edit_ok`, `delete`, `clone`, `clone_ok`, `order`.

The key pattern is querying `newblocks` and `group_permission` tables directly:

```php
$sql = "SELECT * FROM `{$db->prefix('newblocks')}` WHERE `mid` = {$mid} ORDER BY `visible` DESC, `side`, `weight`";
```

---

## 40. Testdata Management

### The Problem

During development and testing, you need sample data. Manually entering posts, categories, and comments through the admin UI is tedious and not reproducible.

### The Solution: YAML-Based Testdata

The blog module uses YAML files in `testdata/english/` for sample data:

```
testdata/english/
├── xmfblog_posts.yml          # 5 sample posts with HTML body content
├── xmfblog_categories.yml     # 4 hierarchical categories
└── xmfblog_comments.yml       # 8 threaded comments
```

The `TestdataButtons` class adds Import/Export/Clear buttons to the admin dashboard. These buttons are controlled by the `displaySampleButton` module config option — set to "No" once you have real data.

#### YAML Format

```yaml
- post_id: 1
  title: "Getting Started with XMF"
  body: "<p>Welcome to the XMF Blog...</p>"
  status: 1
  category_id: 1
  date_created: 1709337600
```

**Important YAML rules:**
- Body fields with HTML must stay on a single line
- Encode curly braces as `&#123;`/`&#125;` inside `<pre>` blocks
- Only include columns that exist in the actual SQL schema
- Use `Xmf\Yaml::readWrapped()` to parse

---

## 41. Testing Your Module

XMF modules are designed for testability. The blog module has a full integration test that runs without a XOOPS installation.

### Test Setup

```php
final class BlogModuleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        // BlogModule::boot() resets the container and registers all services
        // The test stubs provide in-memory implementations of XoopsDatabase,
        // XoopsPersistableObjectHandler, etc.
        $this->container = BlogModule::boot();
    }
}
```

### What to Test

**1. Container wiring** — verify all services are registered:

```php
public function testContainerBootstrap(): void
{
    $this->assertTrue($this->container->has('post_handler'));
    $this->assertTrue($this->container->has('post_repo'));

    // Singletons return same instance
    $bus1 = $this->container->get('event_bus');
    $bus2 = $this->container->get('event_bus');
    $this->assertSame($bus1, $bus2);
}
```

**2. Entity traits** — verify behaviors compose correctly:

```php
public function testPostEntityTraits(): void
{
    $post = new Post();
    $post->title = 'Test';  // Property hook → setVar → ChangeTrackingTrait

    // ChangeTrackingTrait fires through property hooks
    $this->assertTrue($post->isDirty());
    $this->assertContains('title', $post->getChangedFields());

    // JsonFieldsTrait
    $post->setJsonField('tags_json', ['php', 'xmf']);
    $this->assertSame(['php', 'xmf'], $post->getJsonField('tags_json'));

    // AuditTrailTrait
    $this->assertTrue($post->isActive());
    $post->markDefunct();
    $this->assertFalse($post->isActive());
}
```

**3. Repository CRUD** — verify the full lifecycle:

```php
public function testRepositoryCrud(): void
{
    $repo = $this->container->get('post_repo');

    $post = new Post();
    $post->title = 'Test Post';
    $repo->save($post);

    $this->assertSame(1, $repo->count());
    $this->assertTrue($repo->exists(1));

    $found = $repo->find(1);
    $this->assertNotNull($found);

    $repo->delete(1);
    $this->assertFalse($repo->exists(1));
}
```

**4. Events** — verify dispatch and listener ordering:

```php
public function testEventSystem(): void
{
    $provider = $this->container->get('events');
    $bus = $this->container->get('event_bus');

    $log = [];
    $provider->addListener(PostEvent::class, function (PostEvent $e) use (&$log) {
        $log[] = $e->action;
    });

    $bus->dispatch(new PostEvent('created', 1));
    $this->assertContains('created', $log);
}
```

### Running Tests

```bash
composer dump-autoload --ignore-platform-reqs
php vendor/bin/phpunit tests/unit/Examples/BlogModuleTest.php

# Or run the full suite
composer test
```

The blog module's test suite: **29 tests, 149 assertions**, covering all XMF components.

### Architecture Testing with PHPArkitect

Beyond unit tests, XMF enforces architectural rules using [PHPArkitect](https://github.com/phparkitect/arkitect). These rules run as part of `composer ci` and catch violations of your module's layered architecture.

#### What's Enforced

**1. Layer dependencies** — entities can't depend on handlers, events can't depend on API controllers:

```
Entity  →  nothing (only Xmf framework)
Handler →  Entity only
Event   →  nothing
Api     →  Entity + Handler
Module  →  everything (bootstrap wiring)
```

**2. Naming conventions** — handlers end with `Handler`, controllers end with `Controller`.

**3. Entity rules** — all XoopsObject subclasses must use `#[Field]` attributes for field metadata.

#### Configuration

The module's `phparkitect.php` uses the Architecture builder API:

```php
$layerRules = Architecture::withComponents()
    ->component('Entity')->definedBy('XmfBlog\Post', 'XmfBlog\Category')
    ->component('Repository')->definedBy('XmfBlog\PostRepository', 'XmfBlog\CategoryRepository')
    ->component('Event')->definedBy('XmfBlog\PostEvent', 'XmfBlog\PostPublishedEvent', 'XmfBlog\PostDeletedEvent')
    ->component('Api')->definedBy('XmfBlog\PostApiController')
    ->where('Entity')->shouldNotDependOnAnyComponent()
    ->where('Repository')->mayDependOnComponents('Entity')
    ->where('Event')->shouldNotDependOnAnyComponent()
    ->where('Api')->mayDependOnComponents('Entity', 'Repository')
    ->rules();
```

#### Running Architecture Checks

```bash
# Run all architecture rules (XMF library + modules)
composer arkitect

# Run module rules only
vendor/bin/phparkitect check --config=modules/xmfblog/phparkitect.php
```

If you add a new entity that imports from a handler class, PHPArkitect will catch the violation immediately.

---

## 42. Component Reference Table

Every XMF component used in the blog module, organized by where it appears:

| Component | Class | Used In | Purpose |
|-----------|-------|---------|---------|
| **Container** | `Xmf\Container\Container` | BlogModule, header.php | Dependency injection |
| **ListenerProvider** | `Xmf\Event\ListenerProvider` | BlogModule | Event listener registry |
| **EventBus** | `Xmf\Event\EventBus` | BlogModule, index.php | Event dispatching |
| **DotEvent** | `Xmf\Event\DotEvent` | index.php | String-named events |
| **EventInterface** | `Xmf\Event\EventInterface` | PostEvent | Event contract |
| **ConfigManager** | `Xmf\Config\ConfigManager` | BlogModule | Typed config |
| **ConfigSchema** | `Xmf\Config\ConfigSchema` | BlogModule | Config key definitions |
| **CacheManager** | `Xmf\Cache\CacheManager` | BlogModule | Multi-backend cache |
| **CacheBackendInterface** | `Xmf\Cache\CacheBackendInterface` | InMemoryCache | Cache backend contract |
| **Repository** | `Xmf\Repository\Repository` | BlogModule, index.php, blocks | Data access layer |
| **EntityRepository** | `Xmf\Repository\EntityRepository` | PostRepository, CategoryRepository | DDD-path persistence |
| **HandlerTrait** | `Xmf\Repository\HandlerTrait` | CommentHandler | Lifecycle hooks (classic path) |
| **TreeHandlerTrait** | `Xmf\Repository\TreeHandlerTrait` | (classic-path handlers) | Tree operations |
| **TemporalQueryTrait** | `Xmf\Repository\TemporalQueryTrait` | (classic-path handlers) | Publish window queries |
| **QueryBuilder** | `Xmf\Query\QueryBuilder` | index.php, category.php, blocks | Fluent SQL building |
| **TypedCriteriaItem** | `Xmf\Query\TypedCriteriaItem` | QueryBuilder internals | Bind-safe criteria |
| **Pipeline** | `Xmf\Http\Pipeline` | BlogModule | Middleware chain |
| **MiddlewareInterface** | `Xmf\Http\MiddlewareInterface` | AuthMiddleware | Middleware contract |
| **ApiController** | `Xmf\Api\ApiController` | PostApiController | REST CRUD scaffold |
| **ApiResponse** | `Xmf\Api\ApiResponse` | api.php | Structured JSON response |
| **ItemPermission** | `Xmf\Permissions\ItemPermission` | BlogModule | Group permissions |
| **SmartForm** | `Xmf\Presentation\SmartForm` | admin/post.php, admin/category.php | Auto-generated forms |
| **ObjectTable** | `Xmf\Presentation\ObjectTable` | admin/post.php, admin/category.php | Auto-generated tables |
| **TableStateManager** | `Xmf\Presentation\TableStateManager` | admin/post.php, index.php | Sort/page/filter state |
| **FieldDefinitionReader** | `Xmf\Reflection\FieldDefinitionReader` | admin/post.php | Field introspection |
| **MetaGenerator** | `Xmf\Seo\MetaGenerator` | index.php | SEO meta tags |
| **ServiceManager** | `Xmf\Service\ServiceManager` | BlogModule | Service registry |
| **Migration** | `Xmf\Database\Migration` | BlogMigration | Schema versioning |
| **MigrationRunner** | `Xmf\Database\MigrationRunner` | Tests | Migration executor |
| **PreloadEventBridge** | `Xmf\Legacy\Event\PreloadEventBridge` | BlogModule | Legacy event compat |
| **ChangeTrackingTrait** | `Xmf\Object\ChangeTrackingTrait` | Post, Category, Comment | Dirty-field detection |
| **XmfObjectTrait** | `Xmf\Object\XmfObjectTrait` | Post | Typed getVar |
| **AccessorsTrait** | `Xmf\Object\AccessorsTrait` | Post | Formatted accessors |
| **AuditTrailTrait** | `Xmf\Object\AuditTrailTrait` | Post | Soft-delete |
| **CommonFieldsTrait** | `Xmf\Object\CommonFieldsTrait` | Post | Standard fields |
| **SeoFieldsTrait** | `Xmf\Object\SeoFieldsTrait` | Post | SEO fields |
| **JsonFieldsTrait** | `Xmf\Object\JsonFieldsTrait` | Post | JSON field storage |
| **MultiLingualTrait** | `Xmf\Object\MultiLingualTrait` | Post | Translation support |
| **TreeTrait** | `Xmf\Object\TreeTrait` | Category, Comment | Hierarchical data |
| **ObjectStatus** | `Xmf\Enum\ObjectStatus` | Post, admin, index.php | Lifecycle states |
| **RowStatus** | `Xmf\Enum\RowStatus` | Post | Audit trail flags |
| **ComparisonOperator** | `Xmf\Enum\ComparisonOperator` | index.php, blocks | SQL operators |
| **SortOrder** | `Xmf\Enum\SortOrder` | admin/index.php | Sort direction |
| **QueryCondition** | `Xmf\Enum\QueryCondition` | QueryBuilder usage | Boolean operators |
| **DataType** | `Xmf\Enum\DataType` | XmfObjectTrait | Field type mapping |
| **Email** | `Xmf\ValueObject\Email` | Tests | Validated email |
| **Money** | `Xmf\ValueObject\Money` | Tests | Currency arithmetic |
| **Status** | `Xmf\ValueObject\Status` | Tests | Enum wrapper |
| **XmfJsonHelper** | `Xmf\Utilities\XmfJsonHelper` | Tests | JSON utilities |
| **VersionManager** | `Xmf\Versioning\VersionManager` | PostRepository, admin/post.php, admin/versions.php | Content versioning |
| **VersionableTrait** | `Xmf\Versioning\VersionableTrait` | Post | Versionable entity fields |
| **VersionAwareInterface** | `Xmf\Versioning\VersionAwareInterface` | Post | Versioning contract |
| **VersionSnapshot** | `Xmf\Versioning\VersionSnapshot` | admin/versions.php | Immutable version data |
| **VersionDiff** | `Xmf\Versioning\VersionDiff` | admin/versions.php | Two-version comparison |
| **ModuleAdmin** | `Xmf\Module\Admin` | admin/index.php, all admin pages | Admin UI helper |

---

## 43. Migration Path from Legacy Code

You don't need to rewrite your module overnight. Here's a recommended incremental path:

### Phase 1: Add the Container (1 hour)

Create a bootstrap class like `BlogModule::boot()`. Register your existing handlers as singletons. Change nothing else — your existing code keeps working, but now you have a central wiring point.

### Phase 2: Add ChangeTrackingTrait to Entities (30 minutes per entity)

Add `use ChangeTrackingTrait;` to your `XoopsObject` subclass. No other changes needed — the trait intercepts `setVar()` automatically.

### Phase 3: Add HandlerTrait (15 minutes per handler)

Add `use HandlerTrait;` and move any pre/post-save logic into `beforeSave()`/`afterSave()`.

### Phase 4: Replace CriteriaCompo with QueryBuilder (per-query)

Use `QueryBuilder::fromCriteria()` as a bridge. Then gradually rewrite queries to use the fluent API.

### Phase 5: Add Events (as needed)

Define domain events for important actions. Register listeners in the bootstrap. Use `PreloadEventBridge` for any existing preload classes.

### Phase 6: Add REST API (optional)

Extend `ApiController`, implement `hydrate()` and `fill()`, wire through the `Pipeline` with middleware.

Each phase is independent and non-breaking. You can ship after any phase and continue later.

---

## File Structure Summary

```
modules/xmfblog/
├── xoops_version.php              # Module manifest (8 tables, config, blocks, templates)
├── autoload.php                   # PSR-4 autoloader for src/ namespace
├── index.php                      # Frontend: listing, view, search, myposts, RSS, comments
├── category.php                   # Frontend: category view
├── submit.php                     # Frontend: post submission with media upload
├── phparkitect.php                # Module-level architecture rules
├── admin/
│   ├── menu.php                   # Admin navigation (11 entries)
│   ├── index.php                  # Admin: dashboard (ReportBuilder + NotificationManager)
│   ├── post.php                   # Admin: post CRUD, versioning, media upload, bulk ops
│   ├── category.php               # Admin: category CRUD
│   ├── comments.php               # Admin: comment moderation
│   ├── permissions.php            # Admin: group permissions (4 tabs)
│   ├── versions.php               # Admin: version comparison and restore
│   ├── reports.php                # Admin: declarative reports with CSV export
│   ├── importexport.php           # Admin: CSV/JSON import and export
│   ├── audit.php                  # Admin: audit log viewer with filters
│   ├── blocksadmin.php            # Admin: block management
│   ├── clone.php                  # Admin: module cloning
│   └── about.php                  # Admin: module info
├── blocks/
│   └── blog_blocks.php            # Recent posts + categories blocks
├── plugins/                       # Plugin directory (auto-discovered by PluginManager)
│   ├── MarkdownFormatterPlugin.php  # Converts Markdown to HTML in post body
│   └── SpamFilterPlugin.php         # Spam filter via #[EventListener] attribute
├── src/                           # PSR-4 namespace: XmfBlog\
│   ├── Post.php                   # Entity — DDD path (plain PHP + #[Column])
│   ├── Category.php               # Entity — DDD path (plain PHP + #[Column])
│   ├── Comment.php                # Entity — classic path (XoopsObject)
│   ├── PostRepository.php         # EntityRepository (lifecycle hooks + audit + versioning)
│   ├── CategoryRepository.php     # EntityRepository (lifecycle hooks + audit)
│   ├── CommentHandler.php         # Handler — classic path (lifecycle hooks)
│   ├── PostPublishedEvent.php     # Domain event: post published
│   ├── PostDeletedEvent.php       # Domain event: post deleted
│   ├── CommentCreatedEvent.php    # Domain event: comment created
│   ├── SendCommentNotificationJob.php  # Queue job: notify post author
│   ├── PruneOldVersionsTask.php   # Scheduler: prune old versions (daily)
│   ├── ExpirePostsTask.php        # Scheduler: expire posts (hourly)
│   ├── PruneAuditLogTask.php      # Scheduler: prune audit log (weekly)
│   ├── BlogMigration.php          # Migration: creates all tables
│   ├── BlogModule.php             # Container bootstrap (30+ services)
│   ├── TestdataButtons.php        # Sample data import/export/clear
│   ├── Utility.php                # Admin CSS/JS asset loader
│   ├── Cloner.php                 # Module cloning utility
│   └── Blocksadmin.php            # Block management class
├── sql/
│   └── mysql.sql                  # Install schema (3 module + 5 XMF tables)
├── templates/
│   ├── xmfblog_index.tpl         # Post listing with media thumbnails
│   ├── xmfblog_view.tpl          # Single post + featured image + formatted body
│   ├── xmfblog_category.tpl      # Category view
│   ├── xmfblog_submit.tpl        # Post submission with image upload
│   ├── xmfblog_rss.tpl           # RSS 2.0 feed
│   ├── admin/
│   │   ├── xmfblog_admin_posts.tpl
│   │   ├── xmfblog_admin_categories.tpl
│   │   ├── xmfblog_admin_reports.tpl
│   │   ├── xmfblog_admin_importexport.tpl
│   │   └── xmfblog_admin_audit.tpl
│   └── blocks/
│       ├── xmfblog_block_recent.tpl
│       └── xmfblog_block_categories.tpl
├── testdata/english/              # YAML sample data
│   ├── xmfblog_posts.yml
│   ├── xmfblog_categories.yml
│   └── xmfblog_comments.yml
├── language/english/
│   ├── main.php                   # Frontend strings (~55 constants)
│   ├── admin.php                  # Admin strings (~170 constants)
│   ├── modinfo.php                # Module info + config strings (~45 constants)
│   ├── common.php                 # Shared strings (testdata, blocks admin)
│   └── blocksadmin.php            # Block admin guard-wrapped constants
├── assets/
│   ├── css/admin.css              # Admin styling (diff table, badges, buttons)
│   └── images/logoModule.png      # Module logo
└── docs/
    ├── TUTORIAL.md                # This file (43 sections)
    ├── QUICKSTART.md              # Quick start guide
    ├── ADVANCED_PATTERNS.md       # Advanced usage patterns
    ├── MIGRATION_COOKBOOK.md       # Legacy migration cookbook
    └── README.md                  # Module overview
```

---

## 44. Taxonomy System

The Taxonomy system (`Xmf\Taxonomy`) provides WordPress-inspired content classification with vocabularies, terms, and many-to-many entity relationships. It replaces ad-hoc JSON tag columns with a proper relational model.

### Architecture

```
Module Entity                  TaxonomyManager              Database
     │                              │                          │
     │  implements                  │                          │
     │  TaggableInterface           │                          │
     │         │                    │                          │
     ▼         ▼                    ▼                          │
 PostRepository ─► assignTerms() ──► xmf_taxonomies              │
   afterSave()     removeTerms()     xmf_terms                   │
   afterDelete()                     xmf_term_items              │
                     │                                         │
                     ├─ dispatch TermAssigned ──► TagModuleBridge
                     └─ dispatch TermUnassigned ─► (syncs to XOOPS Tag module)
```

Three database tables:
- **`xmf_taxonomies`** — Vocabulary definitions (e.g., "tags", "categories", "topics")
- **`xmf_terms`** — Terms within vocabularies (e.g., "PHP", "Tutorial", "News")
- **`xmf_term_items`** — Many-to-many links between terms and entities

### Making an Entity Taggable

Implement `TaggableInterface` on your entity:

```php
use Xmf\Taxonomy\TaggableInterface;

class Post extends \XoopsObject implements TaggableInterface
{
    /** @var list<string> */
    public array $pendingTags = [];  // Transient — not persisted directly

    public function getTaggableEntityType(): string
    {
        return 'xmfblog_post';
    }

    public function getTaggableEntityId(): int
    {
        return (int) $this->getVar('post_id');
    }
}
```

The `$pendingTags` property carries tag names from form submission to the handler — it's never stored in the database.

### Tag Sync in Handler

The handler syncs tags after successful persistence:

```php
use Xmf\Taxonomy\TaggableInterface;
use Xmf\Taxonomy\TaxonomyManager;

protected function afterSave(object $entity, bool $isNew = false): void
{
    if ($entity instanceof TaggableInterface) {
        $container = \Xmf\Container\Container::getInstance();
        if ($container->has('taxonomy_manager')) {
            /** @var TaxonomyManager $tm */
            $tm = $container->get('taxonomy_manager');
            $tagNames = $entity->pendingTags ?? [];
            if ($tagNames !== []) {
                $tm->assignTerms($entity, 'tags', $tagNames, 'xmfblog');
            }
        }
    }
}

protected function afterDelete(object $entity): void
{
    if ($entity instanceof TaggableInterface) {
        $container = \Xmf\Container\Container::getInstance();
        if ($container->has('taxonomy_manager')) {
            $tm = $container->get('taxonomy_manager');
            $tm->removeAllTerms($entity);
        }
    }
}
```

`assignTerms()` does **full replacement** — it deletes existing assignments for the entity+vocabulary and inserts new ones. This matches the form submission pattern where the user provides the complete tag list.

### Container Registration

```php
use Xmf\Taxonomy\TaxonomyManager;
use Xmf\Taxonomy\TagModuleBridge;
use Xmf\Taxonomy\Event\TermAssigned;
use Xmf\Taxonomy\Event\TermUnassigned;

$container->singleton('taxonomy_manager', fn(Container $c) =>
    new TaxonomyManager($c->get('db'), $c->get('event_bus'))
);

$container->singleton('tag_bridge', function (Container $c) {
    $bridge = new TagModuleBridge('xmfblog', 1, 0);
    /** @var \Xmf\Event\ListenerProvider $provider */
    $provider = $c->get('events');
    $provider->addListener(TermAssigned::class, [$bridge, 'onTermAssigned']);
    $provider->addListener(TermUnassigned::class, [$bridge, 'onTermUnassigned']);
    return $bridge;
});
```

The `TagModuleBridge` listens for term assignment events and syncs them to the legacy XOOPS Tag module — if it's installed. If not, events are silently ignored.

### Admin UI — Tag Input

In the post edit form, tags are a comma-separated text field:

```php
// Load current tags
/** @var TaxonomyManager $tm */
$tm = $container->get('taxonomy_manager');
$currentTags = $tm->getTermsForEntity($post, 'tags', 'xmfblog');
$tagString = implode(', ', array_map(fn($t) => $t->name, $currentTags));

echo '<label>' . _AM_XMFBLOG_TAGS . '</label>';
echo '<input type="text" name="tags" value="' . htmlspecialchars($tagString) . '">';
echo '<small>' . _AM_XMFBLOG_TAGS_DESC . '</small>';
```

On save, parse the input and set `pendingTags`:

```php
$rawTags = \Xmf\Request::getString('tags', '');
$tagNames = array_filter(array_map('trim', explode(',', $rawTags)));
$post->pendingTags = $tagNames;

// Ensure the vocabulary exists
$vocab = $tm->getVocabularyByName('tags', 'xmfblog');
if ($vocab === null) {
    $tm->createVocabulary('tags', 'Tags', 'xmfblog');
}
```

### Frontend — Displaying Tags

Tags are loaded per-post and passed to the template:

```php
// Single post view
$postTags = $tm->getTermsForEntity($post, 'tags', 'xmfblog');
$xoopsTpl->assign('post_tags', $postTags);

// Post listing — build a map of post_id => [Term, ...]
$postTagsMap = [];
foreach ($posts as $p) {
    $pid = (int) $p->getVar('post_id');
    $postTagsMap[$pid] = $tm->getTermsForEntity($p, 'tags', 'xmfblog');
}
$xoopsTpl->assign('post_tags', $postTagsMap);
```

In Smarty templates, display as linked badges:

```smarty
<{if $post_tags|@count > 0}>
    <div class="xmfblog-tags">
        <strong><{$smarty.const._MD_XMFBLOG_TAGS}>:</strong>
        <{foreach item=tag from=$post_tags}>
            <a href="index.php?op=tag&amp;slug=<{$tag->slug}>" class="xmfblog-tag-badge">
                <{$tag->name}>
            </a>
        <{/foreach}>
    </div>
<{/if}>
```

### Tag Filter Page

The frontend supports filtering posts by tag via slug:

```php
case 'tag':
    $slug = \Xmf\Request::getString('slug', '');
    $vocab = $tm->getVocabularyByName('tags', 'xmfblog');
    $term = $tm->getTermBySlug($slug, $vocab->id);
    $postIds = $tm->getEntityIdsByTerm($term->id, 'xmfblog_post');

    // Load posts by IDs, paginate, assign to template
    $xoopsTpl->assign('tag_name', $term->name);
```

### TaxonomyManager API Summary

| Method | Purpose |
|--------|---------|
| `createVocabulary(name, label, module, hierarchical)` | Create a vocabulary definition |
| `getVocabularyByName(name, module)` | Look up vocabulary by machine name |
| `createTerm(taxonomyId, name, parentId, slug)` | Create a term in a vocabulary |
| `getTermBySlug(slug, taxonomyId)` | Look up term by URL slug |
| `getTerms(taxonomyId, parentId)` | List terms in a vocabulary |
| `assignTerms(entity, vocabName, termNames, module)` | Full replacement of terms on entity |
| `removeAllTerms(entity)` | Remove all term assignments for entity |
| `getTermsForEntity(entity, vocabName, module)` | Get terms assigned to an entity |
| `getEntityIdsByTerm(termId, itemType, limit, offset)` | Reverse lookup: entities with a term |
| `updateTermCount(termId)` | Recalculate cached count from xmf_term_items |

**Time saved:** ~400 lines of custom tag SQL + sync code → ~20 lines of TaxonomyManager calls.

---

## 45. Two-Path Entity Architecture

XMF supports **two entity paths** within the same module, sharing the same infrastructure (Container, EventBus, Queue, Cache, Audit, etc.):

### Path 1: Classic (XoopsObject + Traits)

Best for: Simple CRUD modules, rapid prototyping, existing module upgrades.

```php
// Entity — extends XoopsObject, enhanced with XMF traits
class Comment extends \XoopsObject
{
    use ChangeTrackingTrait;
    use TreeTrait;

    public function __construct()
    {
        $this->initVar('comment_id', XOBJ_DTYPE_INT, 0);
        $this->initVar('content', XOBJ_DTYPE_TXTAREA, '');
        $this->initVar('parent_id', XOBJ_DTYPE_INT, 0);
    }
}

// Handler — XoopsPersistableObjectHandler with lifecycle hooks
class CommentHandler extends \XoopsPersistableObjectHandler
{
    use HandlerTrait;
}

// Usage
$handler->insert($comment);
```

**Trade-offs:** Minimal boilerplate, backward-compatible, uses familiar `setVar()`/`getVar()` API. But entities are "data bags" without encapsulation.

### Path 2: Modern/DDD (Pure PHP Entities + EntityRepository)

Best for: Complex domains, rich business rules, green-field development.

```php
// Entity — plain PHP class with #[Column] attributes
#[Table('xmfblog_posts')]
class Post implements VersionAwareInterface, DomainEventAwareInterface
{
    use EntityBridge;      // getVar()/setVar() compat for templates
    use DomainEventTrait;  // domain events

    #[Column('post_id', primaryKey: true, autoIncrement: true)]
    public private(set) int $id = 0;

    #[Column('title')]
    public string $title = '';

    #[Column('status')]
    public int $status = 0;
}

// Repository — EntityRepository with lifecycle hooks
class PostRepository extends EntityRepository
{
    protected function beforeSave(object $entity): bool { ... }
    protected function afterSave(object $entity, bool $isNew): void { ... }
}

// Usage — direct property access
$post->title = 'Hello World';
$post->status = ObjectStatus::Published->value;
$repository->save($post);
```

**Trade-offs:** More initial setup (define columns via attributes), but entities can enforce business invariants, are independently testable, and use native PHP properties.

### How xmfblog Uses Both Paths

The blog module demonstrates both paths side by side:

| Entity | Path | Base Class | Repository |
|--------|------|-----------|------------|
| `Post` | Modern (DDD) | Plain PHP + `#[Column]` | `PostRepository extends EntityRepository` |
| `Category` | Modern (DDD) | Plain PHP + `#[Column]` | `CategoryRepository extends EntityRepository` |
| `Comment` | Classic | `XoopsObject` | `CommentHandler extends XoopsPersistableObjectHandler` |

Both paths use the **same** Container, EventBus, AuditLogger, VersionManager, Queue, and all other XMF infrastructure. The difference is only in how the entity stores its data.

### EntityBridge: Backward Compatibility

Pure PHP entities use the `EntityBridge` trait to provide `getVar()`/`setVar()` backward compatibility. This allows Smarty templates and admin pages to work identically regardless of which entity path is used:

```php
// Both work identically in templates:
$post->getVar('title')     // EntityBridge maps to $post->title
$comment->getVar('content') // XoopsObject native getVar()
```

---

## 46. Command Bus (CQRS-lite)

The Command Bus decouples "what to do" (command) from "how to do it" (handler). Middleware enables cross-cutting concerns without polluting business logic.

### Defining Commands

Commands are simple PHP objects that describe an intent:

```php
final readonly class CreatePostCommand
{
    public function __construct(
        public string $title,
        public string $body,
        public int $authorId,
        public int $categoryId = 0,
    ) {}

    /** Optional: called by ValidationMiddleware */
    public function validate(): void
    {
        if (trim($this->title) === '') {
            throw new \InvalidArgumentException('Title is required');
        }
    }
}
```

### Registering Handlers

Use `MapHandlerResolver` to map commands to their handlers:

```php
use Xmf\CommandBus\MapHandlerResolver;
use Xmf\CommandBus\SimpleCommandBus;

$resolver = new MapHandlerResolver([
    CreatePostCommand::class => function (CreatePostCommand $cmd) use ($repo) {
        $post = new Post();
        $post->title = $cmd->title;
        $post->body = $cmd->body;
        $post->uid = $cmd->authorId;
        $post->categoryId = $cmd->categoryId;
        $repo->save($post);
        return $post->id;
    },
]);

$bus = new SimpleCommandBus($resolver);
$postId = $bus->dispatch(new CreatePostCommand('Hello', 'World', authorId: 1));
```

### Adding Middleware

Middleware wraps every command dispatch for cross-cutting concerns:

```php
use Xmf\CommandBus\Middleware\LoggingMiddleware;
use Xmf\CommandBus\Middleware\TransactionMiddleware;
use Xmf\CommandBus\Middleware\ValidationMiddleware;

$bus = new SimpleCommandBus($resolver, [
    new LoggingMiddleware(fn(string $msg) => error_log($msg)),
    new ValidationMiddleware(),
    new TransactionMiddleware($db),
]);

// Every dispatch now: logs timing → validates → wraps in DB transaction
$bus->dispatch(new CreatePostCommand('Hello', 'World', authorId: 1));
```

**Time saved:** ~50 lines of duplicated validation/transaction/logging code per handler → 3 lines of middleware configuration.

---

## 47. Enhanced Value Objects

XMF provides rich value objects for common domain concepts:

### Title

```php
use Xmf\ValueObject\Title;

$title = new Title('Getting Started with XMF');
echo $title;                  // "Getting Started with XMF"
echo $title->truncate(15);    // "Getting Start..."
echo strlen((string) $title); // 25
```

### Content

```php
use Xmf\ValueObject\Content;

$content = new Content('<p>This is a blog post about XMF...</p>', 'html');
echo $content->wordCount();      // 8
echo $content->readingTime();    // 1 (minutes, at 200 wpm)
echo $content->excerpt(50);      // First 50 chars + "..."
```

### Timestamp and DateRange

```php
use Xmf\ValueObject\Timestamp;
use Xmf\ValueObject\DateRange;

$now = Timestamp::now();
$yesterday = new Timestamp(time() - 86400);

$now->isAfter($yesterday);   // true
$now->format('Y-m-d');       // "2026-03-06"

$range = new DateRange($yesterday, $now);
$range->contains($now);      // true
$range->overlaps($otherRange); // bool
```

### Identity Value Objects

```php
use Xmf\ValueObject\Identifier\AutoIncrementId;
use Xmf\ValueObject\Identifier\UuidId;
use Xmf\ValueObject\UserId;

$newId = AutoIncrementId::new();  // value = 0 (not yet persisted)
$existingId = new AutoIncrementId(42);
$existingId->isNew(); // false

$uuid = UuidId::generate(); // delegates to Xmf\Uuid
$userId = new UserId(0);    // anonymous user
```

---

## 48. Pagination

`PaginatedResult` provides a clean pagination abstraction:

```php
use Xmf\Pagination\PaginatedResult;

// From a pre-loaded array
$result = PaginatedResult::fromArray($posts, page: 1, perPage: 10, totalItems: 57);

echo $result->totalPages();    // 6
echo $result->hasNextPage();   // true
echo $result->hasPreviousPage(); // false
echo $result->offset();         // 0

// Lazy loading (query only executes when items are accessed)
$result = PaginatedResult::lazy(
    loader: fn(int $offset, int $limit) => $repo->findAll($query->limit($limit, $offset)),
    page: 2,
    perPage: 10,
    totalItems: 57,
);
```

---

## 49. Multi-Tenancy

For SaaS deployments where multiple organizations share one XOOPS install:

```php
use Xmf\MultiTenancy\TenantContext;
use Xmf\MultiTenancy\TenantId;

// Set tenant for the current request
$context = new TenantContext();
$context->set(new TenantId(42));

// Repositories with TenantAwareTrait auto-filter by tenant
$posts = $repo->findAll(); // WHERE tenant_id = 42

// Temporary tenant switching
$context->runAs(new TenantId(99), function () use ($repo) {
    $posts = $repo->findAll(); // WHERE tenant_id = 99
});
// Back to tenant 42
```

### Command Bus Integration

Commands implementing `TenantScopedCommand` are validated by `TenantMiddleware`:

```php
use Xmf\MultiTenancy\TenantScopedCommand;

final readonly class CreateInvoiceCommand implements TenantScopedCommand
{
    public function __construct(
        public TenantId $tenantId,
        public string $customerName,
    ) {}

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }
}

// TenantMiddleware verifies command's tenant matches current context
$bus = new SimpleCommandBus($resolver, [
    new TenantMiddleware($tenantContext),
]);
```

---

## 50. Wave 8 Enhancements — xmfblog Module Updates

The xmfblog module was updated to take advantage of Wave 8 XMF features: CommandBus, enhanced Value Objects, PaginatedResult, and the two-path entity architecture.

### What Changed in xmfblog

#### Two-Path Entity Architecture (Post as Pure PHP Entity)

xmfblog's `Post` entity uses the **Path 2 (Modern)** approach — a pure PHP class with `#[Column]` attributes and `EntityBridge` trait, managed by `EntityRepository`:

```php
// modules/xmfblog/src/Post.php
use Xmf\Attribute\Column;
use Xmf\Attribute\Table;
use Xmf\Repository\EntityBridge;
use Xmf\Object\DomainEventTrait;

#[Table('xmfblog_posts', primaryKey: 'post_id')]
final class Post implements VersionAwareInterface, DomainEventAwareInterface
{
    use EntityBridge;
    use DomainEventTrait;

    #[Column('post_id', autoIncrement: true)]
    public int $id = 0;

    #[Column('title')]
    public string $title = '';

    #[Column('body')]
    public string $body = '';
    // ...
}
```

**EntityBridge** provides backward-compatible `getVar()`/`setVar()` for XOOPS templates:
```php
$post = new Post();
$post->title = 'Hello';           // Direct property access
$post->getVar('title');            // Returns 'Hello' (backward compat)
$post->setVar('title', 'World');   // Sets via bridge
```

Meanwhile, `Comment` stays on **Path 1 (Classic)** — `XoopsObject` with `initVar()`:
```php
$comment = new \Xmf\Comment\Comment();
$comment->setVar('content', 'Great post!');
```

Both paths coexist in the same module and container.

#### PostRepository (EntityRepository)

`PostRepository` extends `EntityRepository` for lifecycle hooks:

```php
// modules/xmfblog/src/PostRepository.php
class PostRepository extends EntityRepository
{
    protected function beforeSave(object $entity): bool
    {
        $entity->dateUpdated = time();
        if ($entity->isNew()) {
            $entity->dateCreated = time();
        } else {
            $this->autoVersion($entity);
        }
        return true;
    }

    protected function afterSave(object $entity, bool $isNew): void
    {
        // Audit logging, domain events, taxonomy sync
    }
}
```

#### CommandBus for Post Operations

The showcase test demonstrates CommandBus patterns for post creation and publishing:

```php
use Xmf\CommandBus\SimpleCommandBus;
use Xmf\CommandBus\MapHandlerResolver;
use Xmf\CommandBus\Middleware\LoggingMiddleware;
use Xmf\CommandBus\Middleware\ValidationMiddleware;

// Define a command
final readonly class CreatePostCommand
{
    public function __construct(
        public Title $title,
        public Content $body,
        public UserId $authorId,
    ) {}

    public function validate(): void
    {
        if ((string) $this->title === '') {
            throw new \InvalidArgumentException('Title must not be empty.');
        }
    }
}

// Wire the bus
$resolver = new MapHandlerResolver([
    CreatePostCommand::class => function (CreatePostCommand $cmd): Post {
        $post = new Post();
        $post->title = (string) $cmd->title;
        $post->body = (string) $cmd->body;
        $post->uid = $cmd->authorId->value;
        return $post;
    },
]);

// Variadic middleware — NOT an array
$bus = new SimpleCommandBus(
    $resolver,
    new ValidationMiddleware(),
    new LoggingMiddleware(),
);

$post = $bus->dispatch(new CreatePostCommand(
    title: new Title('My Post'),
    body: new Content('Hello World'),
    authorId: new UserId(1),
));
```

Key API notes:
- `SimpleCommandBus` takes variadic `MiddlewareInterface ...$middleware`, not an array
- `ValidationMiddleware` auto-calls `$command->validate()` if the method exists
- `LoggingMiddleware` accepts an optional `callable $logger` (defaults to `error_log()`)

#### Enhanced Value Objects

```php
use Xmf\ValueObject\Title;
use Xmf\ValueObject\Content;
use Xmf\ValueObject\Timestamp;
use Xmf\ValueObject\UserId;
use Xmf\ValueObject\Identifier\AutoIncrementId;
use Xmf\ValueObject\DateRange;

// Title — validated, 1-255 chars, JsonSerializable
$title = new Title('Getting Started');
echo (string) $title;           // "Getting Started"
echo json_encode($title);       // "\"Getting Started\""

// Content — with format tracking and text utilities
$body = new Content('<p>Article body</p>', 'html');
$body->wordCount();             // int
$body->readingTimeMinutes();    // int
$body->excerpt(100);            // "Article body..."

// Timestamp — private constructor, use factories
$ts = Timestamp::fromUnix(1000000);
$now = Timestamp::now();
$ts->isBefore($now);            // true
$ts->format('Y-m-d');           // "1970-01-12"

// DateRange — immutable range of Timestamps
$range = new DateRange($start, $end);
$range->getStart()->toUnix();   // int
$range->getEnd()->toUnix();     // int

// Identity VOs
$newId = AutoIncrementId::new();  // id=0, isNew()=true
$existId = new AutoIncrementId(7);
$userId = new UserId(42);
```

#### PaginatedResult for Post Listings

```php
use Xmf\Pagination\PaginatedResult;

// From pre-loaded array
$paginated = PaginatedResult::fromArray($posts, $totalCount, $page, $perPage);

$paginated->items();            // list<Post> (method, not property!)
$paginated->total;              // int (public readonly)
$paginated->page;               // int
$paginated->perPage;            // int
$paginated->totalPages();       // int (computed)
$paginated->hasNextPage();      // bool
$paginated->hasPreviousPage();  // bool
$paginated->offset();           // int

// Lazy loading — items fetched on first access
$lazy = PaginatedResult::lazy(
    fn() => $repo->findPage($page, $perPage),
    $totalCount,
    $page,
    $perPage,
);

// Transform items
$arrays = $paginated->map(fn(Post $p) => $p->toArray());
```

#### Container Registration

All new services are wired in `BlogModule::boot()`:

```php
// PostRepository (EntityRepository for pure PHP entities)
$container->singleton('post_repo', function (Container $c) {
    return new PostRepository($c->get('db'), Post::class, $c->get('event_bus'));
});

// CategoryRepository
$container->singleton('category_repo', function (Container $c) {
    return new CategoryRepository($c->get('db'), Category::class, $c->get('event_bus'));
});

// Comment Handler (stays XoopsObject-based — Path 1)
$container->singleton('comment_handler', function (Container $c) {
    return new \Xmf\Comment\CommentHandler($c->get('db'));
});
```

### Migrating from Ddd\* to XMF — Quick Reference

If your module currently uses `Ddd\*` packages, here's the namespace mapping:

| Ddd\* | Xmf\* |
|-------|-------|
| `Ddd\Entity\Trait\HasDomainEvents` | `Xmf\Object\DomainEventTrait` |
| `Ddd\Entity\Event\DomainEvent` | `Xmf\Event\EventInterface` |
| `Ddd\Repository\Mapper\MapperInterface` | `Xmf\Repository\EntityMapperInterface` |
| `Ddd\Repository\Pagination\PaginatedResult` | `Xmf\Pagination\PaginatedResult` |
| `Ddd\Repository\CommandBus\*` | `Xmf\CommandBus\*` |

Critical API differences:
1. `PaginatedResult`: use `::fromArray()` static factory, not `new` constructor. `items()` is a method.
2. `SimpleCommandBus`: variadic middleware (`...$middleware`), not array
3. `TransactionMiddleware`: accepts `\XoopsDatabase`, not `XoopsConnection`
4. `LoggingMiddleware`: accepts optional `callable $logger` constructor param

---

## Further Reading

- **XMF Source Code**: `src/` — every component is documented with PHPDoc
- **Demo Module**: `examples/demo-module/` — minimal example running inside PHPUnit
- **Blog Module Tests**: `tests/unit/Examples/BlogModuleTest.php` — 29 tests exercising all components
- **CommandBus Showcase**: `tests/unit/Examples/CommandBusShowcaseTest.php` — 21 tests demonstrating CommandBus + Value Objects + PaginatedResult
- **PHPStan Stubs**: `stubs/` — type definitions for XOOPS core classes
- **XOOPS Core**: [xoops.org](https://xoops.org) — the CMS that XMF extends
