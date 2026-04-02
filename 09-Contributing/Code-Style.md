# XOOPS Code Style Guide

## Overview

This document defines the coding standards for XOOPS core and module development. Following these standards ensures consistency and maintainability across the ecosystem.

## PHP Standards

### PSR Compliance

XOOPS follows these PHP-FIG standards:
- **PSR-1**: Basic Coding Standard
- **PSR-4**: Autoloading Standard
- **PSR-12**: Extended Coding Style Guide

### File Structure

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule;

use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\Repository\ArticleRepositoryInterface;

/**
 * Article service class.
 */
final class ArticleService
{
    // Class content
}
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `ArticleService` |
| Interfaces | PascalCase + Interface | `ArticleRepositoryInterface` |
| Methods | camelCase | `findById()` |
| Properties | camelCase | `$articleRepository` |
| Constants | UPPER_SNAKE_CASE | `MAX_ITEMS` |
| Variables | camelCase | `$itemCount` |

### Class Structure

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Service;

use XoopsModules\MyModule\Repository\ArticleRepositoryInterface;
use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\DTO\CreateArticleDTO;
use XoopsModules\MyModule\Exception\ArticleNotFoundException;

/**
 * Manages article operations.
 */
final class ArticleService
{
    // 1. Constants
    private const MAX_TITLE_LENGTH = 255;

    // 2. Properties
    private ArticleRepositoryInterface $repository;

    // 3. Constructor
    public function __construct(ArticleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // 4. Public methods
    public function create(CreateArticleDTO $dto): Article
    {
        $this->validateTitle($dto->title);
        $article = Article::create($dto);
        $this->repository->save($article);

        return $article;
    }

    public function findById(int $id): Article
    {
        $article = $this->repository->findById($id);

        if ($article === null) {
            throw new ArticleNotFoundException($id);
        }

        return $article;
    }

    // 5. Private methods
    private function validateTitle(string $title): void
    {
        if (strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new \InvalidArgumentException('Title too long');
        }
    }
}
```

### Control Structures

```php
// If statements
if ($condition) {
    // code
} elseif ($otherCondition) {
    // code
} else {
    // code
}

// Switch statements
switch ($value) {
    case 'option1':
        doSomething();
        break;

    case 'option2':
        doSomethingElse();
        break;

    default:
        doDefault();
}

// Match expressions (PHP 8+)
$result = match ($status) {
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived',
    default => 'Unknown',
};
```

### Type Declarations

```php
// Method signatures with types
public function findByStatus(string $status, int $limit = 10): array
{
    // Implementation
}

// Property types
private readonly ArticleRepositoryInterface $repository;

// Union types (PHP 8+)
public function find(int|string $id): ?Article
{
    // Implementation
}

// Return types
public function getCount(): int
{
    return count($this->items);
}
```

## Documentation

### PHPDoc Comments

```php
/**
 * Creates a new article.
 *
 * @param CreateArticleDTO $dto Article creation data
 * @param int $authorId The author's user ID
 *
 * @return Article The created article
 *
 * @throws ValidationException If validation fails
 * @throws UnauthorizedException If user cannot create articles
 */
public function create(CreateArticleDTO $dto, int $authorId): Article
{
    // Implementation
}
```

### Inline Comments

```php
// Single-line comment for simple explanations

/*
 * Multi-line comment for more complex
 * explanations that span multiple lines.
 */

// TODO: Implement caching
// FIXME: Handle edge case when title is empty
```

## Database Queries

### Safe Queries

```php
// Use parameter binding
$sql = sprintf(
    "SELECT * FROM %s WHERE id = %d AND status = %s",
    $db->prefix('mymodule_articles'),
    intval($id),
    $db->quoteString($status)
);

// Use Criteria for complex queries
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('status', 'published'));
$criteria->add(new Criteria('author_id', $userId));
```

## Smarty Templates

### Template Standards

```smarty
{* File header comment *}
{* templates/mymodule_article.tpl - Article detail template *}

<article class="mymodule-article" id="article-<{$article.id}>">
    <header class="article-header">
        <h1><{$article.title|escape}></h1>
        <{if $article.subtitle}>
            <p class="subtitle"><{$article.subtitle|escape}></p>
        <{/if}>
    </header>

    <div class="article-content">
        <{$article.content}>
    </div>

    <footer class="article-footer">
        <{include file="db:mymodule_article_meta.tpl"}>
    </footer>
</article>
```

## Tools

### PHP CS Fixer

```json
{
    "@PSR12": true,
    "strict_param": true,
    "array_syntax": {"syntax": "short"},
    "ordered_imports": true,
    "no_unused_imports": true
}
```

### PHPStan

```neon
parameters:
    level: 8
    paths:
        - src
```

## Related Documentation

- [[Pull-Request-Guidelines]] - Contribution process
- [[../03-Module-Development/Best-Practices/Clean-Code]] - Clean code principles
- [[../03-Module-Development/Best-Practices/Code-Organization]] - Project structure
