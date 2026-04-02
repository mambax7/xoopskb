# XMF Slug - URL-Friendly Identifiers

## Overview

`Xmf\Slug` provides utilities for generating URL-friendly slugs from titles and text. Slugs are essential for SEO-friendly URLs and human-readable identifiers.

## Basic Usage

### Generating Slugs

```php
use Xmf\Slug;

// Basic slug generation
$slug = Slug::generate('Hello World!');
// Result: "hello-world"

$slug = Slug::generate('XOOPS 4.0: The Future of CMS');
// Result: "xoops-4.0-the-future-of-cms"

$slug = Slug::generate('Café & Restaurant Guide');
// Result: "cafe-restaurant-guide"
```

### With Options

```php
// Limit length
$slug = Slug::generate('A Very Long Title That Should Be Truncated', [
    'maxLength' => 30
]);
// Result: "a-very-long-title-that-should"

// Custom separator
$slug = Slug::generate('Hello World', [
    'separator' => '_'
]);
// Result: "hello_world"

// Preserve case
$slug = Slug::generate('iPhone Review', [
    'lowercase' => false
]);
// Result: "iPhone-Review"
```

## In Entities

### Slug Value Object

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\ValueObject;

use Xmf\Slug as SlugGenerator;

final class Slug
{
    private function __construct(
        private readonly string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Slug cannot be empty');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new \InvalidArgumentException('Invalid slug format');
        }
    }

    public static function fromTitle(string $title): self
    {
        return new self(SlugGenerator::generate($title, [
            'maxLength' => 100
        ]));
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
}
```

### In Article Entity

```php
final class Article
{
    public function __construct(
        private ArticleId $id,
        private string $title,
        private Slug $slug,
        private string $content
    ) {}

    public static function create(string $title, string $content): self
    {
        return new self(
            id: ArticleId::generate(),
            title: $title,
            slug: Slug::fromTitle($title),
            content: $content
        );
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getUrl(): string
    {
        return "/articles/{$this->slug}";
    }
}
```

## Unique Slug Generation

### Repository Integration

```php
interface ArticleRepositoryInterface
{
    public function findBySlug(Slug $slug): ?Article;
    public function slugExists(Slug $slug): bool;
}

final class ArticleService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository
    ) {}

    public function createWithUniqueSlug(string $title, string $content): Article
    {
        $baseSlug = Slug::fromTitle($title);
        $slug = $this->makeUnique($baseSlug);

        $article = new Article(
            ArticleId::generate(),
            $title,
            $slug,
            $content
        );

        $this->repository->save($article);
        return $article;
    }

    private function makeUnique(Slug $slug): Slug
    {
        if (!$this->repository->slugExists($slug)) {
            return $slug;
        }

        $counter = 1;
        do {
            $newSlug = Slug::fromString($slug->toString() . '-' . $counter);
            $counter++;
        } while ($this->repository->slugExists($newSlug));

        return $newSlug;
    }
}
```

## Database Schema

```sql
CREATE TABLE `{PREFIX}_mymodule_articles` (
    `id` VARCHAR(26) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `content` MEDIUMTEXT,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## URL Routing

### Route Definition

```php
// config/routes.php
return [
    'article.show' => [
        'path' => '/articles/{slug}',
        'controller' => ArticleController::class,
        'action' => 'show',
        'requirements' => [
            'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'
        ]
    ],
];
```

### Controller

```php
final class ArticleController
{
    public function show(string $slug): Response
    {
        $article = $this->repository->findBySlug(
            Slug::fromString($slug)
        );

        if (!$article) {
            throw new NotFoundException('Article not found');
        }

        return $this->render('article/show', [
            'article' => $article
        ]);
    }
}
```

## Transliteration

### Handling Non-ASCII Characters

```php
// Xmf\Slug handles transliteration automatically

$slug = Slug::generate('Привет мир');
// Result: "privet-mir" (Cyrillic transliterated)

$slug = Slug::generate('日本語タイトル');
// Result: depends on transliteration library

$slug = Slug::generate('Ελληνικά');
// Result: "ellinika" (Greek transliterated)
```

## Best Practices

1. **Unique Slugs** - Enforce uniqueness at database level
2. **Reasonable Length** - Limit to 100 characters
3. **Lowercase Only** - Use lowercase for consistency
4. **Hyphens** - Use hyphens, not underscores
5. **No Special Chars** - Only alphanumeric and hyphens
6. **Preserve Words** - Don't break words when truncating

## Related Documentation

- [[EntityId]] - ULID identifiers
- [[../../../03-Module-Development/Patterns/Domain-Model]] - Entity design
- [[../../../03-Module-Development/Database/Database-Schema]] - Schema design
