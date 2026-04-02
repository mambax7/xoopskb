# XOOPS 4.0 VS Code Snippets

Code snippets for faster XOOPS 4.0 module development in Visual Studio Code.

## Installation

### Option 1: User Snippets (All Projects)

Copy `xoops-4.0.code-snippets` to your VS Code snippets folder:

- **Windows:** `%APPDATA%\Code\User\snippets\`
- **macOS:** `~/Library/Application Support/Code/User/snippets/`
- **Linux:** `~/.config/Code/User/snippets/`

### Option 2: Workspace Snippets (Single Project)

Copy to your project's `.vscode` folder:

```
your-module/
└── .vscode/
    └── xoops-4.0.code-snippets
```

## Available Snippets

### Value Objects

| Prefix | Description | Output |
|--------|-------------|--------|
| `xvo` | Value Object | Complete value object class with validation |
| `xid` | Entity ID | ULID-based ID value object |
| `xstatus` | Status Enum | Status enum with transitions |

### Entities

| Prefix | Description | Output |
|--------|-------------|--------|
| `xentity` | Domain Entity | Full entity with factory and reconstitute methods |

### Repository

| Prefix | Description | Output |
|--------|-------------|--------|
| `xrepo` | Repository Interface | Repository contract with standard methods |

### Exceptions

| Prefix | Description | Output |
|--------|-------------|--------|
| `xexception` | Domain Exception | Exception with error code and context |

### Commands & Queries

| Prefix | Description | Output |
|--------|-------------|--------|
| `xcmd` | Command | Immutable command DTO |
| `xhandler` | Command Handler | Handler class with repository injection |
| `xquery` | Query | Query DTO |

### Testing

| Prefix | Description | Output |
|--------|-------------|--------|
| `xtest` | Test Class | PHPUnit 11 test class with attributes |
| `xtestm` | Test Method | Single test method with AAA pattern |
| `xprovider` | Data Provider | Test with data provider |

### Common Patterns

| Prefix | Description | Output |
|--------|-------------|--------|
| `xphp` | PHP Header | Strict types and namespace declaration |
| `xulid` | ULID Generation | Generate a new ULID |
| `xulidv` | ULID Validation | Validate ULID with exception |
| `xslug` | Slug Creation | Create URL-friendly slug |
| `xtrans` | Status Transition | Check and perform status change |
| `xsql` | MySQL Query | Repository query with hydration |
| `xservice` | Container Service | Add service to DI container |

### API Patterns

| Prefix | Description | Output |
|--------|-------------|--------|
| `xapires` | API Response | JSON response structure |
| `xvalidate` | Validation | Request validation rules |

## Usage Examples

### Creating a Value Object

Type `xvo` and press Tab:

```php
<?php

declare(strict_types=1);

namespace Articles\Domain\ValueObject;

use Articles\Domain\Exception\InvalidArticleTitle;

/**
 * ArticleTitle - The title of an article.
 */
final readonly class ArticleTitle implements \Stringable, \JsonSerializable
{
    private const int MIN_LENGTH = 1;
    private const int MAX_LENGTH = 200;
    // ... rest of class
}
```

### Creating a Test

Type `xtest` and press Tab:

```php
<?php

declare(strict_types=1);

namespace Articles\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ArticleTitle::class)]
final class ArticleTitleTest extends TestCase
{
    #[Test]
    public function it_creates_valid_title(): void
    {
        // Arrange, Act, Assert
    }
}
```

### Adding a Test Method

Inside a test class, type `xtestm` and press Tab:

```php
#[Test]
public function it_rejects_empty_title(): void
{
    // Arrange

    // Act

    // Assert
    $this->assertTrue();
}
```

## Tab Stops

Snippets use tab stops (`$1`, `$2`, etc.) for quick navigation:

1. Type the prefix and press `Tab` to expand
2. Fill in the first placeholder
3. Press `Tab` to move to the next placeholder
4. Press `Escape` when done

## Customization

Edit the snippet file to:

- Change default values
- Add your company namespace
- Modify coding style
- Add new snippets

### Adding a Custom Snippet

```json
"My Custom Snippet": {
  "prefix": "mycustom",
  "description": "Description here",
  "body": [
    "<?php",
    "",
    "// ${1:placeholder}",
    "${0}"
  ]
}
```

## Related Documentation

- [[../../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development]]
- [[../../Quick-Reference-Card]]
- [[../../Implementation-Guides/Error-Handling-Validation-Guide]]
