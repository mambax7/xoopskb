# XOOPS Test Generator

## Overview

The Test Generator creates PHPUnit test files for your XOOPS module classes, including unit tests, integration tests, and functional tests with proper mocking and fixtures.

## Installation

```bash
composer require --dev xoops/test-generator
```

## Quick Start

### Generate Tests for a Class

```bash
xoops generate:test MyModule ArticleService
```

Creates `tests/Unit/Service/ArticleServiceTest.php`.

### Generate All Tests for a Module

```bash
xoops generate:tests MyModule --all
```

## Command Reference

### `generate:test`

Creates test file for a specific class.

```bash
xoops generate:test <module> <class> [options]
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--type` | Test type (unit/integration/functional) | unit |
| `--methods` | Specific methods to test | all public |
| `--with-fixtures` | Generate fixture files | false |
| `--with-mocks` | Generate mock classes | true |

### `generate:tests`

Creates tests for multiple classes.

```bash
xoops generate:tests <module> [options]
```

**Options:**

| Option | Description |
|--------|-------------|
| `--all` | Generate for all classes |
| `--entities` | Generate for entities only |
| `--services` | Generate for services only |
| `--handlers` | Generate for handlers only |
| `--coverage` | Target coverage percentage |

## Generated Test Structure

### Unit Test Example

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use XoopsModules\MyModule\Service\ArticleService;
use XoopsModules\MyModule\Repository\ArticleRepositoryInterface;
use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\DTO\CreateArticleDTO;

class ArticleServiceTest extends TestCase
{
    private ArticleService $service;
    private ArticleRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ArticleRepositoryInterface::class);
        $this->service = new ArticleService($this->repository);
    }

    public function testCreateArticle(): void
    {
        // Arrange
        $dto = new CreateArticleDTO(
            title: 'Test Article',
            content: 'Test content'
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Article::class));

        // Act
        $article = $this->service->create($dto);

        // Assert
        $this->assertInstanceOf(Article::class, $article);
        $this->assertSame('Test Article', $article->getTitle());
    }

    public function testFindByIdReturnsArticle(): void
    {
        // Arrange
        $expected = Article::create('Title', 'Content');
        $this->repository
            ->method('findById')
            ->with(1)
            ->willReturn($expected);

        // Act
        $result = $this->service->findById(1);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        // Arrange
        $this->repository
            ->method('findById')
            ->willReturn(null);

        // Act
        $result = $this->service->findById(999);

        // Assert
        $this->assertNull($result);
    }
}
```

### Entity Test Example

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use XoopsModules\MyModule\Entity\Article;
use XoopsModules\MyModule\ValueObject\ArticleId;

class ArticleTest extends TestCase
{
    public function testCreate(): void
    {
        $article = Article::create(
            title: 'Test Title',
            content: 'Test content'
        );

        $this->assertInstanceOf(ArticleId::class, $article->getId());
        $this->assertSame('Test Title', $article->getTitle());
        $this->assertTrue($article->isNew());
    }

    /**
     * @dataProvider invalidTitlesProvider
     */
    public function testCreateWithInvalidTitleThrows(string $title): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Article::create(title: $title, content: 'Content');
    }

    public static function invalidTitlesProvider(): array
    {
        return [
            'empty' => [''],
            'too short' => ['Hi'],
            'too long' => [str_repeat('A', 256)],
        ];
    }

    public function testPublish(): void
    {
        $article = Article::create('Title', 'Content');

        $article->publish();

        $this->assertTrue($article->isPublished());
    }
}
```

### Integration Test Example

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use XoopsModules\MyModule\Repository\XoopsArticleRepository;
use XoopsModules\MyModule\Entity\Article;

class ArticleRepositoryTest extends TestCase
{
    private XoopsArticleRepository $repository;

    protected function setUp(): void
    {
        $db = \XoopsDatabaseFactory::getDatabaseConnection();
        $this->repository = new XoopsArticleRepository($db);

        // Start transaction for isolation
        $db->query('START TRANSACTION');
    }

    protected function tearDown(): void
    {
        // Rollback to clean up
        $GLOBALS['xoopsDB']->query('ROLLBACK');
    }

    public function testSaveAndRetrieve(): void
    {
        $article = Article::create('Integration Test', 'Content');

        $this->repository->save($article);

        $retrieved = $this->repository->findById($article->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($article->getTitle(), $retrieved->getTitle());
    }
}
```

## Fixtures

### Generating Fixtures

```bash
xoops generate:test MyModule ArticleService --with-fixtures
```

Creates `tests/Fixtures/ArticleFixture.php`:

```php
<?php

namespace XoopsModules\MyModule\Tests\Fixtures;

class ArticleFixture
{
    public static function createPublished(): Article
    {
        $article = Article::create('Published Article', 'Content');
        $article->publish();
        return $article;
    }

    public static function createDraft(): Article
    {
        return Article::create('Draft Article', 'Content');
    }

    public static function createCollection(int $count = 5): array
    {
        return array_map(
            fn($i) => Article::create("Article {$i}", "Content {$i}"),
            range(1, $count)
        );
    }
}
```

## Configuration

### phpunit.xml Integration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Infrastructure/Legacy</directory>
        </exclude>
    </coverage>
</phpunit>
```

## Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run specific test
vendor/bin/phpunit --filter testCreateArticle
```

## Related Documentation

- [[../../03-Module-Development/Best-Practices/Testing]] - Testing best practices
- [[Module-Generator]] - Generate module scaffold
- [[VS-Code-Snippets]] - IDE integration
- [[Continuous-Integration]] - CI/CD setup
