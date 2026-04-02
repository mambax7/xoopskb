---
title: Entity Mapping & Database Patterns Guide
description: Attribute-based entity mapping, schema design, and migration patterns in XOOPS 4.0
created: 2026-04-02
updated: 2026-04-02
version: 1.0.0
author: XOOPS Team
category: implementation-guide
parent: ../XOOPS-4.0-Architecture.md
php_version: "8.4+"
tags:
  - entity
  - database
  - mysql
  - attributes
  - migration
  - schema
status: reference
---

# Entity Mapping & Database Patterns Guide

> **Attribute-based entity mapping, schema design, and database migrations for XOOPS 4.0 modules.**

XOOPS 4.0 modules define domain entities as plain PHP classes with `#[Table]` and `#[Column]` attributes. No `XoopsObject` inheritance required. The XMF `EntityRepository` handles persistence, while `Migration` manages schema evolution.

This guide uses **xmfblog** as the primary reference and **xmfportal** for supplementary patterns.

---

## Entity Design

### Attribute-Based Mapping

Entities are plain PHP 8.4 classes. The `#[Table]` attribute names the database table; `#[Column]` attributes map properties to columns.

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

use Xmf\Attribute\Column;
use Xmf\Attribute\Table;
use Xmf\Audit\AuditableInterface;
use Xmf\Enum\ObjectStatus;
use Xmf\Object\DomainEventAwareInterface;
use Xmf\Object\DomainEventTrait;
use Xmf\Repository\EntityBridge;
use Xmf\Taxonomy\TaggableInterface;
use Xmf\Versioning\VersionAwareInterface;

#[Table('xmfblog_posts')]
class Post implements VersionAwareInterface, DomainEventAwareInterface, AuditableInterface, TaggableInterface
{
    use EntityBridge;
    use DomainEventTrait;

    // -- Primary Key --

    #[Column('post_id', primaryKey: true, autoIncrement: true)]
    public private(set) int $id = 0;

    // -- Core Fields --

    #[Column('uid')]
    public int $uid = 0;

    #[Column('title')]
    public string $title = '';

    #[Column('status')]
    public int $status = 0;

    #[Column('category_id')]
    public int $categoryId = 0;

    #[Column('body')]
    public string $body = '';

    #[Column('excerpt')]
    public string $excerpt = '';

    // -- Timestamps --

    #[Column('date_created')]
    public int $dateCreated = 0;

    #[Column('date_updated')]
    public int $dateUpdated = 0;

    #[Column('date_publish')]
    public int $datePublish = 0;

    #[Column('date_expire')]
    public int $dateExpire = 0;

    // -- SEO --

    #[Column('meta_title')]
    public string $metaTitle = '';

    #[Column('meta_description')]
    public string $metaDescription = '';

    #[Column('short_url')]
    public string $shortUrl = '';

    // -- Audit Trail --

    #[Column('row_flag')]
    public string $rowFlag = '';

    #[Column('row_uid')]
    public int $rowUid = 0;

    #[Column('row_dt')]
    public int $rowDt = 0;

    // -- Versioning --

    #[Column('version_id')]
    public int $versionId = 0;

    #[Column('version_parent_id')]
    public int $versionParentId = 0;

    #[Column('version_label')]
    public string $versionLabel = '';

    // -- Computed --

    public function getStatusEnum(): ObjectStatus
    {
        return ObjectStatus::from($this->status);
    }
}
```

### Key Patterns

| Pattern | How | Example |
|---------|-----|---------|
| **Primary key** | `#[Column('col', primaryKey: true, autoIncrement: true)]` | `public private(set) int $id = 0;` |
| **Asymmetric visibility** | PHP 8.4 `public private(set)` | ID is publicly readable, only framework sets it |
| **EntityBridge** | `use EntityBridge;` trait | Provides `getVar()`/`setVar()` for backward-compatible templates |
| **DomainEventTrait** | `use DomainEventTrait;` trait | Records events, flushed by repository after persistence |
| **Default values** | PHP property defaults | Every property has a sensible zero-value default |

### Interface Contracts

Entities opt into framework features by implementing interfaces:

| Interface | Purpose | Required Methods |
|-----------|---------|-----------------|
| `VersionAwareInterface` | Automatic versioning snapshots | `getVersionableFields()`, `getEntityType()`, `getEntityId()` |
| `DomainEventAwareInterface` | Domain event recording | Provided by `DomainEventTrait` |
| `AuditableInterface` | Audit trail logging | `getAuditEntityType()`, `getAuditEntityId()` |
| `TaggableInterface` | Taxonomy/tagging support | `getTaggableEntityType()`, `getTaggableEntityId()` |

```php
// VersionAwareInterface — declare which fields are versioned
public function getVersionableFields(): array
{
    return [
        'title', 'body', 'excerpt', 'category_id', 'status',
        'featured_image', 'media_id', 'comments_enabled', 'weight',
        'title_fr',
        'meta_title', 'meta_description', 'meta_keywords', 'short_url',
    ];
}
```

---

## Schema Design

### Table Conventions

XOOPS 4.0 tables follow consistent naming and column patterns:

```sql
CREATE TABLE `xmfblog_posts` (
    -- Primary key: module-prefixed, auto-increment integer
    `post_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Foreign keys: named after the referenced entity
    `category_id`      INT UNSIGNED NOT NULL DEFAULT 0,
    `uid`              INT UNSIGNED NOT NULL DEFAULT 0,

    -- Content fields
    `title`            VARCHAR(255) NOT NULL DEFAULT '',
    `body`             TEXT,
    `excerpt`          VARCHAR(500) NOT NULL DEFAULT '',

    -- Status: integer backed by ObjectStatus enum
    `status`           TINYINT NOT NULL DEFAULT 0,

    -- Timestamps: Unix timestamps (INT UNSIGNED)
    `date_created`     INT UNSIGNED NOT NULL DEFAULT 0,
    `date_updated`     INT UNSIGNED NOT NULL DEFAULT 0,
    `date_publish`     INT UNSIGNED NOT NULL DEFAULT 0,
    `date_expire`      INT UNSIGNED NOT NULL DEFAULT 0,

    -- SEO fields
    `meta_title`       VARCHAR(255) NOT NULL DEFAULT '',
    `meta_description` TEXT,
    `meta_keywords`    VARCHAR(255) NOT NULL DEFAULT '',
    `short_url`        VARCHAR(255) NOT NULL DEFAULT '',

    -- Audit trail columns (standard across all XMF entities)
    `row_flag`         VARCHAR(10) NOT NULL DEFAULT 'A',
    `row_uid`          INT UNSIGNED NOT NULL DEFAULT 0,
    `row_dt`           INT UNSIGNED NOT NULL DEFAULT 0,

    -- Versioning columns (standard across all versioned entities)
    `version_id`       INT NOT NULL DEFAULT 0,
    `version_parent_id` INT NOT NULL DEFAULT 0,
    `version_label`    VARCHAR(100) NOT NULL DEFAULT '',

    PRIMARY KEY (`post_id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_publish` (`date_publish`, `date_expire`),
    KEY `idx_short_url` (`short_url`),
    KEY `idx_row_flag` (`row_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Standard Column Groups

Every XMF entity table includes these standard column groups:

**Audit trail** (`row_flag`, `row_uid`, `row_dt`):
- `row_flag` — `'A'` = active, `'D'` = soft-deleted. Filter with `WHERE row_flag = 'A'`
- `row_uid` — User ID of last actor
- `row_dt` — Unix timestamp of last action

**Versioning** (`version_id`, `version_parent_id`, `version_label`):
- Managed by `VersionManager` — do not write these directly
- `version_id` tracks the current version number
- `version_parent_id` links version chains

### Index Strategy

```sql
-- Filter indexes: columns used in WHERE clauses
KEY `idx_status` (`status`),
KEY `idx_row_flag` (`row_flag`),

-- Composite index for temporal queries
KEY `idx_publish` (`date_publish`, `date_expire`),

-- Lookup indexes for SEO-friendly URLs
KEY `idx_short_url` (`short_url`),
UNIQUE KEY `idx_slug` (`slug`),

-- Foreign key indexes
KEY `idx_category` (`category_id`),
KEY `idx_parent` (`parent_id`),
```

### XMF Infrastructure Tables

XMF modules share infrastructure tables for cross-module features. These are created once by whichever module installs first:

| Table | Purpose | Created By |
|-------|---------|-----------|
| `xmf_comments` | Multi-module comments | `Comment::createTableSql()` |
| `xmf_versions` | Entity version snapshots | `VersionManager::createTableSql()` |
| `xmf_queue_jobs` | Async job processing | `Queue::createTableSql()` |
| `xmf_media` | Uploaded file metadata | `MediaManager::createTableSql()` |
| `xmf_notifications` | In-app notifications | `InAppChannel::createTableSql()` |
| `xmf_audit_log` | Append-only activity log | `AuditLogger::createTableSql()` |
| `xmf_migrations` | Migration version tracking | Auto-created by `MigrationRunner` |
| `xmf_taxonomies` | Vocabulary definitions | `TaxonomyManager` |
| `xmf_terms` | Terms within vocabularies | `TaxonomyManager` |
| `xmf_term_items` | Term-to-item assignments | `TaxonomyManager` |

---

## Database Migrations

### Migration Class

Extend `Xmf\Database\Migration` to define versioned schema changes:

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

use Xmf\Audit\AuditLogger;
use Xmf\Comment\Comment;
use Xmf\Database\Migration;
use Xmf\Media\MediaManager;
use Xmf\Notification\InAppChannel;
use Xmf\Queue\Queue;
use Xmf\Versioning\VersionManager;

class BlogMigration extends Migration
{
    public function version(): string
    {
        return '1.1.0';
    }

    public function up(\XoopsDatabase $db): void
    {
        // Module tables
        $this->createTable($db, 'xmfblog_posts', [
            'post_id'      => 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
            'category_id'  => 'INT UNSIGNED NOT NULL DEFAULT 0',
            'uid'          => 'INT UNSIGNED NOT NULL DEFAULT 0',
            'title'        => 'VARCHAR(255) NOT NULL DEFAULT \'\'',
            'body'         => 'TEXT',
            'status'       => 'TINYINT NOT NULL DEFAULT 0',
            // ... remaining columns
        ]);
        $this->addIndex($db, 'xmfblog_posts', 'idx_category', ['category_id']);
        $this->addIndex($db, 'xmfblog_posts', 'idx_status', ['status']);
        $this->addIndex($db, 'xmfblog_posts', 'idx_publish', ['date_publish', 'date_expire']);

        // Shared XMF infrastructure tables (safe to call multiple times)
        $db->exec(Comment::createTableSql($db->prefix('')));
        $db->exec(VersionManager::createTableSql($db->prefix('')));
        $db->exec(Queue::createTableSql($db->prefix('')));
        $db->exec(MediaManager::createTableSql($db->prefix('')));
        $db->exec(InAppChannel::createTableSql($db->prefix('')));
        $db->exec(AuditLogger::createTableSql($db->prefix('')));
    }

    public function down(\XoopsDatabase $db): void
    {
        $db->exec('DROP TABLE IF EXISTS ' . $db->prefix('xmfblog_categories'));
        $db->exec('DROP TABLE IF EXISTS ' . $db->prefix('xmfblog_posts'));
    }
}
```

### Migration Runner

`MigrationRunner` tracks applied migrations in `xmf_migrations` and applies only pending ones:

```php
// In BlogModule::boot()
$container->singleton('migration_runner', function (Container $c) {
    return new MigrationRunner($c->get('db'), 'xmfblog');
});

// Run pending migrations during install
$runner = $container->get('migration_runner');
$runner->register(new BlogMigration());
$runner->migrate();
```

---

## Direct Query Patterns

### Optimized Single-Column Updates

For operations that don't need the full entity lifecycle, use direct SQL:

```php
// PostRepository — atomic counter increment without loading the entity
public function incrementViewCount(int $postId): void
{
    $table = $this->prefixedTable();
    $sql = "UPDATE {$table} SET view_count = view_count + 1 WHERE post_id = " . (int) $postId;
    $this->db->exec($sql);
}
```

### Batch Lookup Preserving Order

```php
// PostRepository — find multiple posts by IDs, preserving input order
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

For filtered reads, use the `QueryBuilder`:

```php
use Xmf\Enum\ComparisonOperator;
use Xmf\Enum\ObjectStatus;

$posts = $postRepo->query()
    ->where('status', ComparisonOperator::Equal, ObjectStatus::Published->value)
    ->where('row_flag', ComparisonOperator::Equal, 'A')
    ->orderBy('date_created', 'DESC')
    ->limit(10)
    ->findAll();
```

---

## Supplementary: xmfportal Entity

The xmfportal module demonstrates the same patterns for a page/widget entity:

```php
#[Table('xmfportal_pages')]
class Page implements VersionAwareInterface, DomainEventAwareInterface, AuditableInterface
{
    use EntityBridge;
    use DomainEventTrait;

    #[Column('page_id', primaryKey: true, autoIncrement: true)]
    public private(set) int $id = 0;

    #[Column('page_type')]
    public string $pageType = 'content';

    #[Column('layout_json')]
    public string $layoutJson = '';

    // Computed methods for page-type checks
    public function isContent(): bool  { return $this->pageType === 'content'; }
    public function isLanding(): bool  { return $this->pageType === 'landing'; }
    public function isDashboard(): bool { return $this->pageType === 'dashboard'; }
}
```

---

## Related

- [Repository & Query Patterns](Repository-Query-Patterns-Guide.md)
- [Event-Driven Architecture](Event-Driven-Architecture-Guide.md)
- [Error Handling & Validation](Error-Handling-Validation-Guide.md)
- [XMF Reference Implementations](../Reference-Implementations/XMF/README.md)

---

#entity #database #attributes #migration #schema #xoops-4.0
