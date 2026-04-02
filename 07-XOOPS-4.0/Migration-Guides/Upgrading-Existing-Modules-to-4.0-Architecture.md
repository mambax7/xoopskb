# Upgrading Existing Modules to XOOPS 4.0 Architecture

This guide walks you through incrementally modernizing an existing XOOPS module to use Clean Architecture, DDD patterns, and PHP 8.4+ features. The approach is designed to be non-destructive—you can migrate piece by piece while keeping your module functional.

## Migration Philosophy

**Key Principles:**

1. **Incremental Migration** - Don't rewrite everything at once
2. **Backwards Compatible** - Keep existing code working during transition
3. **Test-Driven** - Add tests before refactoring
4. **Domain First** - Extract domain logic before infrastructure

## Migration Phases Overview

```
Phase 1: Preparation (1-2 days)
├── Add PHP 8.4 compatibility
├── Set up Composer autoloading
└── Create basic directory structure

Phase 2: Domain Extraction (3-5 days)
├── Identify entities and value objects
├── Extract domain logic from handlers
└── Create repository interfaces

Phase 3: Infrastructure Layer (2-3 days)
├── Wrap existing database code
├── Implement repository pattern
└── Create service container

Phase 4: Application Layer (2-3 days)
├── Create commands and queries
├── Migrate business logic to handlers
└── Add validation layer

Phase 5: Presentation Layer (1-2 days)
├── Create controllers
├── Update templates
└── Add API endpoints (optional)
```

---

## Phase 1: Preparation

### Step 1.1: PHP 8.4 Compatibility

Update your module to require PHP 8.4 and add strict types:

```php
// Before (XOOPS 2.5 style)
<?php
class MyModuleItem extends XoopsObject {
    function __construct() {
        $this->initVar('item_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 200);
    }
}

// After (PHP 8.4 compatible)
<?php

declare(strict_types=1);

class MyModuleItem extends XoopsObject {
    public function __construct() {
        $this->initVar('item_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 200);
    }
}
```

### Step 1.2: Add Composer Autoloading

Create `composer.json` in your module root:

```json
{
    "name": "xoops/mymodule",
    "description": "My XOOPS Module",
    "type": "xoops-module",
    "require": {
        "php": ">=8.4"
    },
    "autoload": {
        "psr-4": {
            "MyModule\\": ""
        },
        "classmap": [
            "class/"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "MyModule\\Tests\\": "tests/"
        }
    }
}
```

**Note:** The `classmap` includes your existing `class/` directory for backwards compatibility.

### Step 1.3: Create Directory Structure

Add new directories alongside existing code:

```
mymodule/
├── class/                    # Existing XOOPS classes (keep these)
│   ├── item.php
│   └── item_handler.php
├── Domain/                   # NEW: Domain layer
│   ├── Entity/
│   ├── ValueObject/
│   ├── Repository/
│   └── Exception/
├── Application/              # NEW: Application layer
│   ├── Command/
│   └── Query/
├── Infrastructure/           # NEW: Infrastructure layer
│   ├── Persistence/
│   └── Xoops/
├── Presentation/             # NEW: Presentation layer (optional)
│   └── Controller/
├── composer.json             # NEW
└── ... (existing files)
```

### Step 1.4: Update xoops_version.php

```php
<?php

declare(strict_types=1);

$modversion = [
    // ... existing config ...

    // Add new requirements
    'min_php' => '8.2',

    // Flag for new architecture (optional, for tooling)
    'architecture' => 'hybrid',  // 'legacy', 'hybrid', or 'clean'
];
```

---

## Phase 2: Domain Extraction

### Step 2.1: Identify Your Domain Model

Analyze your existing `class/item.php` and `class/item_handler.php` to identify:

1. **Entities** - Objects with identity (usually have an ID)
2. **Value Objects** - Immutable objects defined by their values
3. **Business Rules** - Validation and state transitions

**Example Analysis:**

```php
// Existing item.php
class MyModuleItem extends XoopsObject {
    function __construct() {
        $this->initVar('item_id', XOBJ_DTYPE_INT);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 200);    // Value Object candidate
        $this->initVar('content', XOBJ_DTYPE_TXTAREA);                 // Value Object candidate
        $this->initVar('status', XOBJ_DTYPE_INT, 0);                   // Enum candidate
        $this->initVar('user_id', XOBJ_DTYPE_INT);
        $this->initVar('created', XOBJ_DTYPE_INT);
        $this->initVar('updated', XOBJ_DTYPE_INT);
    }

    // Business logic mixed with presentation - EXTRACT THIS
    function getStatusText() {
        $statuses = [0 => 'Draft', 1 => 'Published', 2 => 'Archived'];
        return $statuses[$this->getVar('status')];
    }

    // Validation logic - EXTRACT THIS
    function isValid() {
        return strlen($this->getVar('title')) >= 1;
    }
}
```

### Step 2.2: Create Value Objects

Start with the simplest value objects:

```php
<?php

declare(strict_types=1);

namespace MyModule\Domain\ValueObject;

use MyModule\Domain\Exception\InvalidItemTitle;

/**
 * ItemTitle - Extracted from XoopsObject title field.
 */
final readonly class ItemTitle implements \Stringable, \JsonSerializable
{
    private const int MIN_LENGTH = 1;
    private const int MAX_LENGTH = 200;

    private function __construct(
        private string $value
    ) {}

    public static function create(string $title): self
    {
        $title = trim($title);

        if (mb_strlen($title) < self::MIN_LENGTH) {
            throw InvalidItemTitle::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($title) > self::MAX_LENGTH) {
            throw InvalidItemTitle::tooLong(self::MAX_LENGTH);
        }

        return new self($title);
    }

    /**
     * Create from legacy XoopsObject.
     * Use this during migration to wrap existing data.
     */
    public static function fromLegacy(\XoopsObject $object): self
    {
        return self::create((string) $object->getVar('title', 'e'));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
```

### Step 2.3: Create ID Value Object

Use ULID for new records, but support legacy integer IDs:

```php
<?php

declare(strict_types=1);

namespace MyModule\Domain\ValueObject;

use Xmf\Ulid;
use MyModule\Domain\Exception\InvalidItemId;

/**
 * ItemId - Supports both legacy integer IDs and new ULIDs.
 */
final readonly class ItemId implements \Stringable, \JsonSerializable
{
    private function __construct(
        private string $value,
        private bool $isLegacy
    ) {}

    /**
     * Generate a new ULID-based ID.
     */
    public static function generate(): self
    {
        return new self(Ulid::generate()->toString(), false);
    }

    /**
     * Create from a string (ULID format).
     */
    public static function fromString(string $id): self
    {
        if (Ulid::isValid($id)) {
            return new self($id, false);
        }

        throw InvalidItemId::invalidFormat($id);
    }

    /**
     * Create from a legacy integer ID.
     * Use during migration to wrap existing database IDs.
     */
    public static function fromLegacyInt(int $id): self
    {
        if ($id <= 0) {
            throw InvalidItemId::invalidLegacyId($id);
        }

        return new self((string) $id, true);
    }

    /**
     * Check if this is a legacy integer ID.
     */
    public function isLegacy(): bool
    {
        return $this->isLegacy;
    }

    /**
     * Get as integer (for legacy database queries).
     *
     * @throws \LogicException If not a legacy ID
     */
    public function toInt(): int
    {
        if (!$this->isLegacy) {
            throw new \LogicException('Cannot convert ULID to integer');
        }

        return (int) $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
```

### Step 2.4: Create Status Enum

```php
<?php

declare(strict_types=1);

namespace MyModule\Domain\ValueObject;

/**
 * ItemStatus - Replaces magic numbers from legacy code.
 */
enum ItemStatus: int
{
    case Draft = 0;
    case Published = 1;
    case Archived = 2;

    /**
     * Create from legacy integer value.
     */
    public static function fromLegacy(int $value): self
    {
        return self::tryFrom($value) ?? self::Draft;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => _MD_MYMODULE_STATUS_DRAFT,
            self::Published => _MD_MYMODULE_STATUS_PUBLISHED,
            self::Archived => _MD_MYMODULE_STATUS_ARCHIVED,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Published, self::Archived]),
            self::Published => $target === self::Archived,
            self::Archived => $target === self::Draft,
        };
    }
}
```

### Step 2.5: Create the Domain Entity

```php
<?php

declare(strict_types=1);

namespace MyModule\Domain\Entity;

use MyModule\Domain\ValueObject\ItemId;
use MyModule\Domain\ValueObject\ItemTitle;
use MyModule\Domain\ValueObject\ItemContent;
use MyModule\Domain\ValueObject\ItemStatus;
use MyModule\Domain\Exception\InvalidStatusTransition;

/**
 * Item - Domain entity extracted from MyModuleItem XoopsObject.
 */
final class Item
{
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly ItemId $id,
        private readonly int $userId,
        private ItemTitle $title,
        private ItemContent $content,
        private ItemStatus $status,
        private readonly \DateTimeImmutable $createdAt
    ) {
        $this->updatedAt = $createdAt;
    }

    /**
     * Create a new Item (for new records).
     */
    public static function create(
        int $userId,
        ItemTitle $title,
        ItemContent $content
    ): self {
        return new self(
            id: ItemId::generate(),
            userId: $userId,
            title: $title,
            content: $content,
            status: ItemStatus::Draft,
            createdAt: new \DateTimeImmutable()
        );
    }

    /**
     * Reconstitute from persistence (new or legacy).
     */
    public static function reconstitute(
        ItemId $id,
        int $userId,
        ItemTitle $title,
        ItemContent $content,
        ItemStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt
    ): self {
        $item = new self($id, $userId, $title, $content, $status, $createdAt);
        $item->updatedAt = $updatedAt;
        return $item;
    }

    /**
     * Create from legacy XoopsObject.
     * Bridge method for gradual migration.
     */
    public static function fromLegacy(\XoopsObject $object): self
    {
        return self::reconstitute(
            id: ItemId::fromLegacyInt((int) $object->getVar('item_id')),
            userId: (int) $object->getVar('user_id'),
            title: ItemTitle::fromLegacy($object),
            content: ItemContent::fromLegacy($object),
            status: ItemStatus::fromLegacy((int) $object->getVar('status')),
            createdAt: (new \DateTimeImmutable())->setTimestamp((int) $object->getVar('created')),
            updatedAt: (new \DateTimeImmutable())->setTimestamp((int) $object->getVar('updated'))
        );
    }

    // ... getters and domain methods same as before ...

    public function getId(): ItemId { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTitle(): ItemTitle { return $this->title; }
    public function getContent(): ItemContent { return $this->content; }
    public function getStatus(): ItemStatus { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function updateTitle(ItemTitle $newTitle): void
    {
        if (!$this->title->equals($newTitle)) {
            $this->title = $newTitle;
            $this->touch();
        }
    }

    public function publish(): void
    {
        if (!$this->status->canTransitionTo(ItemStatus::Published)) {
            throw InvalidStatusTransition::create($this->status, ItemStatus::Published);
        }
        $this->status = ItemStatus::Published;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

### Step 2.6: Create Repository Interface

```php
<?php

declare(strict_types=1);

namespace MyModule\Domain\Repository;

use MyModule\Domain\Entity\Item;
use MyModule\Domain\ValueObject\ItemId;

/**
 * ItemRepositoryInterface - Persistence contract.
 */
interface ItemRepositoryInterface
{
    public function findById(ItemId $id): Item;
    public function findByIdOrNull(ItemId $id): ?Item;
    public function findByUserId(int $userId): array;
    public function save(Item $item): void;
    public function delete(Item $item): void;
    public function exists(ItemId $id): bool;
}
```

---

## Phase 3: Infrastructure Layer

### Step 3.1: Create Legacy Repository Adapter

This wraps your existing handler to implement the new interface:

```php
<?php

declare(strict_types=1);

namespace MyModule\Infrastructure\Persistence;

use MyModule\Domain\Entity\Item;
use MyModule\Domain\ValueObject\ItemId;
use MyModule\Domain\ValueObject\ItemTitle;
use MyModule\Domain\ValueObject\ItemContent;
use MyModule\Domain\ValueObject\ItemStatus;
use MyModule\Domain\Repository\ItemRepositoryInterface;
use MyModule\Domain\Exception\ItemNotFound;

/**
 * LegacyItemRepository - Wraps existing XoopsObjectHandler.
 *
 * Use this during migration. Eventually replace with pure SQL implementation.
 */
final class LegacyItemRepository implements ItemRepositoryInterface
{
    private \MyModuleItemHandler $handler;

    public function __construct(\XoopsDatabase $db)
    {
        // Get the legacy handler
        $this->handler = \xoops_getModuleHandler('item', 'mymodule');
    }

    public function findById(ItemId $id): Item
    {
        $item = $this->findByIdOrNull($id);

        if ($item === null) {
            throw ItemNotFound::withId($id);
        }

        return $item;
    }

    public function findByIdOrNull(ItemId $id): ?Item
    {
        if (!$id->isLegacy()) {
            // New ULID-based ID - check new column if exists
            // For now, return null (not found in legacy system)
            return null;
        }

        $object = $this->handler->get($id->toInt());

        if (!$object || $object->isNew()) {
            return null;
        }

        return $this->toDomainEntity($object);
    }

    public function findByUserId(int $userId): array
    {
        $criteria = new \CriteriaCompo();
        $criteria->add(new \Criteria('user_id', $userId));
        $criteria->setSort('updated');
        $criteria->setOrder('DESC');

        $objects = $this->handler->getObjects($criteria);

        return array_map([$this, 'toDomainEntity'], $objects);
    }

    public function save(Item $item): void
    {
        if ($item->getId()->isLegacy()) {
            // Update existing legacy record
            $object = $this->handler->get($item->getId()->toInt());
        } else {
            // New record - create XoopsObject
            $object = $this->handler->create();
        }

        $this->applyToObject($item, $object);
        $this->handler->insert($object);

        // If this was a new domain entity, we might need to handle
        // the auto-increment ID somehow (for legacy compatibility)
    }

    public function delete(Item $item): void
    {
        if (!$item->getId()->isLegacy()) {
            return; // Can't delete ULID-based items from legacy storage
        }

        $object = $this->handler->get($item->getId()->toInt());
        if ($object) {
            $this->handler->delete($object);
        }
    }

    public function exists(ItemId $id): bool
    {
        return $this->findByIdOrNull($id) !== null;
    }

    /**
     * Convert XoopsObject to Domain Entity.
     */
    private function toDomainEntity(\XoopsObject $object): Item
    {
        return Item::fromLegacy($object);
    }

    /**
     * Apply Domain Entity changes to XoopsObject.
     */
    private function applyToObject(Item $item, \XoopsObject $object): void
    {
        $object->setVar('title', $item->getTitle()->toString());
        $object->setVar('content', $item->getContent()->toString());
        $object->setVar('status', $item->getStatus()->value);
        $object->setVar('user_id', $item->getUserId());
        $object->setVar('updated', $item->getUpdatedAt()->getTimestamp());

        if ($object->isNew()) {
            $object->setVar('created', $item->getCreatedAt()->getTimestamp());
        }
    }
}
```

### Step 3.2: Create Service Container

```php
<?php

declare(strict_types=1);

namespace MyModule\Infrastructure\Xoops;

use MyModule\Domain\Repository\ItemRepositoryInterface;
use MyModule\Infrastructure\Persistence\LegacyItemRepository;
use MyModule\Application\Command\CreateItemHandler;
use MyModule\Application\Command\UpdateItemHandler;
use MyModule\Application\Query\GetItemHandler;
use MyModule\Application\Query\ListItemsHandler;

/**
 * Container - Dependency injection for the module.
 */
final class Container
{
    private array $services = [];
    private static ?self $instance = null;

    private function __construct(
        private readonly \XoopsDatabase $db
    ) {}

    /**
     * Get singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self($GLOBALS['xoopsDB']);
        }

        return self::$instance;
    }

    public function getItemRepository(): ItemRepositoryInterface
    {
        return $this->services[ItemRepositoryInterface::class] ??=
            new LegacyItemRepository($this->db);
    }

    public function getCreateItemHandler(): CreateItemHandler
    {
        return $this->services[CreateItemHandler::class] ??=
            new CreateItemHandler($this->getItemRepository());
    }

    public function getUpdateItemHandler(): UpdateItemHandler
    {
        return $this->services[UpdateItemHandler::class] ??=
            new UpdateItemHandler($this->getItemRepository());
    }

    public function getGetItemHandler(): GetItemHandler
    {
        return $this->services[GetItemHandler::class] ??=
            new GetItemHandler($this->getItemRepository());
    }

    public function getListItemsHandler(): ListItemsHandler
    {
        return $this->services[ListItemsHandler::class] ??=
            new ListItemsHandler($this->getItemRepository());
    }
}
```

---

## Phase 4: Application Layer

### Step 4.1: Create Commands

```php
<?php

declare(strict_types=1);

namespace MyModule\Application\Command;

final readonly class CreateItemCommand
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $content = ''
    ) {}
}

final readonly class CreateItemHandler
{
    public function __construct(
        private \MyModule\Domain\Repository\ItemRepositoryInterface $repository
    ) {}

    public function handle(CreateItemCommand $command): \MyModule\Domain\Entity\Item
    {
        $item = \MyModule\Domain\Entity\Item::create(
            userId: $command->userId,
            title: \MyModule\Domain\ValueObject\ItemTitle::create($command->title),
            content: \MyModule\Domain\ValueObject\ItemContent::create($command->content)
        );

        $this->repository->save($item);

        return $item;
    }
}
```

### Step 4.2: Update Existing Code to Use New Architecture

Gradually update your existing files to use the new infrastructure:

```php
<?php
// submit.php - Before (legacy)

include_once dirname(__DIR__, 2) . '/mainfile.php';
$itemHandler = xoops_getModuleHandler('item', 'mymodule');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = $itemHandler->create();
    $item->setVar('title', $_POST['title']);
    $item->setVar('content', $_POST['content']);
    $item->setVar('user_id', $xoopsUser->uid());
    $item->setVar('status', 0);
    $item->setVar('created', time());
    $item->setVar('updated', time());

    if ($itemHandler->insert($item)) {
        redirect_header('index.php', 2, 'Item created');
    }
}
```

```php
<?php
// submit.php - After (using new architecture)

declare(strict_types=1);

include_once dirname(__DIR__, 2) . '/mainfile.php';

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use MyModule\Infrastructure\Xoops\Container;
use MyModule\Application\Command\CreateItemCommand;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $container = Container::getInstance();
    $handler = $container->getCreateItemHandler();

    try {
        $command = new CreateItemCommand(
            userId: $xoopsUser->uid(),
            title: $_POST['title'] ?? '',
            content: $_POST['content'] ?? ''
        );

        $item = $handler->handle($command);

        redirect_header('index.php', 2, 'Item created');
    } catch (\MyModule\Domain\Exception\ItemException $e) {
        // Handle validation errors
        $GLOBALS['xoopsTpl']->assign('error', $e->getMessage());
    }
}
```

---

## Phase 5: Database Migration

### Step 5.1: Add ULID Column

When you're ready to move away from auto-increment IDs:

```sql
-- Add ULID column (nullable initially)
ALTER TABLE `mymodule_item`
ADD COLUMN `ulid` CHAR(26) NULL AFTER `item_id`,
ADD UNIQUE KEY `uk_ulid` (`ulid`);

-- Generate ULIDs for existing records (run via PHP script)
-- See migration script below
```

### Step 5.2: Migration Script

```php
<?php

declare(strict_types=1);

/**
 * Migration script to add ULIDs to existing records.
 * Run this from admin area or CLI.
 */

require_once dirname(__DIR__, 3) . '/mainfile.php';
require_once __DIR__ . '/vendor/autoload.php';

use Xmf\Ulid;

$db = $GLOBALS['xoopsDB'];
$table = $db->prefix('mymodule_item');

// Get records without ULID
$sql = "SELECT item_id, created FROM {$table} WHERE ulid IS NULL ORDER BY created ASC";
$result = $db->query($sql);

$count = 0;
while ($row = $db->fetchArray($result)) {
    // Generate ULID based on original creation timestamp
    $timestamp = new DateTimeImmutable('@' . $row['created']);
    $ulid = Ulid::generate($timestamp);

    $updateSql = sprintf(
        "UPDATE {$table} SET ulid = %s WHERE item_id = %d",
        $db->quoteString($ulid->toString()),
        (int) $row['item_id']
    );

    $db->queryF($updateSql);
    $count++;
}

echo "Migrated {$count} records.\n";
```

### Step 5.3: Update Repository to Use ULID

After migration, update your repository to prefer ULID:

```php
public function findByIdOrNull(ItemId $id): ?Item
{
    if ($id->isLegacy()) {
        // Try legacy integer lookup first
        $sql = sprintf(
            "SELECT * FROM %s WHERE item_id = %d",
            $this->db->prefix('mymodule_item'),
            $id->toInt()
        );
    } else {
        // ULID lookup
        $sql = sprintf(
            "SELECT * FROM %s WHERE ulid = %s",
            $this->db->prefix('mymodule_item'),
            $this->db->quoteString($id->toString())
        );
    }

    $result = $this->db->query($sql);
    $row = $this->db->fetchArray($result);

    if (!$row) {
        return null;
    }

    return $this->hydrate($row);
}
```

---

## Testing Your Migration

### Add Tests as You Migrate

```php
<?php

declare(strict_types=1);

namespace MyModule\Tests\Migration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests to verify migration doesn't break existing functionality.
 */
final class LegacyCompatibilityTest extends TestCase
{
    #[Test]
    public function it_loads_legacy_items(): void
    {
        // Assuming you have test data in the database
        $container = \MyModule\Infrastructure\Xoops\Container::getInstance();
        $repository = $container->getItemRepository();

        // Load a known legacy item
        $id = \MyModule\Domain\ValueObject\ItemId::fromLegacyInt(1);
        $item = $repository->findByIdOrNull($id);

        $this->assertNotNull($item);
        $this->assertTrue($item->getId()->isLegacy());
    }

    #[Test]
    public function it_creates_new_items_with_ulid(): void
    {
        $container = \MyModule\Infrastructure\Xoops\Container::getInstance();
        $handler = $container->getCreateItemHandler();

        $command = new \MyModule\Application\Command\CreateItemCommand(
            userId: 1,
            title: 'Test Item',
            content: 'Test content'
        );

        $item = $handler->handle($command);

        $this->assertFalse($item->getId()->isLegacy());
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $item->getId()->toString());
    }

    #[Test]
    public function domain_entity_matches_legacy_data(): void
    {
        // Load same item both ways and compare
        $legacyHandler = xoops_getModuleHandler('item', 'mymodule');
        $legacyObject = $legacyHandler->get(1);

        $domainEntity = \MyModule\Domain\Entity\Item::fromLegacy($legacyObject);

        $this->assertSame(
            $legacyObject->getVar('title', 'e'),
            $domainEntity->getTitle()->toString()
        );
    }
}
```

---

## Migration Checklist

### Phase 1: Preparation
- [ ] Add `declare(strict_types=1)` to all files
- [ ] Update constructor visibility (`function __construct` → `public function __construct`)
- [ ] Create `composer.json` with autoloading
- [ ] Run `composer dump-autoload`
- [ ] Create new directory structure
- [ ] Update `xoops_version.php` with PHP 8.4 requirement

### Phase 2: Domain Extraction
- [ ] Identify entities from existing XoopsObjects
- [ ] Create value objects for validated fields (Title, Content, etc.)
- [ ] Create ItemId with legacy support
- [ ] Create status enum
- [ ] Create domain entity with `fromLegacy()` method
- [ ] Create repository interface
- [ ] Create domain exceptions

### Phase 3: Infrastructure Layer
- [ ] Create LegacyItemRepository wrapping existing handler
- [ ] Create service container
- [ ] Test that legacy data loads correctly

### Phase 4: Application Layer
- [ ] Create command classes
- [ ] Create command handlers
- [ ] Create query classes
- [ ] Create query handlers
- [ ] Update one entry point to use new architecture
- [ ] Test end-to-end

### Phase 5: Database Migration
- [ ] Add ULID column to database
- [ ] Run migration script for existing records
- [ ] Update repository to support both ID formats
- [ ] Test legacy and new records work together

### Final Steps
- [ ] Update all entry points to use new architecture
- [ ] Remove legacy code (optional - can keep for reference)
- [ ] Update `architecture` flag to `'clean'`
- [ ] Comprehensive testing

---

## Common Pitfalls

1. **Don't migrate everything at once** - Pick one entity and complete the full cycle
2. **Keep legacy code working** - Use adapter pattern to wrap existing handlers
3. **Test with real data** - Migration bugs often appear with edge cases
4. **Handle ID conversion carefully** - Legacy integer IDs and ULIDs must coexist
5. **Update one file at a time** - Easier to debug and rollback

## Related Documentation

- [[../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development]]
- [[../Implementation-Guides/XMF-Components-Guide]]
- [Error Handling & Validation](../Implementation-Guides/Error-Handling-Validation-Guide.md)
- [Entity Mapping & Database Patterns](../Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md)
