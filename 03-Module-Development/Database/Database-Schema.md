# Database Schema Design

## Overview

Proper database schema design is crucial for XOOPS module performance and maintainability. This guide covers best practices for table design, relationships, indexing, and migrations.

## Table Naming Conventions

### Standard Format

```
{prefix}_{modulename}_{tablename}
```

Examples:
- `xoops_mymodule_articles`
- `xoops_mymodule_categories`
- `xoops_mymodule_article_category` (junction table)

### In Schema Files

Use `{PREFIX}` placeholder:

```sql
CREATE TABLE `{PREFIX}_mymodule_articles` (
    ...
);
```

## Column Types

### Recommended Types

| Data | MySQL Type | PHP Type | Description |
|------|-----------|----------|-------------|
| ID (ULID) | `VARCHAR(26)` | `string` | ULID identifiers |
| ID (Auto) | `INT UNSIGNED AUTO_INCREMENT` | `int` | Sequential IDs |
| Short Text | `VARCHAR(n)` | `string` | Up to 255 chars |
| Long Text | `TEXT` | `string` | Unlimited text |
| Rich Text | `MEDIUMTEXT` | `string` | HTML content |
| Boolean | `TINYINT(1)` | `bool` | True/false |
| Enum | `ENUM(...)` | `string` | Fixed options |
| Date | `DATE` | `DateTimeImmutable` | Date only |
| DateTime | `DATETIME` | `DateTimeImmutable` | Date and time |
| Timestamp | `INT UNSIGNED` | `int` | Unix timestamp |
| Price | `DECIMAL(10,2)` | `float` | Currency values |
| JSON | `JSON` | `array` | Structured data |

### Entity Schema Example

```sql
CREATE TABLE `{PREFIX}_mymodule_articles` (
    `id` VARCHAR(26) NOT NULL COMMENT 'ULID identifier',
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` MEDIUMTEXT,
    `summary` TEXT,
    `status` ENUM('draft', 'pending', 'published', 'archived') DEFAULT 'draft',
    `author_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED,
    `views` INT UNSIGNED DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `published_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_author` (`author_id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_published` (`published_at`),
    KEY `idx_featured` (`is_featured`, `published_at`),

    CONSTRAINT `fk_article_author`
        FOREIGN KEY (`author_id`) REFERENCES `{PREFIX}_users` (`uid`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_article_category`
        FOREIGN KEY (`category_id`) REFERENCES `{PREFIX}_mymodule_categories` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Relationships

### One-to-Many

```sql
-- Categories (one)
CREATE TABLE `{PREFIX}_mymodule_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL
);

-- Articles (many)
CREATE TABLE `{PREFIX}_mymodule_articles` (
    `id` VARCHAR(26) PRIMARY KEY,
    `category_id` INT UNSIGNED,
    FOREIGN KEY (`category_id`) REFERENCES `{PREFIX}_mymodule_categories` (`id`)
);
```

### Many-to-Many

```sql
-- Articles
CREATE TABLE `{PREFIX}_mymodule_articles` (
    `id` VARCHAR(26) PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL
);

-- Tags
CREATE TABLE `{PREFIX}_mymodule_tags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    UNIQUE KEY (`name`)
);

-- Junction table
CREATE TABLE `{PREFIX}_mymodule_article_tags` (
    `article_id` VARCHAR(26) NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`article_id`, `tag_id`),
    FOREIGN KEY (`article_id`) REFERENCES `{PREFIX}_mymodule_articles` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `{PREFIX}_mymodule_tags` (`id`) ON DELETE CASCADE
);
```

### Self-Referencing (Hierarchy)

```sql
CREATE TABLE `{PREFIX}_mymodule_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(1000) COMMENT 'Materialized path: /1/5/12/',
    `depth` TINYINT UNSIGNED DEFAULT 0,

    KEY `idx_parent` (`parent_id`),
    KEY `idx_path` (`path`(255)),

    FOREIGN KEY (`parent_id`) REFERENCES `{PREFIX}_mymodule_categories` (`id`)
        ON DELETE SET NULL
);
```

## Indexing Strategy

### When to Index

| Scenario | Index Type |
|----------|-----------|
| Primary key | PRIMARY |
| Unique constraint | UNIQUE |
| Foreign key | Regular KEY |
| WHERE clause column | Regular KEY |
| ORDER BY column | Regular KEY |
| Full-text search | FULLTEXT |

### Composite Indexes

Order matters - most selective column first:

```sql
-- Good: matches WHERE status = 'published' ORDER BY created_at
KEY `idx_status_created` (`status`, `created_at`)

-- Query optimization
SELECT * FROM articles
WHERE status = 'published'
ORDER BY created_at DESC
```

### Covering Indexes

Include all queried columns to avoid table lookup:

```sql
-- Covers: SELECT title, status FROM articles WHERE author_id = ?
KEY `idx_author_covering` (`author_id`, `title`, `status`)
```

## Migrations

### Migration File Structure

```php
// migrations/001_create_articles.php
<?php

return new class {
    public function up(\XoopsDatabase $db): void
    {
        $prefix = $db->prefix('mymodule_articles');

        $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}` (
            `id` VARCHAR(26) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->queryF($sql);
    }

    public function down(\XoopsDatabase $db): void
    {
        $prefix = $db->prefix('mymodule_articles');
        $db->queryF("DROP TABLE IF EXISTS `{$prefix}`");
    }
};
```

### Adding Columns

```php
// migrations/002_add_status_column.php
public function up(\XoopsDatabase $db): void
{
    $table = $db->prefix('mymodule_articles');
    $db->queryF("ALTER TABLE `{$table}` ADD COLUMN `status` ENUM('draft','published') DEFAULT 'draft' AFTER `title`");
    $db->queryF("CREATE INDEX `idx_status` ON `{$table}` (`status`)");
}
```

## Best Practices

1. **Use InnoDB** - Supports transactions and foreign keys
2. **UTF8MB4** - Full Unicode support including emojis
3. **NOT NULL** - Use defaults instead of nullable columns when possible
4. **Appropriate Types** - Don't use TEXT for short strings
5. **Index Sparingly** - Each index slows writes
6. **Document Schema** - Add COMMENT to columns
7. **Avoid Reserved Words** - Don't use `order`, `group`, `key` as column names

## Related Documentation

- [[../Database-Operations]] - Query execution
- [[../../04-API-Reference/Database/Criteria]] - Query building
- [[Migrations]] - Schema versioning
- [[../../01-Getting-Started/Configuration/Performance-Optimization]] - Query optimization
