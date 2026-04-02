# XOOPS 4.0 Quick Reference Card

A single-page cheat sheet for modern XOOPS module development.

---

## ULID Generation

```php
use Xmf\Ulid;

// Generate new ULID
$ulid = Ulid::generate();                    // 01HV8X5Z0KDMVR8SDPY62J9ACP

// From string
$ulid = Ulid::fromString('01HV8X5Z0KDMVR8SDPY62J9ACP');

// Validation
Ulid::isValid($string);                      // bool

// Get timestamp
$ulid->getTimestamp();                       // DateTimeImmutable

// Comparison
$ulid->equals($other);                       // bool
$ulid->compareTo($other);                    // -1, 0, 1
```

**Database Storage:** `CHAR(26) NOT NULL`

---

## Slug Generation

```php
use Xmf\Slug;

// Basic usage
$slug = Slug::create('Hello World!');        // hello-world

// With options
$slug = Slug::create('My Article', [
    'separator' => '-',                      // default
    'lowercase' => true,                     // default
    'maxLength' => 60,                       // default
    'locale' => 'en',                        // for transliteration
]);

// Add suffix for uniqueness
$slug->withSuffix(2);                        // my-article-2

// Validation
Slug::isValid($string);                      // bool
```

**Database Storage:** `VARCHAR(60) NOT NULL`

---

## Value Object Template

```php
<?php
declare(strict_types=1);

final readonly class ArticleTitle implements \Stringable, \JsonSerializable
{
    private const int MIN_LENGTH = 1;
    private const int MAX_LENGTH = 200;

    private function __construct(private string $value) {}

    public static function create(string $title): self
    {
        $title = trim($title);

        if (mb_strlen($title) < self::MIN_LENGTH) {
            throw InvalidArticleTitle::tooShort(self::MIN_LENGTH);
        }
        if (mb_strlen($title) > self::MAX_LENGTH) {
            throw InvalidArticleTitle::tooLong(self::MAX_LENGTH);
        }

        return new self($title);
    }

    public function toString(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
    public function jsonSerialize(): string { return $this->value; }
}
```

---

## Entity ID with Trait

```php
<?php
declare(strict_types=1);

final readonly class ArticleId
{
    use \Xmf\EntityId;  // Provides generate(), fromString(), equals(), etc.

    protected static function exceptionClass(): string
    {
        return InvalidArticleId::class;
    }
}

// Usage
$id = ArticleId::generate();
$id = ArticleId::fromString('01HV8X5Z0KDMVR8SDPY62J9ACP');
```

---

## Entity Template

```php
<?php
declare(strict_types=1);

final class Article
{
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly ArticleId $id,
        private ArticleTitle $title,
        private ArticleContent $content,
        private ArticleStatus $status,
        private readonly \DateTimeImmutable $createdAt
    ) {
        $this->updatedAt = $createdAt;
    }

    // Factory method
    public static function create(ArticleTitle $title, ArticleContent $content): self
    {
        return new self(
            ArticleId::generate(),
            $title,
            $content,
            ArticleStatus::Draft,
            new \DateTimeImmutable()
        );
    }

    // Reconstitute from persistence
    public static function reconstitute(/* all fields */): self { /* ... */ }

    // Domain behavior
    public function publish(): void
    {
        if (!$this->status->canTransitionTo(ArticleStatus::Published)) {
            throw InvalidStatusTransition::create($this->status, ArticleStatus::Published);
        }
        $this->status = ArticleStatus::Published;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

---

## Repository Interface

```php
<?php
declare(strict_types=1);

interface ArticleRepositoryInterface
{
    public function findById(ArticleId $id): Article;
    public function findByIdOrNull(ArticleId $id): ?Article;
    public function findBySlug(ArticleSlug $slug): ?Article;
    public function save(Article $article): void;
    public function delete(Article $article): void;
    public function exists(ArticleId $id): bool;
}
```

---

## Command/Handler Pattern

```php
// Command (immutable DTO)
final readonly class CreateArticleCommand
{
    public function __construct(
        public string $title,
        public string $content,
        public int $authorId
    ) {}
}

// Handler
final readonly class CreateArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $repository
    ) {}

    public function handle(CreateArticleCommand $cmd): Article
    {
        $article = Article::create(
            ArticleTitle::create($cmd->title),
            ArticleContent::create($cmd->content)
        );
        $this->repository->save($article);
        return $article;
    }
}
```

---

## Domain Exception Pattern

```php
// Base exception
abstract class DomainException extends \DomainException
{
    protected function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }
}

// Specific exception
final class InvalidArticleTitle extends DomainException
{
    public static function tooLong(int $max): self
    {
        return new self(
            "Title cannot exceed {$max} characters",
            'ARTICLE_TITLE_TOO_LONG',
            ['max_length' => $max]
        );
    }
}
```

---

## Directory Structure

```
modules/mymodule/
├── Domain/
│   ├── Entity/           # Aggregate roots, entities
│   ├── ValueObject/      # Immutable value types
│   ├── Repository/       # Repository interfaces
│   ├── Service/          # Domain services
│   └── Exception/        # Domain exceptions
├── Application/
│   ├── Command/          # Commands and handlers
│   ├── Query/            # Queries and handlers
│   └── Service/          # Application services
├── Infrastructure/
│   ├── Persistence/      # Repository implementations
│   ├── Api/              # REST API classes
│   └── Xoops/            # XOOPS integrations
├── Presentation/
│   ├── Controller/       # Web controllers
│   └── templates/        # Smarty templates
├── sql/                  # Database schemas
├── api/v1/               # API entry point
└── xoops_version.php
```

---

## SQL Schema Patterns

```sql
-- Entity table with ULID primary key
CREATE TABLE `mod_article` (
    `id` CHAR(26) NOT NULL,
    `slug` VARCHAR(60) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` MEDIUMTEXT,
    `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `author_id` CHAR(26) NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_author` (`author_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Junction table for many-to-many
CREATE TABLE `mod_article_tag` (
    `article_id` CHAR(26) NOT NULL,
    `tag_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`article_id`, `tag_id`),
    KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB;
```

---

## REST API Response Format

```json
{
  "data": {
    "id": "01HV8X5Z0KDMVR8SDPY62J9ACP",
    "type": "article",
    "attributes": {
      "title": "My Article",
      "slug": "my-article",
      "status": "published",
      "created_at": "2026-01-30T10:30:00+00:00"
    },
    "links": {
      "self": "/api/v1/articles/01HV8X5Z0KDMVR8SDPY62J9ACP"
    }
  }
}
```

**Error Response:**

```json
{
  "error": {
    "code": 422,
    "message": "Validation failed",
    "details": {
      "title": ["The title field is required"]
    }
  }
}
```

---

## HTTP Status Codes

| Code | Meaning | When to Use |
|------|---------|-------------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Malformed JSON |
| 401 | Unauthorized | Missing/invalid token |
| 403 | Forbidden | Valid token, no permission |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Unexpected exception |

---

## PHPUnit Test Example

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

final class ArticleTitleTest extends TestCase
{
    #[Test]
    public function it_creates_valid_title(): void
    {
        $title = ArticleTitle::create('Hello World');
        $this->assertSame('Hello World', $title->toString());
    }

    #[Test]
    public function it_rejects_empty_title(): void
    {
        $this->expectException(InvalidArticleTitle::class);
        ArticleTitle::create('');
    }

    #[Test]
    #[DataProvider('invalidTitlesProvider')]
    public function it_rejects_invalid_titles(string $title): void
    {
        $this->expectException(InvalidArticleTitle::class);
        ArticleTitle::create($title);
    }

    public static function invalidTitlesProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace' => ['   '],
            'too long' => [str_repeat('a', 201)],
        ];
    }
}
```

---

## Quick Commands

```bash
# Run tests
./vendor/bin/phpunit

# Static analysis
./vendor/bin/phpstan analyse

# Code style
./vendor/bin/php-cs-fixer fix

# Benchmarks
php Benchmarks/IdBenchmark.php
```

---

## Related Documentation

- [[Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development]]
- [[Tutorials/Adding-REST-API-to-Your-Module]]
- [[Implementation-Guides/XMF-Components-Guide]]
- [Error Handling & Validation](Implementation-Guides/Error-Handling-Validation-Guide.md)
- [Repository & Query Patterns](Implementation-Guides/Repository-Query-Patterns-Guide.md)
- [Entity Mapping & Database Patterns](Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md)
