# Getting Started with XOOPS 4.0 Module Development

This comprehensive tutorial guides you through building a modern XOOPS module using Domain-Driven Design, Clean Architecture, and the new XMF components. By the end, you'll have built a functional "Notes" module with full CRUD operations.

## Prerequisites

- PHP 8.4 or higher
- XOOPS 2.6.x installed
- Composer
- Basic understanding of PHP OOP
- Familiarity with MVC concepts

## What We're Building

A **Notes Module** that allows users to:
- Create, read, update, and delete personal notes
- Organize notes with tags
- Search notes by title or content
- Archive old notes

## Architecture Overview

```
Notes/
├── Domain/                    # Core business logic (no dependencies)
│   ├── Entity/
│   │   └── Note.php
│   ├── ValueObject/
│   │   ├── NoteId.php
│   │   ├── NoteTitle.php
│   │   └── NoteContent.php
│   ├── Repository/
│   │   └── NoteRepositoryInterface.php
│   └── Exception/
│       └── NoteException.php
├── Application/               # Use cases and commands
│   ├── Command/
│   │   ├── CreateNoteCommand.php
│   │   └── CreateNoteHandler.php
│   └── Query/
│       ├── GetNoteQuery.php
│       └── GetNoteHandler.php
├── Infrastructure/            # Framework integrations
│   ├── Persistence/
│   │   └── MySqlNoteRepository.php
│   └── Xoops/
│       └── NoteModule.php
└── Presentation/              # Controllers and views
    ├── Controller/
    │   └── NoteController.php
    └── templates/
        └── note_list.tpl
```

---

## Part 1: Setting Up the Module Structure

### Step 1: Create the Module Directory

```bash
mkdir -p modules/notes/{Domain/{Entity,ValueObject,Repository,Exception},Application/{Command,Query},Infrastructure/{Persistence,Xoops},Presentation/{Controller,templates}}
```

### Step 2: Create xoops_version.php

```php
<?php

declare(strict_types=1);

/**
 * Notes Module - XOOPS Version File
 *
 * @package    Notes
 * @subpackage Configuration
 */

$modversion = [
    'name'        => 'Notes',
    'version'     => '1.0.0',
    'description' => 'Personal notes management with modern architecture',
    'author'      => 'Your Name',
    'license'     => 'GPL-2.0-or-later',
    'dirname'     => 'notes',

    // Module requirements
    'min_php'     => '8.2',
    'min_xoops'   => '2.6.0',
    'min_admin'   => '1.2',

    // Architecture flag - enables autoloading
    'architecture' => 'clean',

    // Database tables
    'tables' => [
        'notes_note',
        'notes_tag',
        'notes_note_tag',
    ],

    // Admin menu
    'hasAdmin'    => 1,
    'adminindex'  => 'admin/index.php',
    'adminmenu'   => 'admin/menu.php',

    // User side
    'hasMain'     => 1,

    // Templates
    'templates' => [
        ['file' => 'notes_index.tpl', 'description' => 'Notes list page'],
        ['file' => 'notes_view.tpl', 'description' => 'Single note view'],
        ['file' => 'notes_form.tpl', 'description' => 'Note form'],
    ],

    // Blocks
    'blocks' => [
        [
            'file'        => 'blocks.php',
            'name'        => 'Recent Notes',
            'description' => 'Display recent notes',
            'show_func'   => 'notes_block_recent',
            'template'    => 'notes_block_recent.tpl',
        ],
    ],
];
```

---

## Part 2: Building the Domain Layer

The Domain layer is the heart of your module. It contains pure business logic with no external dependencies.

### Step 3: Create the NoteId Value Object

Using the XMF ULID component for time-sortable identifiers:

```php
<?php

declare(strict_types=1);

namespace Notes\Domain\ValueObject;

use Xmf\Ulid;
use Notes\Domain\Exception\InvalidNoteId;

/**
 * NoteId - Unique identifier for a Note entity.
 *
 * Uses ULID for time-sorted, URL-safe identifiers.
 * Example: 01HV8X5Z0KDMVR8SDPY62J9ACP
 */
final readonly class NoteId implements \Stringable, \JsonSerializable
{
    private function __construct(
        private Ulid $ulid
    ) {}

    /**
     * Generate a new NoteId.
     */
    public static function generate(): self
    {
        return new self(Ulid::generate());
    }

    /**
     * Create from an existing ULID string.
     *
     * @throws InvalidNoteId If the string is not a valid ULID
     */
    public static function fromString(string $id): self
    {
        if (!Ulid::isValid($id)) {
            throw InvalidNoteId::invalidFormat($id);
        }

        return new self(Ulid::fromString($id));
    }

    /**
     * Get the string representation.
     */
    public function toString(): string
    {
        return $this->ulid->toString();
    }

    /**
     * Get the creation timestamp.
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->ulid->getTimestamp();
    }

    /**
     * Check equality with another NoteId.
     */
    public function equals(self $other): bool
    {
        return $this->ulid->equals($other->ulid);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
```

### Step 4: Create the NoteTitle Value Object

```php
<?php

declare(strict_types=1);

namespace Notes\Domain\ValueObject;

use Notes\Domain\Exception\InvalidNoteTitle;

/**
 * NoteTitle - The title of a note.
 *
 * Constraints:
 * - Must be between 1 and 200 characters
 * - Cannot be only whitespace
 */
final readonly class NoteTitle implements \Stringable, \JsonSerializable
{
    private const int MIN_LENGTH = 1;
    private const int MAX_LENGTH = 200;

    private function __construct(
        private string $value
    ) {}

    /**
     * Create a new NoteTitle.
     *
     * @throws InvalidNoteTitle If validation fails
     */
    public static function create(string $title): self
    {
        $title = trim($title);

        if (mb_strlen($title) < self::MIN_LENGTH) {
            throw InvalidNoteTitle::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($title) > self::MAX_LENGTH) {
            throw InvalidNoteTitle::tooLong(self::MAX_LENGTH);
        }

        return new self($title);
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

### Step 5: Create the NoteContent Value Object

```php
<?php

declare(strict_types=1);

namespace Notes\Domain\ValueObject;

use Notes\Domain\Exception\InvalidNoteContent;

/**
 * NoteContent - The body content of a note.
 *
 * Constraints:
 * - Maximum 50,000 characters
 * - Can be empty
 */
final readonly class NoteContent implements \Stringable, \JsonSerializable
{
    private const int MAX_LENGTH = 50_000;

    private function __construct(
        private string $value
    ) {}

    /**
     * Create a new NoteContent.
     *
     * @throws InvalidNoteContent If validation fails
     */
    public static function create(string $content): self
    {
        if (mb_strlen($content) > self::MAX_LENGTH) {
            throw InvalidNoteContent::tooLong(self::MAX_LENGTH);
        }

        return new self($content);
    }

    /**
     * Create empty content.
     */
    public static function empty(): self
    {
        return new self('');
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Get word count.
     */
    public function getWordCount(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return str_word_count($this->value);
    }

    /**
     * Get a preview (first N characters).
     */
    public function getPreview(int $length = 150): string
    {
        if (mb_strlen($this->value) <= $length) {
            return $this->value;
        }

        return mb_substr($this->value, 0, $length) . '...';
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

### Step 6: Create Domain Exceptions

```php
<?php

declare(strict_types=1);

namespace Notes\Domain\Exception;

/**
 * Base exception for Note domain errors.
 */
abstract class NoteException extends \DomainException
{
    protected function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function toArray(): array
    {
        return [
            'error' => $this->errorCode,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}

/**
 * Thrown when a NoteId is invalid.
 */
final class InvalidNoteId extends NoteException
{
    public static function invalidFormat(string $value): self
    {
        return new self(
            message: "Invalid note ID format: '{$value}'",
            errorCode: 'INVALID_NOTE_ID_FORMAT',
            context: ['value' => $value]
        );
    }
}

/**
 * Thrown when a NoteTitle is invalid.
 */
final class InvalidNoteTitle extends NoteException
{
    public static function tooShort(int $minLength): self
    {
        return new self(
            message: "Note title must be at least {$minLength} character(s)",
            errorCode: 'NOTE_TITLE_TOO_SHORT',
            context: ['min_length' => $minLength]
        );
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(
            message: "Note title cannot exceed {$maxLength} characters",
            errorCode: 'NOTE_TITLE_TOO_LONG',
            context: ['max_length' => $maxLength]
        );
    }
}

/**
 * Thrown when NoteContent is invalid.
 */
final class InvalidNoteContent extends NoteException
{
    public static function tooLong(int $maxLength): self
    {
        return new self(
            message: "Note content cannot exceed {$maxLength} characters",
            errorCode: 'NOTE_CONTENT_TOO_LONG',
            context: ['max_length' => $maxLength]
        );
    }
}

/**
 * Thrown when a Note is not found.
 */
final class NoteNotFound extends NoteException
{
    public static function withId(NoteId $id): self
    {
        return new self(
            message: "Note not found with ID: {$id}",
            errorCode: 'NOTE_NOT_FOUND',
            context: ['note_id' => $id->toString()]
        );
    }
}
```

### Step 7: Create the Note Entity

```php
<?php

declare(strict_types=1);

namespace Notes\Domain\Entity;

use Notes\Domain\ValueObject\NoteId;
use Notes\Domain\ValueObject\NoteTitle;
use Notes\Domain\ValueObject\NoteContent;

/**
 * Note - The core domain entity.
 *
 * A Note is the aggregate root for the Notes bounded context.
 * It enforces all business rules and maintains consistency.
 */
final class Note
{
    private \DateTimeImmutable $updatedAt;
    private bool $isArchived = false;

    private function __construct(
        private readonly NoteId $id,
        private readonly int $userId,
        private NoteTitle $title,
        private NoteContent $content,
        private readonly \DateTimeImmutable $createdAt
    ) {
        $this->updatedAt = $createdAt;
    }

    /**
     * Create a new Note.
     *
     * Factory method ensures all invariants are satisfied.
     */
    public static function create(
        int $userId,
        NoteTitle $title,
        NoteContent $content,
        ?\DateTimeImmutable $createdAt = null
    ): self {
        return new self(
            id: NoteId::generate(),
            userId: $userId,
            title: $title,
            content: $content,
            createdAt: $createdAt ?? new \DateTimeImmutable()
        );
    }

    /**
     * Reconstitute from persistence.
     *
     * Used by the repository to rebuild entities from storage.
     */
    public static function reconstitute(
        NoteId $id,
        int $userId,
        NoteTitle $title,
        NoteContent $content,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        bool $isArchived
    ): self {
        $note = new self($id, $userId, $title, $content, $createdAt);
        $note->updatedAt = $updatedAt;
        $note->isArchived = $isArchived;

        return $note;
    }

    // === Getters ===

    public function getId(): NoteId
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTitle(): NoteTitle
    {
        return $this->title;
    }

    public function getContent(): NoteContent
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    // === Domain Behaviors ===

    /**
     * Update the note's title.
     */
    public function updateTitle(NoteTitle $newTitle): void
    {
        if ($this->title->equals($newTitle)) {
            return; // No change needed
        }

        $this->title = $newTitle;
        $this->touch();
    }

    /**
     * Update the note's content.
     */
    public function updateContent(NoteContent $newContent): void
    {
        if ($this->content->equals($newContent)) {
            return;
        }

        $this->content = $newContent;
        $this->touch();
    }

    /**
     * Archive the note.
     */
    public function archive(): void
    {
        if ($this->isArchived) {
            return; // Already archived
        }

        $this->isArchived = true;
        $this->touch();
    }

    /**
     * Restore from archive.
     */
    public function restore(): void
    {
        if (!$this->isArchived) {
            return; // Not archived
        }

        $this->isArchived = false;
        $this->touch();
    }

    /**
     * Check if user can edit this note.
     */
    public function canBeEditedBy(int $userId): bool
    {
        return $this->userId === $userId;
    }

    /**
     * Update the modification timestamp.
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

### Step 8: Create the Repository Interface

```php
<?php

declare(strict_types=1);

namespace Notes\Domain\Repository;

use Notes\Domain\Entity\Note;
use Notes\Domain\ValueObject\NoteId;
use Notes\Domain\Exception\NoteNotFound;

/**
 * NoteRepositoryInterface - Defines persistence contract.
 *
 * The domain layer defines the interface; the infrastructure
 * layer provides the implementation. This is Dependency Inversion.
 */
interface NoteRepositoryInterface
{
    /**
     * Find a note by its ID.
     *
     * @throws NoteNotFound If the note doesn't exist
     */
    public function findById(NoteId $id): Note;

    /**
     * Find a note by ID, or return null.
     */
    public function findByIdOrNull(NoteId $id): ?Note;

    /**
     * Find all notes for a user.
     *
     * @return Note[]
     */
    public function findByUserId(int $userId, bool $includeArchived = false): array;

    /**
     * Save a note (insert or update).
     */
    public function save(Note $note): void;

    /**
     * Delete a note permanently.
     */
    public function delete(Note $note): void;

    /**
     * Check if a note exists.
     */
    public function exists(NoteId $id): bool;

    /**
     * Count notes for a user.
     */
    public function countByUserId(int $userId, bool $includeArchived = false): int;
}
```

---

## Part 3: Building the Application Layer

The Application layer orchestrates domain objects to fulfill use cases.

### Step 9: Create the CreateNote Command

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Command;

/**
 * CreateNoteCommand - Request to create a new note.
 *
 * Commands are simple DTOs that carry the intent of an action.
 * They contain only the data needed to execute the command.
 */
final readonly class CreateNoteCommand
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $content = ''
    ) {}
}
```

### Step 10: Create the CreateNote Handler

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Command;

use Notes\Domain\Entity\Note;
use Notes\Domain\ValueObject\NoteTitle;
use Notes\Domain\ValueObject\NoteContent;
use Notes\Domain\Repository\NoteRepositoryInterface;

/**
 * CreateNoteHandler - Executes the CreateNote use case.
 *
 * Handlers coordinate between commands and the domain model.
 * They don't contain business logic - that stays in entities.
 */
final readonly class CreateNoteHandler
{
    public function __construct(
        private NoteRepositoryInterface $repository
    ) {}

    /**
     * Handle the command.
     *
     * @return Note The created note
     * @throws InvalidNoteTitle If title validation fails
     * @throws InvalidNoteContent If content validation fails
     */
    public function handle(CreateNoteCommand $command): Note
    {
        // Create value objects (validation happens here)
        $title = NoteTitle::create($command->title);
        $content = NoteContent::create($command->content);

        // Create the domain entity
        $note = Note::create(
            userId: $command->userId,
            title: $title,
            content: $content
        );

        // Persist
        $this->repository->save($note);

        return $note;
    }
}
```

### Step 11: Create the UpdateNote Command and Handler

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Command;

/**
 * UpdateNoteCommand - Request to update an existing note.
 */
final readonly class UpdateNoteCommand
{
    public function __construct(
        public string $noteId,
        public int $userId,
        public ?string $title = null,
        public ?string $content = null
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Command;

use Notes\Domain\Entity\Note;
use Notes\Domain\ValueObject\NoteId;
use Notes\Domain\ValueObject\NoteTitle;
use Notes\Domain\ValueObject\NoteContent;
use Notes\Domain\Repository\NoteRepositoryInterface;
use Notes\Domain\Exception\NoteNotFound;

/**
 * UpdateNoteHandler - Executes the UpdateNote use case.
 */
final readonly class UpdateNoteHandler
{
    public function __construct(
        private NoteRepositoryInterface $repository
    ) {}

    /**
     * Handle the command.
     *
     * @throws NoteNotFound If note doesn't exist
     * @throws \DomainException If user cannot edit this note
     */
    public function handle(UpdateNoteCommand $command): Note
    {
        $noteId = NoteId::fromString($command->noteId);
        $note = $this->repository->findById($noteId);

        // Authorization check
        if (!$note->canBeEditedBy($command->userId)) {
            throw new \DomainException('You cannot edit this note');
        }

        // Update title if provided
        if ($command->title !== null) {
            $note->updateTitle(NoteTitle::create($command->title));
        }

        // Update content if provided
        if ($command->content !== null) {
            $note->updateContent(NoteContent::create($command->content));
        }

        $this->repository->save($note);

        return $note;
    }
}
```

### Step 12: Create Query Objects

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Query;

/**
 * GetNoteQuery - Request to retrieve a single note.
 */
final readonly class GetNoteQuery
{
    public function __construct(
        public string $noteId,
        public int $userId
    ) {}
}

/**
 * GetUserNotesQuery - Request to retrieve all notes for a user.
 */
final readonly class GetUserNotesQuery
{
    public function __construct(
        public int $userId,
        public bool $includeArchived = false,
        public int $limit = 50,
        public int $offset = 0
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Query;

use Notes\Domain\Entity\Note;
use Notes\Domain\ValueObject\NoteId;
use Notes\Domain\Repository\NoteRepositoryInterface;
use Notes\Domain\Exception\NoteNotFound;

/**
 * GetNoteHandler - Retrieves a single note.
 */
final readonly class GetNoteHandler
{
    public function __construct(
        private NoteRepositoryInterface $repository
    ) {}

    /**
     * Handle the query.
     *
     * @throws NoteNotFound If note doesn't exist
     * @throws \DomainException If user cannot access this note
     */
    public function handle(GetNoteQuery $query): Note
    {
        $noteId = NoteId::fromString($query->noteId);
        $note = $this->repository->findById($noteId);

        if (!$note->canBeEditedBy($query->userId)) {
            throw new \DomainException('You cannot access this note');
        }

        return $note;
    }
}

/**
 * GetUserNotesHandler - Retrieves all notes for a user.
 */
final readonly class GetUserNotesHandler
{
    public function __construct(
        private NoteRepositoryInterface $repository
    ) {}

    /**
     * Handle the query.
     *
     * @return Note[]
     */
    public function handle(GetUserNotesQuery $query): array
    {
        return $this->repository->findByUserId(
            $query->userId,
            $query->includeArchived
        );
    }
}
```

---

## Part 4: Building the Infrastructure Layer

The Infrastructure layer provides concrete implementations for interfaces defined in the domain.

### Step 13: Create the MySQL Repository

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Persistence;

use Notes\Domain\Entity\Note;
use Notes\Domain\ValueObject\NoteId;
use Notes\Domain\ValueObject\NoteTitle;
use Notes\Domain\ValueObject\NoteContent;
use Notes\Domain\Repository\NoteRepositoryInterface;
use Notes\Domain\Exception\NoteNotFound;
use XoopsDatabase;

/**
 * MySqlNoteRepository - MySQL implementation of NoteRepositoryInterface.
 */
final class MySqlNoteRepository implements NoteRepositoryInterface
{
    private const TABLE = 'notes_note';

    public function __construct(
        private readonly XoopsDatabase $db
    ) {}

    public function findById(NoteId $id): Note
    {
        $note = $this->findByIdOrNull($id);

        if ($note === null) {
            throw NoteNotFound::withId($id);
        }

        return $note;
    }

    public function findByIdOrNull(NoteId $id): ?Note
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE id = %s",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($id->toString())
        );

        $result = $this->db->query($sql);
        $row = $this->db->fetchArray($result);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByUserId(int $userId, bool $includeArchived = false): array
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE user_id = %d",
            $this->db->prefix(self::TABLE),
            $userId
        );

        if (!$includeArchived) {
            $sql .= " AND is_archived = 0";
        }

        $sql .= " ORDER BY updated_at DESC";

        $result = $this->db->query($sql);
        $notes = [];

        while ($row = $this->db->fetchArray($result)) {
            $notes[] = $this->hydrate($row);
        }

        return $notes;
    }

    public function save(Note $note): void
    {
        if ($this->exists($note->getId())) {
            $this->update($note);
        } else {
            $this->insert($note);
        }
    }

    public function delete(Note $note): void
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE id = %s",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($note->getId()->toString())
        );

        $this->db->queryF($sql);
    }

    public function exists(NoteId $id): bool
    {
        $sql = sprintf(
            "SELECT COUNT(*) FROM %s WHERE id = %s",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($id->toString())
        );

        $result = $this->db->query($sql);
        [$count] = $this->db->fetchRow($result);

        return (int) $count > 0;
    }

    public function countByUserId(int $userId, bool $includeArchived = false): int
    {
        $sql = sprintf(
            "SELECT COUNT(*) FROM %s WHERE user_id = %d",
            $this->db->prefix(self::TABLE),
            $userId
        );

        if (!$includeArchived) {
            $sql .= " AND is_archived = 0";
        }

        $result = $this->db->query($sql);
        [$count] = $this->db->fetchRow($result);

        return (int) $count;
    }

    private function insert(Note $note): void
    {
        $sql = sprintf(
            "INSERT INTO %s (id, user_id, title, content, is_archived, created_at, updated_at)
             VALUES (%s, %d, %s, %s, %d, %s, %s)",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($note->getId()->toString()),
            $note->getUserId(),
            $this->db->quoteString($note->getTitle()->toString()),
            $this->db->quoteString($note->getContent()->toString()),
            $note->isArchived() ? 1 : 0,
            $this->db->quoteString($note->getCreatedAt()->format('Y-m-d H:i:s')),
            $this->db->quoteString($note->getUpdatedAt()->format('Y-m-d H:i:s'))
        );

        $this->db->queryF($sql);
    }

    private function update(Note $note): void
    {
        $sql = sprintf(
            "UPDATE %s SET
                title = %s,
                content = %s,
                is_archived = %d,
                updated_at = %s
             WHERE id = %s",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($note->getTitle()->toString()),
            $this->db->quoteString($note->getContent()->toString()),
            $note->isArchived() ? 1 : 0,
            $this->db->quoteString($note->getUpdatedAt()->format('Y-m-d H:i:s')),
            $this->db->quoteString($note->getId()->toString())
        );

        $this->db->queryF($sql);
    }

    private function hydrate(array $row): Note
    {
        return Note::reconstitute(
            id: NoteId::fromString($row['id']),
            userId: (int) $row['user_id'],
            title: NoteTitle::create($row['title']),
            content: NoteContent::create($row['content']),
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            isArchived: (bool) $row['is_archived']
        );
    }
}
```

### Step 14: Create the Database Schema

Create `sql/mysql.sql`:

```sql
-- Notes Module Database Schema
-- Uses ULID (26 chars) for primary keys

CREATE TABLE `notes_note` (
    `id` CHAR(26) NOT NULL COMMENT 'ULID primary key',
    `user_id` INT(10) UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` MEDIUMTEXT,
    `is_archived` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_user_archived` (`user_id`, `is_archived`),
    KEY `idx_updated_at` (`updated_at`),

    CONSTRAINT `fk_notes_note_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`uid`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notes_tag` (
    `id` CHAR(26) NOT NULL COMMENT 'ULID primary key',
    `user_id` INT(10) UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(60) NOT NULL,
    `color` CHAR(7) DEFAULT '#808080',
    `created_at` DATETIME NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_slug` (`user_id`, `slug`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notes_note_tag` (
    `note_id` CHAR(26) NOT NULL,
    `tag_id` CHAR(26) NOT NULL,

    PRIMARY KEY (`note_id`, `tag_id`),
    KEY `idx_tag_id` (`tag_id`),

    CONSTRAINT `fk_note_tag_note`
        FOREIGN KEY (`note_id`)
        REFERENCES `notes_note` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_note_tag_tag`
        FOREIGN KEY (`tag_id`)
        REFERENCES `notes_tag` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Part 5: Building the Presentation Layer

### Step 15: Create the Controller

```php
<?php

declare(strict_types=1);

namespace Notes\Presentation\Controller;

use Notes\Application\Command\CreateNoteCommand;
use Notes\Application\Command\CreateNoteHandler;
use Notes\Application\Command\UpdateNoteCommand;
use Notes\Application\Command\UpdateNoteHandler;
use Notes\Application\Query\GetNoteQuery;
use Notes\Application\Query\GetNoteHandler;
use Notes\Application\Query\GetUserNotesQuery;
use Notes\Application\Query\GetUserNotesHandler;
use Notes\Domain\Exception\NoteException;

/**
 * NoteController - Handles HTTP requests for notes.
 */
final class NoteController
{
    public function __construct(
        private readonly CreateNoteHandler $createHandler,
        private readonly UpdateNoteHandler $updateHandler,
        private readonly GetNoteHandler $getNoteHandler,
        private readonly GetUserNotesHandler $getUserNotesHandler
    ) {}

    /**
     * Display list of user's notes.
     */
    public function index(\Smarty $tpl, int $userId): void
    {
        $query = new GetUserNotesQuery(
            userId: $userId,
            includeArchived: false
        );

        $notes = $this->getUserNotesHandler->handle($query);

        $tpl->assign('notes', $this->formatNotes($notes));
        $tpl->assign('totalNotes', count($notes));
    }

    /**
     * Display a single note.
     */
    public function view(\Smarty $tpl, string $noteId, int $userId): void
    {
        try {
            $query = new GetNoteQuery(
                noteId: $noteId,
                userId: $userId
            );

            $note = $this->getNoteHandler->handle($query);

            $tpl->assign('note', $this->formatNote($note));
        } catch (NoteException $e) {
            $tpl->assign('error', $e->getMessage());
        }
    }

    /**
     * Create a new note.
     *
     * @return array{success: bool, note?: array, error?: string}
     */
    public function create(int $userId, string $title, string $content): array
    {
        try {
            $command = new CreateNoteCommand(
                userId: $userId,
                title: $title,
                content: $content
            );

            $note = $this->createHandler->handle($command);

            return [
                'success' => true,
                'note' => $this->formatNote($note),
            ];
        } catch (NoteException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing note.
     */
    public function update(
        string $noteId,
        int $userId,
        ?string $title,
        ?string $content
    ): array {
        try {
            $command = new UpdateNoteCommand(
                noteId: $noteId,
                userId: $userId,
                title: $title,
                content: $content
            );

            $note = $this->updateHandler->handle($command);

            return [
                'success' => true,
                'note' => $this->formatNote($note),
            ];
        } catch (NoteException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format a note for template/API output.
     */
    private function formatNote($note): array
    {
        return [
            'id' => $note->getId()->toString(),
            'title' => $note->getTitle()->toString(),
            'content' => $note->getContent()->toString(),
            'preview' => $note->getContent()->getPreview(),
            'wordCount' => $note->getContent()->getWordCount(),
            'isArchived' => $note->isArchived(),
            'createdAt' => $note->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $note->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format multiple notes.
     */
    private function formatNotes(array $notes): array
    {
        return array_map([$this, 'formatNote'], $notes);
    }
}
```

### Step 16: Create Templates

Create `templates/notes_index.tpl`:

```smarty
<{include file='db:notes_header.tpl'}>

<div class="notes-container">
    <div class="notes-header">
        <h2><{$smarty.const._MD_NOTES_MY_NOTES}></h2>
        <a href="<{$xoops_url}>/modules/notes/edit.php" class="btn btn-primary">
            <{$smarty.const._MD_NOTES_CREATE_NOTE}>
        </a>
    </div>

    <{if $totalNotes == 0}>
        <div class="notes-empty">
            <p><{$smarty.const._MD_NOTES_NO_NOTES}></p>
            <p><{$smarty.const._MD_NOTES_CREATE_FIRST}></p>
        </div>
    <{else}>
        <div class="notes-list">
            <{foreach from=$notes item=note}>
                <article class="note-card<{if $note.isArchived}> note-archived<{/if}>">
                    <h3 class="note-title">
                        <a href="<{$xoops_url}>/modules/notes/view.php?id=<{$note.id}>">
                            <{$note.title}>
                        </a>
                    </h3>
                    <p class="note-preview"><{$note.preview}></p>
                    <footer class="note-meta">
                        <span class="note-date">
                            <{$note.updatedAt|date_format:"%b %d, %Y"}>
                        </span>
                        <span class="note-words">
                            <{$note.wordCount}> <{$smarty.const._MD_NOTES_WORDS}>
                        </span>
                    </footer>
                </article>
            <{/foreach}>
        </div>
    <{/if}>
</div>

<{include file='db:notes_footer.tpl'}>
```

---

## Part 6: Wiring It Together

### Step 17: Create the Service Container

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Xoops;

use Notes\Application\Command\CreateNoteHandler;
use Notes\Application\Command\UpdateNoteHandler;
use Notes\Application\Query\GetNoteHandler;
use Notes\Application\Query\GetUserNotesHandler;
use Notes\Infrastructure\Persistence\MySqlNoteRepository;
use Notes\Presentation\Controller\NoteController;

/**
 * Container - Simple dependency injection container.
 */
final class Container
{
    private array $services = [];

    public function __construct(
        private readonly \XoopsDatabase $db
    ) {}

    public function getNoteRepository(): MySqlNoteRepository
    {
        return $this->services[MySqlNoteRepository::class] ??=
            new MySqlNoteRepository($this->db);
    }

    public function getCreateNoteHandler(): CreateNoteHandler
    {
        return $this->services[CreateNoteHandler::class] ??=
            new CreateNoteHandler($this->getNoteRepository());
    }

    public function getUpdateNoteHandler(): UpdateNoteHandler
    {
        return $this->services[UpdateNoteHandler::class] ??=
            new UpdateNoteHandler($this->getNoteRepository());
    }

    public function getGetNoteHandler(): GetNoteHandler
    {
        return $this->services[GetNoteHandler::class] ??=
            new GetNoteHandler($this->getNoteRepository());
    }

    public function getGetUserNotesHandler(): GetUserNotesHandler
    {
        return $this->services[GetUserNotesHandler::class] ??=
            new GetUserNotesHandler($this->getNoteRepository());
    }

    public function getNoteController(): NoteController
    {
        return $this->services[NoteController::class] ??=
            new NoteController(
                $this->getCreateNoteHandler(),
                $this->getUpdateNoteHandler(),
                $this->getGetNoteHandler(),
                $this->getGetUserNotesHandler()
            );
    }
}
```

### Step 18: Create the Main Entry Point

Create `index.php`:

```php
<?php

declare(strict_types=1);

/**
 * Notes Module - Main Index
 */

use Notes\Infrastructure\Xoops\Container;

require_once dirname(__DIR__, 2) . '/mainfile.php';

// Initialize XOOPS
$GLOBALS['xoopsOption']['template_main'] = 'notes_index.tpl';
require_once XOOPS_ROOT_PATH . '/header.php';

// Check authentication
if (!$xoopsUser) {
    redirect_header(XOOPS_URL . '/user.php', 3, _NOPERM);
    exit;
}

// Bootstrap the module
$container = new Container($GLOBALS['xoopsDB']);
$controller = $container->getNoteController();

// Handle the request
$controller->index($xoopsTpl, $xoopsUser->uid());

require_once XOOPS_ROOT_PATH . '/footer.php';
```

---

## Part 7: Testing Your Module

### Step 19: Create Unit Tests

Create `tests/Domain/ValueObject/NoteTitleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Notes\Tests\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Notes\Domain\ValueObject\NoteTitle;
use Notes\Domain\Exception\InvalidNoteTitle;

final class NoteTitleTest extends TestCase
{
    #[Test]
    public function it_creates_valid_title(): void
    {
        $title = NoteTitle::create('My First Note');

        $this->assertSame('My First Note', $title->toString());
    }

    #[Test]
    public function it_trims_whitespace(): void
    {
        $title = NoteTitle::create('  Trimmed Title  ');

        $this->assertSame('Trimmed Title', $title->toString());
    }

    #[Test]
    public function it_rejects_empty_title(): void
    {
        $this->expectException(InvalidNoteTitle::class);

        NoteTitle::create('');
    }

    #[Test]
    public function it_rejects_whitespace_only(): void
    {
        $this->expectException(InvalidNoteTitle::class);

        NoteTitle::create('   ');
    }

    #[Test]
    public function it_rejects_too_long_title(): void
    {
        $this->expectException(InvalidNoteTitle::class);

        NoteTitle::create(str_repeat('a', 201));
    }

    #[Test]
    public function it_allows_max_length(): void
    {
        $title = NoteTitle::create(str_repeat('a', 200));

        $this->assertSame(200, mb_strlen($title->toString()));
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $title1 = NoteTitle::create('Test');
        $title2 = NoteTitle::create('Test');
        $title3 = NoteTitle::create('Different');

        $this->assertTrue($title1->equals($title2));
        $this->assertFalse($title1->equals($title3));
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $title = NoteTitle::create('Stringable Test');

        $this->assertSame('Stringable Test', (string) $title);
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $title = NoteTitle::create('JSON Test');

        $this->assertSame('"JSON Test"', json_encode($title));
    }
}
```

### Step 20: Run Tests

```bash
cd modules/notes
composer install
./vendor/bin/phpunit
```

---

## Summary

Congratulations! You've built a complete XOOPS module using modern PHP practices:

### What You've Learned

1. **Domain-Driven Design**
   - Entities contain business logic
   - Value Objects ensure data validity
   - Repositories abstract persistence
   - Domain Exceptions provide meaningful errors

2. **Clean Architecture**
   - Domain layer has no dependencies
   - Application layer orchestrates use cases
   - Infrastructure provides implementations
   - Presentation handles user interaction

3. **XMF Components**
   - ULID for time-sortable identifiers
   - Slug for URL-friendly strings (when needed)
   - EntityId trait for reducing boilerplate

4. **Best Practices**
   - Immutable value objects
   - Type-safe code with PHP 8.4+
   - Comprehensive testing
   - Clear separation of concerns

### Next Steps

- Add tag management functionality
- Implement search capabilities
- Add REST API endpoints
- Create admin interface
- Add block functionality
- Implement notifications

### Related Documentation

- [[../Implementation-Guides/XMF-Components-Guide]]
- [Error Handling & Validation](../Implementation-Guides/Error-Handling-Validation-Guide.md)
- [Repository & Query Patterns](../Implementation-Guides/Repository-Query-Patterns-Guide.md)
- [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md)
- [Entity Mapping & Database Patterns](../Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md)
