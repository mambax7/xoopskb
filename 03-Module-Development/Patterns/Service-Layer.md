# Service Layer Pattern

## Overview

The Service Layer pattern provides a clear boundary between your application's domain logic and its presentation layer. In XOOPS module development, services encapsulate business rules and orchestrate operations across multiple entities.

## Purpose

The Service Layer serves several critical functions:

1. **Encapsulation** - Business logic stays in one place, not scattered across controllers
2. **Reusability** - Same service can be used by web controllers, CLI commands, and APIs
3. **Testability** - Services can be unit tested without HTTP or database dependencies
4. **Transaction Management** - Services coordinate database transactions across operations

## Basic Service Structure

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Service;

use XoopsModules\MyModule\Repository\ArticleRepository;
use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\DTO\CreateArticleDTO;
use XoopsModules\MyModule\Event\ArticleCreatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ArticleService
{
    public function __construct(
        private readonly ArticleRepository $repository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly PermissionChecker $permissions
    ) {}

    public function createArticle(CreateArticleDTO $dto, int $userId): Article
    {
        // Authorization check
        if (!$this->permissions->canCreateArticle($userId)) {
            throw new UnauthorizedException('User cannot create articles');
        }

        // Business rule validation
        $this->validateArticleData($dto);

        // Create entity
        $article = Article::create(
            title: $dto->title,
            content: $dto->content,
            authorId: $userId,
            categoryId: $dto->categoryId
        );

        // Persist
        $this->repository->save($article);

        // Dispatch domain event
        $this->dispatcher->dispatch(new ArticleCreatedEvent($article));

        return $article;
    }

    private function validateArticleData(CreateArticleDTO $dto): void
    {
        if (strlen($dto->title) < 5) {
            throw new ValidationException('Title must be at least 5 characters');
        }

        if (empty($dto->content)) {
            throw new ValidationException('Content cannot be empty');
        }
    }
}
```

## Service Dependencies

Services should depend on abstractions (interfaces), not concrete implementations:

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Service;

interface ArticleServiceInterface
{
    public function createArticle(CreateArticleDTO $dto, int $userId): Article;
    public function updateArticle(int $id, UpdateArticleDTO $dto, int $userId): Article;
    public function deleteArticle(int $id, int $userId): void;
    public function findById(int $id): ?Article;
    public function findPublished(int $limit = 10, int $offset = 0): array;
}
```

## Registering Services

Use dependency injection to wire services:

```php
// In module.json
{
    "services": {
        "article.service": {
            "class": "XoopsModules\\MyModule\\Service\\ArticleService",
            "arguments": [
                "@article.repository",
                "@event.dispatcher",
                "@permission.checker"
            ]
        }
    }
}
```

## Service Patterns

### Query vs Command Services

Separate read operations from write operations:

```php
// Query Service - No side effects
final class ArticleQueryService
{
    public function findById(int $id): ?ArticleDTO { }
    public function findByCategory(int $categoryId): array { }
    public function search(string $query): array { }
}

// Command Service - Has side effects
final class ArticleCommandService
{
    public function create(CreateArticleDTO $dto): int { }
    public function update(int $id, UpdateArticleDTO $dto): void { }
    public function delete(int $id): void { }
    public function publish(int $id): void { }
}
```

### Transaction Handling

Services manage transaction boundaries:

```php
public function transferOwnership(int $articleId, int $newOwnerId): void
{
    $this->transactionManager->begin();

    try {
        $article = $this->repository->findById($articleId);
        $oldOwnerId = $article->getAuthorId();

        $article->setAuthorId($newOwnerId);
        $this->repository->save($article);

        // Update related records
        $this->commentRepository->updateAuthor($articleId, $newOwnerId);

        $this->transactionManager->commit();

        $this->dispatcher->dispatch(
            new OwnershipTransferredEvent($articleId, $oldOwnerId, $newOwnerId)
        );
    } catch (\Exception $e) {
        $this->transactionManager->rollback();
        throw $e;
    }
}
```

## Integration with XOOPS

### Accessing Services from Legacy Code

```php
// In a legacy XOOPS file
$container = \Xmf\Module\Helper::getHelper('mymodule')->getContainer();
$articleService = $container->get('article.service');

$article = $articleService->findById($articleId);
```

### Controller Integration

```php
final class ArticleController
{
    public function __construct(
        private readonly ArticleServiceInterface $service,
        private readonly ViewRenderer $renderer
    ) {}

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $dto = CreateArticleDTO::fromRequest($request);
        $userId = $request->getAttribute('userId');

        try {
            $article = $this->service->createArticle($dto, $userId);
            return $this->renderer->render('article/created', [
                'article' => $article
            ]);
        } catch (ValidationException $e) {
            return $this->renderer->render('article/form', [
                'errors' => $e->getErrors(),
                'dto' => $dto
            ]);
        }
    }
}
```

## Best Practices

1. **Keep Services Focused** - Each service should have a single responsibility
2. **Use DTOs** - Never pass raw arrays or request objects into services
3. **Validate Early** - Perform validation at service boundaries
4. **Dispatch Events** - Let other parts of the system react to changes
5. **Handle Transactions** - Services own transaction boundaries, not repositories
6. **Log Important Actions** - Services should log significant business events

## Related Documentation

- [[Repository-Layer]] - Data access pattern
- [[DTO-Pattern]] - Data Transfer Objects
- [[Domain-Model]] - Domain modeling
- [[../../02-Core-Concepts/Dependency-Injection]] - DI container integration
