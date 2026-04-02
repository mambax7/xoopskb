---
title: Repository & Query Patterns Guide
description: EntityRepository lifecycle hooks, query patterns, and read optimization in XOOPS 4.0
created: 2026-04-02
updated: 2026-04-02
version: 1.0.0
author: XOOPS Team
category: implementation-guide
parent: ../XOOPS-4.0-Architecture.md
php_version: "8.4+"
tags:
  - repository
  - query
  - patterns
  - lifecycle
  - data-provider
status: reference
---

# Repository & Query Patterns Guide

> **EntityRepository with lifecycle hooks, query optimization, and data providers for XOOPS 4.0 modules.**

The repository pattern in XOOPS 4.0 centers on `EntityRepository` — a base class that handles persistence, hydration, and lifecycle hooks. Repositories manage both reads and writes through a single interface, with lifecycle hooks providing extension points for audit logging, domain events, versioning, and taxonomy sync.

This guide uses **xmfblog** as the primary reference and **xmfportal** for the DataProvider pattern.

---

## EntityRepository Basics

### Core Operations

`EntityRepository` provides these operations out of the box:

| Method | Purpose |
|--------|---------|
| `save($entity)` | Insert (new) or update (existing) with lifecycle hooks |
| `delete($entity)` | Delete with lifecycle hooks |
| `findById($id)` | Find single entity by primary key |
| `findAll($query)` | Find multiple entities matching a query |
| `query()` | Get a `QueryBuilder` instance |
| `isNew()` | Check if entity has been persisted |
| `hydrateAndSnapshot($row)` | Create entity from database row and snapshot original state |

### Lifecycle Hooks

Three hooks execute around persistence operations:

```
save() flow:    beforeSave() → SQL INSERT/UPDATE → afterSave()
delete() flow:  (no before) → SQL DELETE → afterDelete()
```

| Hook | Signature | Return | Purpose |
|------|-----------|--------|---------|
| `beforeSave(object $entity)` | `bool` | Return `false` to cancel save | Timestamps, validation, auto-versioning |
| `afterSave(object $entity, bool $isNew)` | `void` | Side effects after persistence | Audit, events, taxonomy |
| `afterDelete(object $entity)` | `void` | Cleanup after deletion | Audit, events, taxonomy removal |

---

## xmfblog PostRepository

The blog post repository demonstrates all lifecycle hooks with real-world concerns:

### beforeSave — Timestamps and Versioning

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

use Xmf\Repository\EntityRepository;
use Xmf\Versioning\VersionAwareInterface;
use Xmf\Versioning\VersionManager;

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
            // Auto-version: snapshot current state before overwriting
            $this->autoVersion($entity);
        }

        return true;
    }

    private function autoVersion(object $entity): void
    {
        if (!$entity instanceof VersionAwareInterface) {
            return;
        }

        $container = Container::getInstance();
        if (!$container->has('version_manager')) {
            return;
        }

        /** @var VersionManager $vm */
        $vm = $container->get('version_manager');
        $vm->createVersion($entity, 'Auto-save before update ' . date('Y-m-d H:i:s'));
    }
}
```

### afterSave — Audit, Events, Taxonomy

```php
protected function afterSave(object $entity, bool $isNew): void
{
    /** @var Post $entity */
    $container = Container::getInstance();

    // 1. Audit logging
    if ($container->has('audit_logger') && $entity instanceof AuditableInterface) {
        /** @var AuditLogger $auditLogger */
        $auditLogger = $container->get('audit_logger');
        $action = $isNew ? 'create' : 'update';

        $actorId = isset($GLOBALS['xoopsUser']) ? (int) $GLOBALS['xoopsUser']->getVar('uid') : 0;
        $actorIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $auditLogger->log(
            $actorId,
            $actorIp,
            $entity->getAuditEntityType(),
            $entity->getAuditEntityId(),
            $action,
            [],
            [],
            'xmfblog',
        );
    }

    // 2. Record domain events for status transitions
    if ($entity->status === ObjectStatus::Published->value) {
        $entity->recordEvent(new PostPublishedEvent(
            postId: $entity->id,
            title: $entity->title,
            authorId: $entity->uid,
        ));
    } elseif (!$isNew && $entity->status !== ObjectStatus::Published->value) {
        $entity->recordEvent(new PostUnpublishedEvent(
            postId: $entity->id,
            title: $entity->title,
        ));
    }

    // 3. Sync tags via TaxonomyManager
    if ($entity instanceof TaggableInterface && $container->has('taxonomy_manager')) {
        /** @var TaxonomyManager $tm */
        $tm = $container->get('taxonomy_manager');

        $tagNames = $entity->pendingTags ?? [];
        if ($tagNames !== []) {
            $tm->assignTerms($entity, 'tags', $tagNames, 'xmfblog');
            $this->syncToTagModule($entity, $tagNames);
        }
    }
}
```

### afterDelete — Cleanup

```php
protected function afterDelete(object $entity): void
{
    /** @var Post $entity */
    $container = Container::getInstance();

    // Audit logging
    if ($container->has('audit_logger') && $entity instanceof AuditableInterface) {
        /** @var AuditLogger $auditLogger */
        $auditLogger = $container->get('audit_logger');

        $actorId = isset($GLOBALS['xoopsUser']) ? (int) $GLOBALS['xoopsUser']->getVar('uid') : 0;
        $actorIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $auditLogger->log($actorId, $actorIp, $entity->getAuditEntityType(),
            $entity->getAuditEntityId(), 'delete', [], [], 'xmfblog');
    }

    // Record domain event
    $entity->recordEvent(new PostDeletedEvent(
        postId: $entity->id,
        title: $entity->title,
    ));

    // Remove all taxonomy assignments
    if ($entity instanceof TaggableInterface && $container->has('taxonomy_manager')) {
        /** @var TaxonomyManager $tm */
        $tm = $container->get('taxonomy_manager');
        $tm->removeAllTerms($entity);
    }
}
```

---

## Custom Query Methods

### Optimized Counter Update

When you only need to update a single column, bypass the entity lifecycle to avoid unnecessary overhead:

```php
public function incrementViewCount(int $postId): void
{
    $table = $this->prefixedTable();
    $sql = "UPDATE {$table} SET view_count = view_count + 1 WHERE post_id = " . (int) $postId;
    $this->db->exec($sql);
}
```

### Batch Lookup with Order Preservation

```php
public function findByIds(array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', $ids);
    $table = $this->prefixedTable();
    $sql = "SELECT * FROM `{$table}` WHERE `post_id` IN ({$placeholders})";
    $result = $this->db->query($sql);
    if ($result === false) {
        return [];
    }

    $byId = [];
    while (($row = $this->db->fetchArray($result)) !== false) {
        $post = $this->hydrateAndSnapshot($row);
        $byId[(int) $row['post_id']] = $post;
    }
    $this->db->freeRecordSet($result);

    // Preserve input order
    $ordered = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $ordered[] = $byId[$id];
        }
    }
    return $ordered;
}
```

### QueryBuilder Fluent API

```php
use Xmf\Enum\ComparisonOperator;
use Xmf\Enum\ObjectStatus;

// Find published, active posts ordered by creation date
$posts = $postRepo->query()
    ->where('status', ComparisonOperator::Equal, ObjectStatus::Published->value)
    ->where('row_flag', ComparisonOperator::Equal, 'A')
    ->orderBy('date_created', 'DESC')
    ->limit($rssItems)
    ->findAll();
```

---

## DataProvider Pattern (xmfportal)

For complex read scenarios where multiple data sources feed a single UI component, xmfportal introduces the `DataProviderInterface`:

```php
<?php
declare(strict_types=1);

namespace XmfPortal\DataProvider;

use Xmf\Presentation\Widget\DataProviderInterface;

final class RecentContentProvider implements DataProviderInterface
{
    public function __construct(
        private readonly \XoopsDatabase $db,
    ) {
    }

    public function getData(array $props): array
    {
        $source = (string) ($props['source'] ?? 'recent_pages');
        $limit  = max(1, min(24, (int) ($props['limit'] ?? 6)));

        $items = match ($source) {
            'recent_pages'      => $this->getRecentPages($limit),
            'popular_pages'     => $this->getPopularPages($limit),
            'recent_blog_posts' => $this->getRecentBlogPosts($limit),
            'category'          => $this->getByCategory($categoryId, $limit),
            default             => [],
        };

        return ['items' => $items];
    }

    private function getRecentPages(int $limit): array
    {
        $table = $this->db->prefix('xmfportal_pages');
        $sql   = "SELECT page_id, title, slug, body, featured_image, date_created"
            . " FROM {$table}"
            . " WHERE status = 1 AND row_flag = 'A'"
            . " ORDER BY date_created DESC"
            . " LIMIT {$limit}";

        return $this->fetchPages($sql);
    }

    // Cross-module query with graceful degradation
    private function getRecentBlogPosts(int $limit): array
    {
        if (!$this->tableExists('xmfblog_posts')) {
            return [];
        }

        $table = $this->db->prefix('xmfblog_posts');
        $sql   = "SELECT post_id, title, short_url, body, featured_image, date_created"
            . " FROM {$table}"
            . " WHERE status = 1"
            . " ORDER BY date_created DESC"
            . " LIMIT {$limit}";

        // ... fetch and normalize to common format
    }
}
```

### When to Use DataProviders

| Scenario | Use Repository | Use DataProvider |
|----------|---------------|-----------------|
| Single entity CRUD | Yes | No |
| Filtered entity lists | Yes (QueryBuilder) | No |
| Cross-module content aggregation | No | Yes |
| Widget/presentation data assembly | No | Yes |
| Multiple sources, normalized output | No | Yes |

---

## Container Registration

Repositories are registered as singletons in the module bootstrap:

```php
// BlogModule::boot()
$container->singleton('post_repo', function (Container $c) {
    return new PostRepository(Post::class, $c->get('db'));
});

$container->singleton('category_repo', function (Container $c) {
    return new CategoryRepository(Category::class, $c->get('db'));
});
```

---

## Testing Repositories

Entities are testable without a database since they're plain PHP classes:

```php
<?php
declare(strict_types=1);

namespace XmfBlog\Tests\Unit;

use PHPUnit\Framework\TestCase;
use XmfBlog\Post;
use Xmf\Enum\ObjectStatus;

class PostTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $post = new Post();
        $this->assertSame(0, $post->id);
        $this->assertSame('', $post->title);
        $this->assertSame(0, $post->status);
    }

    public function testGetStatusEnum(): void
    {
        $post = new Post();
        $post->status = ObjectStatus::Published->value;
        $this->assertSame(ObjectStatus::Published, $post->getStatusEnum());
    }

    public function testVersionableFields(): void
    {
        $post = new Post();
        $fields = $post->getVersionableFields();
        $this->assertContains('title', $fields);
        $this->assertContains('body', $fields);
        $this->assertContains('status', $fields);
    }
}
```

---

## Related

- [Entity Mapping & Database Patterns](Entity-Mapping-Database-Patterns-Guide.md)
- [Event-Driven Architecture](Event-Driven-Architecture-Guide.md)
- [Error Handling & Validation](Error-Handling-Validation-Guide.md)
- [Repository Pattern (2.5.x)](../../03-Module-Development/Patterns/Repository-Pattern.md)

---

#repository #query #lifecycle #data-provider #xoops-4.0
