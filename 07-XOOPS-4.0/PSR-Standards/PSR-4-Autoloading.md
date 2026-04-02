---
title: PSR-4 Autoloading in XOOPS 4.0
description: Implementation guide for PSR-4 class autoloading in XOOPS modules
version: 1.0.0
created: 2025-12-01
updated: 2026-01-28
---

# PSR-4 Autoloading

## Overview

PSR-4 describes a specification for autoloading classes from file paths. XOOPS 4.0 fully adopts PSR-4, replacing the legacy class loading mechanisms with a standardized, Composer-compatible approach.

## The PSR-4 Standard

### Key Principles

1. **Fully Qualified Class Name (FQCN)** must have a top-level namespace
2. **Namespace prefixes** map to base directories
3. **Subdirectory names** correspond to sub-namespace names
4. **File names** must match class names with `.php` extension

### Mapping Formula

```
Namespace Prefix     → Base Directory
Xoops\Module\News\   → modules/news/src/

Class: Xoops\Module\News\Controller\ArticleController
File:  modules/news/src/Controller/ArticleController.php
```

## XOOPS Module Structure

### Directory Layout

```
modules/publisher/
├── composer.json          # Module-specific dependencies
├── module.json            # Module manifest
├── xoops_version.php      # Legacy metadata (for compatibility)
├── src/                   # PSR-4 autoloaded source
│   ├── Controller/
│   │   ├── ArticleController.php
│   │   └── CategoryController.php
│   ├── Entity/
│   │   ├── Article.php
│   │   └── Category.php
│   ├── Repository/
│   │   ├── ArticleRepository.php
│   │   └── CategoryRepository.php
│   ├── Service/
│   │   ├── ArticleService.php
│   │   └── SearchService.php
│   └── Helper.php
├── class/                 # Legacy classes (deprecated)
│   └── Handler/           # Legacy handlers for BC
├── templates/
├── language/
└── assets/
```

### Namespace Convention

XOOPS 4.0 uses a standardized namespace pattern:

```
Xoops\Module\{ModuleName}\{Component}\{ClassName}
```

Examples:

- `Xoops\Module\Publisher\Controller\ArticleController`
- `Xoops\Module\Publisher\Entity\Article`
- `Xoops\Module\Publisher\Service\ArticleService`
- `Xoops\Module\Publisher\Repository\ArticleRepository`

## Composer Configuration

### Module composer.json

```json
{
    "name": "xoopsmodules/publisher",
    "description": "XOOPS Publisher Module",
    "type": "xoops-module",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.4",
        "xoops/xoops-core": "^2026.0"
    },
    "autoload": {
        "psr-4": {
            "Xoops\\Module\\Publisher\\": "src/"
        }
    },
    "require-dev": {
        "phpstan/phpstan": "^1.10",
        "phpunit/phpunit": "^10.0"
    }
}
```

### Core XOOPS composer.json

```json
{
    "name": "xoops/xoops-core",
    "description": "XOOPS Content Management System",
    "type": "project",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.4",
        "psr/http-message": "^2.0",
        "psr/container": "^2.0",
        "psr/log": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Xoops\\Core\\": "class/Core/",
            "Xoops\\Kernel\\": "class/Kernel/",
            "Xmf\\": "class/Xmf/"
        }
    }
}
```

## Class Examples

### Controller Class

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xoops\Core\View\ViewRendererInterface;
use Xoops\Module\Publisher\Service\ArticleService;

/**
 * Article Controller
 *
 * @package Xoops\Module\Publisher\Controller
 */
class ArticleController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ViewRendererInterface $view
    ) {}

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $articles = $this->articleService->getPaginated($page);

        return $this->view->render('@modules/publisher/article/list', [
            'articles' => $articles,
            'currentPage' => $page,
        ]);
    }

    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $article = $this->articleService->findById($id);

        if ($article === null) {
            throw new NotFoundException('Article not found');
        }

        return $this->view->render('@modules/publisher/article/view', [
            'article' => $article,
        ]);
    }
}
```

### Entity Class

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Entity;

/**
 * Article Entity
 *
 * @package Xoops\Module\Publisher\Entity
 */
class Article
{
    public function __construct(
        public readonly int $id,
        public string $title,
        public string $content,
        public int $authorId,
        public int $categoryId,
        public bool $published = false,
        public ?\DateTimeImmutable $publishedAt = null,
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function publish(): void
    {
        $this->published = true;
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function unpublish(): void
    {
        $this->published = false;
        $this->publishedAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author_id' => $this->authorId,
            'category_id' => $this->categoryId,
            'published' => $this->published,
            'published_at' => $this->publishedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Repository Class

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Repository;

use Xoops\Core\Database\ConnectionInterface;
use Xoops\Module\Publisher\Entity\Article;

/**
 * Article Repository
 *
 * @package Xoops\Module\Publisher\Repository
 */
class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {}

    public function findById(int $id): ?Article
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
           ->from('publisher_articles')
           ->where('id = :id')
           ->setParameter('id', $id);

        $row = $qb->fetchAssociative();

        return $row ? $this->hydrate($row) : null;
    }

    public function findPublished(int $limit = 10, int $offset = 0): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('*')
           ->from('publisher_articles')
           ->where('published = :published')
           ->setParameter('published', true)
           ->orderBy('published_at', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        $rows = $qb->fetchAllAssociative();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(Article $article): Article
    {
        $data = $article->toArray();
        unset($data['id'], $data['created_at']);

        if ($article->id === 0) {
            $this->connection->insert('publisher_articles', $data);
            $id = (int) $this->connection->lastInsertId();
            return new Article($id, ...array_values($data));
        }

        $this->connection->update('publisher_articles', $data, ['id' => $article->id]);
        return $article;
    }

    private function hydrate(array $row): Article
    {
        return new Article(
            id: (int) $row['id'],
            title: $row['title'],
            content: $row['content'],
            authorId: (int) $row['author_id'],
            categoryId: (int) $row['category_id'],
            published: (bool) $row['published'],
            publishedAt: $row['published_at']
                ? new \DateTimeImmutable($row['published_at'])
                : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: $row['updated_at']
                ? new \DateTimeImmutable($row['updated_at'])
                : null,
        );
    }
}
```

### Service Class

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Service;

use Xoops\Module\Publisher\Entity\Article;
use Xoops\Module\Publisher\Repository\ArticleRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Article Service
 *
 * @package Xoops\Module\Publisher\Service
 */
class ArticleService
{
    private const ARTICLES_PER_PAGE = 20;

    public function __construct(
        private readonly ArticleRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function findById(int $id): ?Article
    {
        return $this->repository->findById($id);
    }

    public function getPaginated(int $page = 1): array
    {
        $offset = ($page - 1) * self::ARTICLES_PER_PAGE;

        return $this->repository->findPublished(
            self::ARTICLES_PER_PAGE,
            $offset
        );
    }

    public function publish(int $articleId): Article
    {
        $article = $this->repository->findById($articleId);

        if ($article === null) {
            throw new ArticleNotFoundException("Article {$articleId} not found");
        }

        $article->publish();
        $article = $this->repository->save($article);

        $this->eventDispatcher->dispatch(
            new ArticlePublishedEvent($article->id, $article->authorId)
        );

        return $article;
    }
}
```

## Migration from Legacy Classes

### Legacy Class Location

```
modules/publisher/class/
├── Article.php           # class PublisherArticle extends XoopsObject
├── ArticleHandler.php    # class PublisherArticleHandler extends XoopsPersistableObjectHandler
└── Category.php
```

### Migration Strategy

#### Step 1: Create Modern Equivalents

Keep legacy classes but create new PSR-4 classes:

```php
// modules/publisher/src/Entity/Article.php (new)
namespace Xoops\Module\Publisher\Entity;

class Article { /* ... */ }
```

#### Step 2: Create Adapter for Legacy Handler

```php
// modules/publisher/src/Repository/LegacyArticleRepository.php
namespace Xoops\Module\Publisher\Repository;

use Xoops\Module\Publisher\Entity\Article;

class LegacyArticleRepository implements ArticleRepositoryInterface
{
    private \PublisherArticleHandler $handler;

    public function __construct()
    {
        $helper = \Xoops\Module\Publisher\Helper::getInstance();
        $this->handler = $helper->getHandler('Article');
    }

    public function findById(int $id): ?Article
    {
        $obj = $this->handler->get($id);

        if ($obj === false) {
            return null;
        }

        return $this->convertToEntity($obj);
    }

    private function convertToEntity(\PublisherArticle $obj): Article
    {
        return new Article(
            id: (int) $obj->getVar('articleid'),
            title: $obj->getVar('title'),
            content: $obj->getVar('body'),
            // ... map other fields
        );
    }
}
```

#### Step 3: Use Interface for Flexibility

```php
// Register based on configuration
$container->set(ArticleRepositoryInterface::class, function($c) {
    $useLegacy = $c->get('config')->get('publisher.use_legacy_handler');

    return $useLegacy
        ? new LegacyArticleRepository()
        : new ArticleRepository($c->get('database'));
});
```

## Autoloader Registration

### Core Bootstrap

```php
// include/common.php
<?php

// Load Composer autoloader
require_once XOOPS_ROOT_PATH . '/vendor/autoload.php';

// The autoloader handles all PSR-4 namespaced classes automatically
// No manual require_once needed for classes following PSR-4
```

### Module Autoloading

XOOPS core registers module namespaces automatically:

```php
// Automatic registration for all modules
// Xoops\Module\{ModuleName}\ → modules/{modulename}/src/

$loader = require XOOPS_ROOT_PATH . '/vendor/autoload.php';

// Register active modules
foreach ($activeModules as $module) {
    $namespace = 'Xoops\\Module\\' . ucfirst($module->dirname) . '\\';
    $path = XOOPS_ROOT_PATH . '/modules/' . $module->dirname . '/src/';

    if (is_dir($path)) {
        $loader->addPsr4($namespace, $path);
    }
}
```

## Best Practices

### 1. One Class Per File

```php
// Correct: ArticleController.php contains only ArticleController
// Wrong: Multiple classes in one file
```

### 2. Match Namespace to Path

```php
// Class: Xoops\Module\Publisher\Controller\Admin\ArticleController
// File:  modules/publisher/src/Controller/Admin/ArticleController.php
```

### 3. Use Strict Types

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Service;
```

### 4. Follow Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Class | PascalCase | `ArticleController` |
| Method | camelCase | `findById()` |
| Property | camelCase | `$articleService` |
| Constant | UPPER_SNAKE | `MAX_ARTICLES` |

## IDE Configuration

### PHPStorm

PHPStorm automatically detects `composer.json` and configures autoloading.

### VS Code

Install PHP Intelephense extension and ensure `composer.json` is present.

### Configuration File

```json
// .vscode/settings.json
{
    "intelephense.environment.includePaths": [
        "vendor",
        "class"
    ]
}
```

## See Also

- [[PSR-Standards-Overview|PSR Standards Overview]]
- [[../Migration-Guides/From-2.5-to-4.0|Migration Guide]]
- [[../Roadmap/4.0-Specification|XOOPS 4.0 Specification]]

## External Resources

- [PSR-4 Specification](https://www.php-fig.org/psr/psr-4/)
- [Composer Autoloading](https://getcomposer.org/doc/01-basic-usage.md#autoloading)

---

#xoops-4.0 #psr-4 #autoloading #namespaces #composer
