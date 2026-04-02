# XMF EntityId - ULID Implementation

## Overview

`Xmf\EntityId` provides ULID (Universally Unique Lexicographically Sortable Identifier) generation for XOOPS entities. ULIDs offer advantages over traditional auto-increment IDs and UUIDs.

## Why ULIDs?

### Comparison

| Feature | Auto-Increment | UUID v4 | ULID |
|---------|---------------|---------|------|
| Uniqueness | Per-table | Global | Global |
| Sortable | Yes | No | Yes (time-based) |
| Size | 4-8 bytes | 16 bytes | 16 bytes |
| String Length | Variable | 36 chars | 26 chars |
| Database Index | Efficient | Inefficient | Efficient |
| Guessable | Yes | No | No |

### ULID Format

```
01ARZ3NDEKTSV4RRFFQ69G5FAV
└─────┬─────┘└──────┬──────┘
  Timestamp       Randomness
  (48 bits)       (80 bits)
```

## Installation

The `Xmf\EntityId` class is included in XMF 2.x:

```bash
composer require xoops/xmf
```

## Basic Usage

### Generating a ULID

```php
use Xmf\EntityId;

// Generate new ULID
$id = EntityId::generate();
echo $id; // "01ARZ3NDEKTSV4RRFFQ69G5FAV"

// Get timestamp from ULID
$timestamp = EntityId::getTimestamp($id);
echo date('Y-m-d H:i:s', $timestamp);
```

### In Value Objects

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\ValueObject;

use Xmf\EntityId;

final class ArticleId
{
    private function __construct(
        private readonly string $value
    ) {
        if (!EntityId::isValid($value)) {
            throw new \InvalidArgumentException('Invalid ArticleId format');
        }
    }

    public static function generate(): self
    {
        return new self(EntityId::generate());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function getTimestamp(): int
    {
        return EntityId::getTimestamp($this->value);
    }
}
```

### In Entities

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Entity;

use XoopsModules\MyModule\ValueObject\ArticleId;

final class Article
{
    public function __construct(
        private ArticleId $id,
        private string $title,
        private string $content,
        private \DateTimeImmutable $createdAt
    ) {}

    public static function create(string $title, string $content): self
    {
        return new self(
            id: ArticleId::generate(),
            title: $title,
            content: $content,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): ArticleId
    {
        return $this->id;
    }

    // ... other methods
}
```

## Database Integration

### Schema Design

```sql
CREATE TABLE `{PREFIX}_mymodule_articles` (
    `id` VARCHAR(26) NOT NULL COMMENT 'ULID identifier',
    `title` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Repository Implementation

```php
<?php

namespace XoopsModules\MyModule\Repository;

use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\ValueObject\ArticleId;

final class XoopsArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly \XoopsDatabase $db
    ) {}

    public function nextIdentity(): ArticleId
    {
        return ArticleId::generate();
    }

    public function findById(ArticleId $id): ?Article
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE id = %s",
            $this->db->prefix('mymodule_articles'),
            $this->db->quoteString($id->toString())
        );

        $result = $this->db->query($sql);
        $row = $this->db->fetchArray($result);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(Article $article): void
    {
        $sql = sprintf(
            "INSERT INTO %s (id, title, created_at) VALUES (%s, %s, %s)
             ON DUPLICATE KEY UPDATE title = VALUES(title)",
            $this->db->prefix('mymodule_articles'),
            $this->db->quoteString($article->getId()->toString()),
            $this->db->quoteString($article->getTitle()),
            $this->db->quoteString($article->getCreatedAt()->format('Y-m-d H:i:s'))
        );

        $this->db->queryF($sql);
    }

    private function hydrate(array $row): Article
    {
        return new Article(
            id: ArticleId::fromString($row['id']),
            title: $row['title'],
            content: $row['content'] ?? '',
            createdAt: new \DateTimeImmutable($row['created_at'])
        );
    }
}
```

## API Reference

### EntityId::generate()

Generates a new ULID string.

```php
public static function generate(): string
```

### EntityId::isValid()

Validates a ULID string format.

```php
public static function isValid(string $ulid): bool
```

### EntityId::getTimestamp()

Extracts Unix timestamp from ULID.

```php
public static function getTimestamp(string $ulid): int
```

### EntityId::fromTimestamp()

Generates ULID with specific timestamp.

```php
public static function fromTimestamp(int $timestamp): string
```

## Sorting and Ordering

ULIDs sort lexicographically by creation time:

```php
$ids = [];
for ($i = 0; $i < 5; $i++) {
    $ids[] = EntityId::generate();
    usleep(1000); // Small delay
}

sort($ids); // Already in chronological order!
```

```sql
-- Articles ordered by creation (using ULID)
SELECT * FROM articles ORDER BY id ASC;

-- Recent articles (no need for created_at index)
SELECT * FROM articles ORDER BY id DESC LIMIT 10;
```

## Migration from Auto-Increment

```php
// Migration script
public function up(\XoopsDatabase $db): void
{
    // 1. Add new ULID column
    $db->queryF("ALTER TABLE articles ADD COLUMN ulid VARCHAR(26)");

    // 2. Generate ULIDs for existing rows
    $result = $db->query("SELECT id, created_at FROM articles");
    while ($row = $db->fetchArray($result)) {
        $ulid = EntityId::fromTimestamp(strtotime($row['created_at']));
        $db->queryF("UPDATE articles SET ulid = '{$ulid}' WHERE id = {$row['id']}");
    }

    // 3. Switch primary key
    $db->queryF("ALTER TABLE articles DROP PRIMARY KEY, ADD PRIMARY KEY (ulid)");
    $db->queryF("ALTER TABLE articles DROP COLUMN id");
    $db->queryF("ALTER TABLE articles CHANGE ulid id VARCHAR(26)");
}
```

## Related Documentation

- [[../../../03-Module-Development/Patterns/Domain-Model|Domain Model]] - Entity design
- [[../../../03-Module-Development/Database/Database-Schema|Database Schema]] - Schema design
- [[Slug]] - URL-friendly identifiers
- [[../../../03-Module-Development/Patterns/Repository-Layer|Repository Pattern]] - Data access
