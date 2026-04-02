# Repository Layer Pattern

## Overview

The Repository pattern mediates between the domain and data mapping layers. Repositories encapsulate the logic required to access data sources, providing a collection-like interface for accessing domain objects.

## Purpose

1. **Abstraction** - Hide data access implementation details from business logic
2. **Testability** - Easy to mock for unit testing
3. **Flexibility** - Switch data sources without changing business logic
4. **Query Encapsulation** - Complex queries stay in one place

## Basic Repository Interface

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Repository;

use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\ValueObject\ArticleId;

interface ArticleRepositoryInterface
{
    public function findById(ArticleId $id): ?Article;
    public function findAll(): array;
    public function save(Article $article): void;
    public function delete(Article $article): void;
    public function nextIdentity(): ArticleId;
}
```

## Implementation with XOOPS

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Repository;

use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\ValueObject\ArticleId;
use XoopsDatabase;

final class XoopsArticleRepository implements ArticleRepositoryInterface
{
    private string $table;

    public function __construct(
        private readonly XoopsDatabase $db
    ) {
        $this->table = $db->prefix('mymodule_articles');
    }

    public function findById(ArticleId $id): ?Article
    {
        $sql = "SELECT * FROM {$this->table} WHERE article_id = ?";
        $result = $this->db->query($sql, [$id->toString()]);

        if ($row = $this->db->fetchArray($result)) {
            return $this->hydrate($row);
        }

        return null;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->db->query($sql);

        $articles = [];
        while ($row = $this->db->fetchArray($result)) {
            $articles[] = $this->hydrate($row);
        }

        return $articles;
    }

    public function save(Article $article): void
    {
        if ($article->isNew()) {
            $this->insert($article);
        } else {
            $this->update($article);
        }
    }

    public function delete(Article $article): void
    {
        $sql = "DELETE FROM {$this->table} WHERE article_id = ?";
        $this->db->query($sql, [$article->getId()->toString()]);
    }

    public function nextIdentity(): ArticleId
    {
        return ArticleId::generate();
    }

    private function hydrate(array $row): Article
    {
        return new Article(
            id: ArticleId::fromString($row['article_id']),
            title: $row['title'],
            content: $row['content'],
            authorId: (int) $row['author_id'],
            categoryId: (int) $row['category_id'],
            status: ArticleStatus::from($row['status']),
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: $row['updated_at']
                ? new \DateTimeImmutable($row['updated_at'])
                : null
        );
    }

    private function insert(Article $article): void
    {
        $sql = "INSERT INTO {$this->table}
                (article_id, title, content, author_id, category_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $this->db->query($sql, [
            $article->getId()->toString(),
            $article->getTitle(),
            $article->getContent(),
            $article->getAuthorId(),
            $article->getCategoryId(),
            $article->getStatus()->value
        ]);
    }

    private function update(Article $article): void
    {
        $sql = "UPDATE {$this->table}
                SET title = ?, content = ?, category_id = ?, status = ?, updated_at = NOW()
                WHERE article_id = ?";

        $this->db->query($sql, [
            $article->getTitle(),
            $article->getContent(),
            $article->getCategoryId(),
            $article->getStatus()->value,
            $article->getId()->toString()
        ]);
    }
}
```

## Query Methods

Add specialized query methods as needed:

```php
interface ArticleRepositoryInterface
{
    // ... basic methods ...

    public function findByCategory(int $categoryId): array;
    public function findPublished(int $limit, int $offset): array;
    public function findByAuthor(int $authorId): array;
    public function countByStatus(ArticleStatus $status): int;
    public function search(string $query): array;
}
```

## Using Criteria

For complex queries, use the Criteria pattern:

```php
public function findByCriteria(Criteria $criteria): array
{
    $sql = "SELECT * FROM {$this->table}";
    $params = [];

    if ($criteria->hasConditions()) {
        $sql .= " WHERE " . $criteria->renderWhere($params);
    }

    if ($criteria->hasOrder()) {
        $sql .= " ORDER BY " . $criteria->renderOrder();
    }

    if ($criteria->hasLimit()) {
        $sql .= " LIMIT " . $criteria->getOffset() . ", " . $criteria->getLimit();
    }

    $result = $this->db->query($sql, $params);
    return $this->hydrateAll($result);
}
```

## Best Practices

1. **One Repository Per Aggregate** - Don't create repositories for every entity
2. **Return Domain Objects** - Never return raw database arrays
3. **No Business Logic** - Repositories only handle persistence
4. **Interface First** - Define interface before implementation
5. **Batch Operations** - Provide methods for bulk operations when needed

## Related Documentation

- [[Service-Layer]] - Business logic layer
- [[Domain-Model]] - Domain entities
- [[../../04-API-Reference/Kernel/Criteria]] - Query building
- [[Unit-of-Work]] - Transaction patterns
