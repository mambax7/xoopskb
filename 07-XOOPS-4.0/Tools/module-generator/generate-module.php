#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * XOOPS 4.0 Module Generator
 *
 * Generates a new module with Clean Architecture structure,
 * DDD patterns, and all boilerplate files.
 *
 * Usage:
 *   php generate-module.php <module-name> [options]
 *
 * Options:
 *   --entity=<name>    Primary entity name (default: derived from module name)
 *   --author=<name>    Author name for headers
 *   --output=<path>    Output directory (default: current directory)
 *   --with-api         Include REST API scaffolding
 *   --with-admin       Include admin interface scaffolding
 *   --with-blocks      Include block scaffolding
 *   --help             Show this help message
 *
 * Examples:
 *   php generate-module.php articles
 *   php generate-module.php blog --entity=Post --with-api --with-admin
 *   php generate-module.php inventory --author="John Doe" --output=/var/www/xoops/modules
 */

namespace XoopsModuleGenerator;

// ============================================================================
// Configuration
// ============================================================================

final class Config
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $moduleNameLower,
        public readonly string $moduleNameUpper,
        public readonly string $entityName,
        public readonly string $entityNameLower,
        public readonly string $entityNamePlural,
        public readonly string $author,
        public readonly string $outputPath,
        public readonly bool $withApi,
        public readonly bool $withAdmin,
        public readonly bool $withBlocks,
        public readonly string $namespace
    ) {}

    public static function fromArgs(array $args): self
    {
        $options = self::parseArgs($args);

        if (isset($options['help']) || empty($options['_positional'])) {
            self::showHelp();
            exit(0);
        }

        $moduleName = $options['_positional'][0];
        $moduleNameLower = strtolower($moduleName);
        $entityName = $options['entity'] ?? self::singularize(ucfirst($moduleName));

        return new self(
            moduleName: ucfirst($moduleNameLower),
            moduleNameLower: $moduleNameLower,
            moduleNameUpper: strtoupper($moduleNameLower),
            entityName: ucfirst($entityName),
            entityNameLower: strtolower($entityName),
            entityNamePlural: self::pluralize($entityName),
            author: $options['author'] ?? 'XOOPS Developer',
            outputPath: rtrim($options['output'] ?? getcwd(), '/'),
            withApi: isset($options['with-api']),
            withAdmin: isset($options['with-admin']),
            withBlocks: isset($options['with-blocks']),
            namespace: ucfirst($moduleNameLower)
        );
    }

    private static function parseArgs(array $args): array
    {
        $options = ['_positional' => []];

        // Skip script name
        array_shift($args);

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2);
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $options[$key] = $value;
                } else {
                    $options[$arg] = true;
                }
            } else {
                $options['_positional'][] = $arg;
            }
        }

        return $options;
    }

    private static function singularize(string $word): string
    {
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }
        if (str_ends_with($word, 'es')) {
            return substr($word, 0, -2);
        }
        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }
        return $word;
    }

    private static function pluralize(string $word): string
    {
        if (str_ends_with($word, 'y')) {
            return substr($word, 0, -1) . 'ies';
        }
        if (str_ends_with($word, 's') || str_ends_with($word, 'x') ||
            str_ends_with($word, 'ch') || str_ends_with($word, 'sh')) {
            return $word . 'es';
        }
        return $word . 's';
    }

    private static function showHelp(): void
    {
        echo <<<'HELP'
XOOPS 4.0 Module Generator

Generates a new module with Clean Architecture structure,
DDD patterns, and all boilerplate files.

Usage:
  php generate-module.php <module-name> [options]

Options:
  --entity=<name>    Primary entity name (default: derived from module name)
  --author=<name>    Author name for headers
  --output=<path>    Output directory (default: current directory)
  --with-api         Include REST API scaffolding
  --with-admin       Include admin interface scaffolding
  --with-blocks      Include block scaffolding
  --help             Show this help message

Examples:
  php generate-module.php articles
  php generate-module.php blog --entity=Post --with-api --with-admin
  php generate-module.php inventory --author="John Doe" --output=/var/www/xoops/modules

HELP;
    }
}

// ============================================================================
// File Generator
// ============================================================================

final class FileGenerator
{
    private int $filesCreated = 0;
    private int $dirsCreated = 0;

    public function __construct(
        private readonly Config $config
    ) {}

    public function generate(): void
    {
        $basePath = $this->config->outputPath . '/' . $this->config->moduleNameLower;

        $this->output("\n🚀 Generating XOOPS 4.0 module: {$this->config->moduleName}\n");
        $this->output("   Output: {$basePath}\n\n");

        // Create directory structure
        $this->createDirectories($basePath);

        // Generate files
        $this->generateDomainLayer($basePath);
        $this->generateApplicationLayer($basePath);
        $this->generateInfrastructureLayer($basePath);
        $this->generatePresentationLayer($basePath);
        $this->generateConfigFiles($basePath);
        $this->generateSqlSchema($basePath);

        if ($this->config->withApi) {
            $this->generateApiLayer($basePath);
        }

        if ($this->config->withAdmin) {
            $this->generateAdminLayer($basePath);
        }

        if ($this->config->withBlocks) {
            $this->generateBlocks($basePath);
        }

        $this->output("\n✅ Module generated successfully!\n");
        $this->output("   Files created: {$this->filesCreated}\n");
        $this->output("   Directories created: {$this->dirsCreated}\n\n");
    }

    private function createDirectories(string $basePath): void
    {
        $dirs = [
            '',
            'Domain',
            'Domain/Entity',
            'Domain/ValueObject',
            'Domain/Repository',
            'Domain/Exception',
            'Application',
            'Application/Command',
            'Application/Query',
            'Infrastructure',
            'Infrastructure/Persistence',
            'Infrastructure/Xoops',
            'Presentation',
            'Presentation/Controller',
            'Presentation/templates',
            'sql',
            'language',
            'language/english',
        ];

        if ($this->config->withApi) {
            $dirs[] = 'api';
            $dirs[] = 'api/v1';
            $dirs[] = 'Infrastructure/Api';
            $dirs[] = 'Infrastructure/Api/Controller';
        }

        if ($this->config->withAdmin) {
            $dirs[] = 'admin';
            $dirs[] = 'Presentation/templates/admin';
        }

        if ($this->config->withBlocks) {
            $dirs[] = 'blocks';
            $dirs[] = 'Presentation/templates/blocks';
        }

        foreach ($dirs as $dir) {
            $path = $basePath . ($dir ? '/' . $dir : '');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                $this->dirsCreated++;
                $this->output("  📁 Created: {$dir}/\n");
            }
        }
    }

    // ========================================================================
    // Domain Layer
    // ========================================================================

    private function generateDomainLayer(string $basePath): void
    {
        $this->output("\n📦 Generating Domain Layer...\n");

        // Entity ID Value Object
        $this->writeFile("{$basePath}/Domain/ValueObject/{$this->config->entityName}Id.php",
            $this->template('entity_id'));

        // Title Value Object
        $this->writeFile("{$basePath}/Domain/ValueObject/{$this->config->entityName}Title.php",
            $this->template('entity_title'));

        // Content Value Object
        $this->writeFile("{$basePath}/Domain/ValueObject/{$this->config->entityName}Content.php",
            $this->template('entity_content'));

        // Status Enum
        $this->writeFile("{$basePath}/Domain/ValueObject/{$this->config->entityName}Status.php",
            $this->template('entity_status'));

        // Main Entity
        $this->writeFile("{$basePath}/Domain/Entity/{$this->config->entityName}.php",
            $this->template('entity'));

        // Repository Interface
        $this->writeFile("{$basePath}/Domain/Repository/{$this->config->entityName}RepositoryInterface.php",
            $this->template('repository_interface'));

        // Exceptions
        $this->writeFile("{$basePath}/Domain/Exception/{$this->config->entityName}Exception.php",
            $this->template('exceptions'));
    }

    // ========================================================================
    // Application Layer
    // ========================================================================

    private function generateApplicationLayer(string $basePath): void
    {
        $this->output("\n📦 Generating Application Layer...\n");

        // Create Command
        $this->writeFile("{$basePath}/Application/Command/Create{$this->config->entityName}Command.php",
            $this->template('create_command'));

        // Create Handler
        $this->writeFile("{$basePath}/Application/Command/Create{$this->config->entityName}Handler.php",
            $this->template('create_handler'));

        // Update Command
        $this->writeFile("{$basePath}/Application/Command/Update{$this->config->entityName}Command.php",
            $this->template('update_command'));

        // Update Handler
        $this->writeFile("{$basePath}/Application/Command/Update{$this->config->entityName}Handler.php",
            $this->template('update_handler'));

        // Delete Command
        $this->writeFile("{$basePath}/Application/Command/Delete{$this->config->entityName}Command.php",
            $this->template('delete_command'));

        // Delete Handler
        $this->writeFile("{$basePath}/Application/Command/Delete{$this->config->entityName}Handler.php",
            $this->template('delete_handler'));

        // Get Query
        $this->writeFile("{$basePath}/Application/Query/Get{$this->config->entityName}Query.php",
            $this->template('get_query'));

        // Get Handler
        $this->writeFile("{$basePath}/Application/Query/Get{$this->config->entityName}Handler.php",
            $this->template('get_handler'));

        // List Query
        $this->writeFile("{$basePath}/Application/Query/List{$this->config->entityNamePlural}Query.php",
            $this->template('list_query'));

        // List Handler
        $this->writeFile("{$basePath}/Application/Query/List{$this->config->entityNamePlural}Handler.php",
            $this->template('list_handler'));
    }

    // ========================================================================
    // Infrastructure Layer
    // ========================================================================

    private function generateInfrastructureLayer(string $basePath): void
    {
        $this->output("\n📦 Generating Infrastructure Layer...\n");

        // MySQL Repository
        $this->writeFile("{$basePath}/Infrastructure/Persistence/MySql{$this->config->entityName}Repository.php",
            $this->template('mysql_repository'));

        // Service Container
        $this->writeFile("{$basePath}/Infrastructure/Xoops/Container.php",
            $this->template('container'));
    }

    // ========================================================================
    // Presentation Layer
    // ========================================================================

    private function generatePresentationLayer(string $basePath): void
    {
        $this->output("\n📦 Generating Presentation Layer...\n");

        // Controller
        $this->writeFile("{$basePath}/Presentation/Controller/{$this->config->entityName}Controller.php",
            $this->template('controller'));

        // Templates
        $this->writeFile("{$basePath}/Presentation/templates/{$this->config->moduleNameLower}_index.tpl",
            $this->template('template_index'));

        $this->writeFile("{$basePath}/Presentation/templates/{$this->config->moduleNameLower}_view.tpl",
            $this->template('template_view'));

        $this->writeFile("{$basePath}/Presentation/templates/{$this->config->moduleNameLower}_form.tpl",
            $this->template('template_form'));

        // Main entry points
        $this->writeFile("{$basePath}/index.php",
            $this->template('index_php'));

        $this->writeFile("{$basePath}/view.php",
            $this->template('view_php'));
    }

    // ========================================================================
    // Config Files
    // ========================================================================

    private function generateConfigFiles(string $basePath): void
    {
        $this->output("\n📦 Generating Config Files...\n");

        // xoops_version.php
        $this->writeFile("{$basePath}/xoops_version.php",
            $this->template('xoops_version'));

        // composer.json
        $this->writeFile("{$basePath}/composer.json",
            $this->template('composer_json'));

        // Language files
        $this->writeFile("{$basePath}/language/english/main.php",
            $this->template('language_main'));

        $this->writeFile("{$basePath}/language/english/modinfo.php",
            $this->template('language_modinfo'));

        // README
        $this->writeFile("{$basePath}/README.md",
            $this->template('readme'));
    }

    // ========================================================================
    // SQL Schema
    // ========================================================================

    private function generateSqlSchema(string $basePath): void
    {
        $this->output("\n📦 Generating SQL Schema...\n");

        $this->writeFile("{$basePath}/sql/mysql.sql",
            $this->template('mysql_schema'));
    }

    // ========================================================================
    // API Layer (optional)
    // ========================================================================

    private function generateApiLayer(string $basePath): void
    {
        $this->output("\n📦 Generating API Layer...\n");

        // API Controller
        $this->writeFile("{$basePath}/Infrastructure/Api/Controller/{$this->config->entityNamePlural}ApiController.php",
            $this->template('api_controller'));

        // API Entry Point
        $this->writeFile("{$basePath}/api/v1/index.php",
            $this->template('api_index'));

        // .htaccess
        $this->writeFile("{$basePath}/api/v1/.htaccess",
            $this->template('api_htaccess'));

        // OpenAPI spec
        $this->writeFile("{$basePath}/api/v1/openapi.yaml",
            $this->template('openapi'));
    }

    // ========================================================================
    // Admin Layer (optional)
    // ========================================================================

    private function generateAdminLayer(string $basePath): void
    {
        $this->output("\n📦 Generating Admin Layer...\n");

        $this->writeFile("{$basePath}/admin/index.php",
            $this->template('admin_index'));

        $this->writeFile("{$basePath}/admin/menu.php",
            $this->template('admin_menu'));

        $this->writeFile("{$basePath}/Presentation/templates/admin/{$this->config->moduleNameLower}_admin_index.tpl",
            $this->template('admin_template'));

        // Add admin language
        $this->writeFile("{$basePath}/language/english/admin.php",
            $this->template('language_admin'));
    }

    // ========================================================================
    // Blocks (optional)
    // ========================================================================

    private function generateBlocks(string $basePath): void
    {
        $this->output("\n📦 Generating Blocks...\n");

        $this->writeFile("{$basePath}/blocks/blocks.php",
            $this->template('blocks_php'));

        $this->writeFile("{$basePath}/Presentation/templates/blocks/{$this->config->moduleNameLower}_block_recent.tpl",
            $this->template('block_template'));

        // Add block language
        $this->writeFile("{$basePath}/language/english/blocks.php",
            $this->template('language_blocks'));
    }

    // ========================================================================
    // Template Engine
    // ========================================================================

    private function template(string $name): string
    {
        $method = 'template' . str_replace('_', '', ucwords($name, '_'));

        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Template not found: {$name}");
        }

        return $this->$method();
    }

    private function replace(string $content): string
    {
        $replacements = [
            '{{MODULE_NAME}}' => $this->config->moduleName,
            '{{MODULE_NAME_LOWER}}' => $this->config->moduleNameLower,
            '{{MODULE_NAME_UPPER}}' => $this->config->moduleNameUpper,
            '{{ENTITY_NAME}}' => $this->config->entityName,
            '{{ENTITY_NAME_LOWER}}' => $this->config->entityNameLower,
            '{{ENTITY_NAME_PLURAL}}' => $this->config->entityNamePlural,
            '{{ENTITY_NAME_PLURAL_LOWER}}' => strtolower($this->config->entityNamePlural),
            '{{NAMESPACE}}' => $this->config->namespace,
            '{{AUTHOR}}' => $this->config->author,
            '{{DATE}}' => date('Y-m-d'),
            '{{YEAR}}' => date('Y'),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }

    private function writeFile(string $path, string $content): void
    {
        file_put_contents($path, $this->replace($content));
        $this->filesCreated++;
        $relativePath = str_replace($this->config->outputPath . '/', '', $path);
        $this->output("  📄 Created: {$relativePath}\n");
    }

    private function output(string $message): void
    {
        echo $message;
    }

    // ========================================================================
    // Templates
    // ========================================================================

    private function templateEntityId(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\ValueObject;

use Xmf\Ulid;
use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Id;

/**
 * {{ENTITY_NAME}}Id - Unique identifier for a {{ENTITY_NAME}} entity.
 */
final readonly class {{ENTITY_NAME}}Id implements \Stringable, \JsonSerializable
{
    private function __construct(
        private Ulid $ulid
    ) {}

    public static function generate(): self
    {
        return new self(Ulid::generate());
    }

    public static function fromString(string $id): self
    {
        if (!Ulid::isValid($id)) {
            throw Invalid{{ENTITY_NAME}}Id::invalidFormat($id);
        }

        return new self(Ulid::fromString($id));
    }

    public function toString(): string
    {
        return $this->ulid->toString();
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->ulid->getTimestamp();
    }

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
PHP;
    }

    private function templateEntityTitle(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\ValueObject;

use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Title;

/**
 * {{ENTITY_NAME}}Title - The title of a {{ENTITY_NAME_LOWER}}.
 */
final readonly class {{ENTITY_NAME}}Title implements \Stringable, \JsonSerializable
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
            throw Invalid{{ENTITY_NAME}}Title::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($title) > self::MAX_LENGTH) {
            throw Invalid{{ENTITY_NAME}}Title::tooLong(self::MAX_LENGTH);
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
PHP;
    }

    private function templateEntityContent(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\ValueObject;

use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Content;

/**
 * {{ENTITY_NAME}}Content - The body content of a {{ENTITY_NAME_LOWER}}.
 */
final readonly class {{ENTITY_NAME}}Content implements \Stringable, \JsonSerializable
{
    private const int MAX_LENGTH = 50_000;

    private function __construct(
        private string $value
    ) {}

    public static function create(string $content): self
    {
        if (mb_strlen($content) > self::MAX_LENGTH) {
            throw Invalid{{ENTITY_NAME}}Content::tooLong(self::MAX_LENGTH);
        }

        return new self($content);
    }

    public static function empty(): self
    {
        return new self('');
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function getWordCount(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return str_word_count($this->value);
    }

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
PHP;
    }

    private function templateEntityStatus(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\ValueObject;

/**
 * {{ENTITY_NAME}}Status - Lifecycle status of a {{ENTITY_NAME_LOWER}}.
 */
enum {{ENTITY_NAME}}Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Get allowed transitions from this status.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Archived],
            self::Published => [self::Archived],
            self::Archived => [self::Draft],
        };
    }

    /**
     * Check if transition to target status is allowed.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
PHP;
    }

    private function templateEntity(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\Entity;

use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;
use {{NAMESPACE}}\Domain\Exception\InvalidStatusTransition;

/**
 * {{ENTITY_NAME}} - The core domain entity.
 */
final class {{ENTITY_NAME}}
{
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly {{ENTITY_NAME}}Id $id,
        private readonly int $authorId,
        private {{ENTITY_NAME}}Title $title,
        private {{ENTITY_NAME}}Content $content,
        private {{ENTITY_NAME}}Status $status,
        private readonly \DateTimeImmutable $createdAt
    ) {
        $this->updatedAt = $createdAt;
    }

    /**
     * Create a new {{ENTITY_NAME}}.
     */
    public static function create(
        int $authorId,
        {{ENTITY_NAME}}Title $title,
        {{ENTITY_NAME}}Content $content
    ): self {
        return new self(
            id: {{ENTITY_NAME}}Id::generate(),
            authorId: $authorId,
            title: $title,
            content: $content,
            status: {{ENTITY_NAME}}Status::Draft,
            createdAt: new \DateTimeImmutable()
        );
    }

    /**
     * Reconstitute from persistence.
     */
    public static function reconstitute(
        {{ENTITY_NAME}}Id $id,
        int $authorId,
        {{ENTITY_NAME}}Title $title,
        {{ENTITY_NAME}}Content $content,
        {{ENTITY_NAME}}Status $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt
    ): self {
        $entity = new self($id, $authorId, $title, $content, $status, $createdAt);
        $entity->updatedAt = $updatedAt;

        return $entity;
    }

    // === Getters ===

    public function getId(): {{ENTITY_NAME}}Id
    {
        return $this->id;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getTitle(): {{ENTITY_NAME}}Title
    {
        return $this->title;
    }

    public function getContent(): {{ENTITY_NAME}}Content
    {
        return $this->content;
    }

    public function getStatus(): {{ENTITY_NAME}}Status
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // === Domain Behaviors ===

    public function updateTitle({{ENTITY_NAME}}Title $newTitle): void
    {
        if ($this->title->equals($newTitle)) {
            return;
        }

        $this->title = $newTitle;
        $this->touch();
    }

    public function updateContent({{ENTITY_NAME}}Content $newContent): void
    {
        if ($this->content->equals($newContent)) {
            return;
        }

        $this->content = $newContent;
        $this->touch();
    }

    public function publish(): void
    {
        $this->transitionTo({{ENTITY_NAME}}Status::Published);
    }

    public function archive(): void
    {
        $this->transitionTo({{ENTITY_NAME}}Status::Archived);
    }

    public function restore(): void
    {
        $this->transitionTo({{ENTITY_NAME}}Status::Draft);
    }

    public function canBeEditedBy(int $userId): bool
    {
        return $this->authorId === $userId;
    }

    public function isPublished(): bool
    {
        return $this->status === {{ENTITY_NAME}}Status::Published;
    }

    public function isArchived(): bool
    {
        return $this->status === {{ENTITY_NAME}}Status::Archived;
    }

    private function transitionTo({{ENTITY_NAME}}Status $newStatus): void
    {
        if ($this->status === $newStatus) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidStatusTransition::create($this->status, $newStatus);
        }

        $this->status = $newStatus;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
PHP;
    }

    private function templateRepositoryInterface(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\Repository;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;

/**
 * {{ENTITY_NAME}}RepositoryInterface - Persistence contract.
 */
interface {{ENTITY_NAME}}RepositoryInterface
{
    /**
     * Find by ID or throw exception.
     *
     * @throws \{{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound
     */
    public function findById({{ENTITY_NAME}}Id $id): {{ENTITY_NAME}};

    /**
     * Find by ID or return null.
     */
    public function findByIdOrNull({{ENTITY_NAME}}Id $id): ?{{ENTITY_NAME}};

    /**
     * Find all by author.
     *
     * @return {{ENTITY_NAME}}[]
     */
    public function findByAuthorId(int $authorId): array;

    /**
     * Save (insert or update).
     */
    public function save({{ENTITY_NAME}} $entity): void;

    /**
     * Delete permanently.
     */
    public function delete({{ENTITY_NAME}} $entity): void;

    /**
     * Check if exists.
     */
    public function exists({{ENTITY_NAME}}Id $id): bool;

    /**
     * Count by author.
     */
    public function countByAuthorId(int $authorId): int;
}
PHP;
    }

    private function templateExceptions(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Domain\Exception;

use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;

/**
 * Base exception for {{ENTITY_NAME}} domain errors.
 */
abstract class {{ENTITY_NAME}}Exception extends \DomainException
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

final class Invalid{{ENTITY_NAME}}Id extends {{ENTITY_NAME}}Exception
{
    public static function invalidFormat(string $value): self
    {
        return new self(
            "Invalid {{ENTITY_NAME_LOWER}} ID format: '{$value}'",
            'INVALID_{{MODULE_NAME_UPPER}}_ID_FORMAT',
            ['value' => $value]
        );
    }
}

final class Invalid{{ENTITY_NAME}}Title extends {{ENTITY_NAME}}Exception
{
    public static function tooShort(int $minLength): self
    {
        return new self(
            "Title must be at least {$minLength} character(s)",
            '{{MODULE_NAME_UPPER}}_TITLE_TOO_SHORT',
            ['min_length' => $minLength]
        );
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(
            "Title cannot exceed {$maxLength} characters",
            '{{MODULE_NAME_UPPER}}_TITLE_TOO_LONG',
            ['max_length' => $maxLength]
        );
    }
}

final class Invalid{{ENTITY_NAME}}Content extends {{ENTITY_NAME}}Exception
{
    public static function tooLong(int $maxLength): self
    {
        return new self(
            "Content cannot exceed {$maxLength} characters",
            '{{MODULE_NAME_UPPER}}_CONTENT_TOO_LONG',
            ['max_length' => $maxLength]
        );
    }
}

final class {{ENTITY_NAME}}NotFound extends {{ENTITY_NAME}}Exception
{
    public static function withId({{ENTITY_NAME}}Id $id): self
    {
        return new self(
            "{{ENTITY_NAME}} not found with ID: {$id}",
            '{{MODULE_NAME_UPPER}}_NOT_FOUND',
            ['id' => $id->toString()]
        );
    }
}

final class InvalidStatusTransition extends {{ENTITY_NAME}}Exception
{
    public static function create({{ENTITY_NAME}}Status $from, {{ENTITY_NAME}}Status $to): self
    {
        return new self(
            "Cannot transition from '{$from->value}' to '{$to->value}'",
            '{{MODULE_NAME_UPPER}}_INVALID_STATUS_TRANSITION',
            ['from' => $from->value, 'to' => $to->value]
        );
    }
}
PHP;
    }

    private function templateCreateCommand(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Command;

/**
 * Create{{ENTITY_NAME}}Command - Request to create a new {{ENTITY_NAME_LOWER}}.
 */
final readonly class Create{{ENTITY_NAME}}Command
{
    public function __construct(
        public int $authorId,
        public string $title,
        public string $content = ''
    ) {}
}
PHP;
    }

    private function templateCreateHandler(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Command;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;

/**
 * Create{{ENTITY_NAME}}Handler - Executes the create use case.
 */
final readonly class Create{{ENTITY_NAME}}Handler
{
    public function __construct(
        private {{ENTITY_NAME}}RepositoryInterface $repository
    ) {}

    public function handle(Create{{ENTITY_NAME}}Command $command): {{ENTITY_NAME}}
    {
        $title = {{ENTITY_NAME}}Title::create($command->title);
        $content = {{ENTITY_NAME}}Content::create($command->content);

        $entity = {{ENTITY_NAME}}::create(
            authorId: $command->authorId,
            title: $title,
            content: $content
        );

        $this->repository->save($entity);

        return $entity;
    }
}
PHP;
    }

    private function templateUpdateCommand(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Command;

/**
 * Update{{ENTITY_NAME}}Command - Request to update an existing {{ENTITY_NAME_LOWER}}.
 */
final readonly class Update{{ENTITY_NAME}}Command
{
    public function __construct(
        public string $id,
        public int $userId,
        public ?string $title = null,
        public ?string $content = null
    ) {}
}
PHP;
    }

    private function templateUpdateHandler(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Command;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;

/**
 * Update{{ENTITY_NAME}}Handler - Executes the update use case.
 */
final readonly class Update{{ENTITY_NAME}}Handler
{
    public function __construct(
        private {{ENTITY_NAME}}RepositoryInterface $repository
    ) {}

    public function handle(Update{{ENTITY_NAME}}Command $command): {{ENTITY_NAME}}
    {
        $id = {{ENTITY_NAME}}Id::fromString($command->id);
        $entity = $this->repository->findById($id);

        if (!$entity->canBeEditedBy($command->userId)) {
            throw new \DomainException('You cannot edit this {{ENTITY_NAME_LOWER}}');
        }

        if ($command->title !== null) {
            $entity->updateTitle({{ENTITY_NAME}}Title::create($command->title));
        }

        if ($command->content !== null) {
            $entity->updateContent({{ENTITY_NAME}}Content::create($command->content));
        }

        $this->repository->save($entity);

        return $entity;
    }
}
PHP;
    }

    private function templateDeleteCommand(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Command;

/**
 * Delete{{ENTITY_NAME}}Command - Request to delete a {{ENTITY_NAME_LOWER}}.
 */
final readonly class Delete{{ENTITY_NAME}}Command
{
    public function __construct(
        public string $id,
        public int $userId
    ) {}
}
PHP;
    }

    private function templateDeleteHandler(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Command;

use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;

/**
 * Delete{{ENTITY_NAME}}Handler - Executes the delete use case.
 */
final readonly class Delete{{ENTITY_NAME}}Handler
{
    public function __construct(
        private {{ENTITY_NAME}}RepositoryInterface $repository
    ) {}

    public function handle(Delete{{ENTITY_NAME}}Command $command): void
    {
        $id = {{ENTITY_NAME}}Id::fromString($command->id);
        $entity = $this->repository->findById($id);

        if (!$entity->canBeEditedBy($command->userId)) {
            throw new \DomainException('You cannot delete this {{ENTITY_NAME_LOWER}}');
        }

        $this->repository->delete($entity);
    }
}
PHP;
    }

    private function templateGetQuery(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Query;

/**
 * Get{{ENTITY_NAME}}Query - Request to retrieve a single {{ENTITY_NAME_LOWER}}.
 */
final readonly class Get{{ENTITY_NAME}}Query
{
    public function __construct(
        public string $id,
        public int $userId
    ) {}
}
PHP;
    }

    private function templateGetHandler(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Query;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;

/**
 * Get{{ENTITY_NAME}}Handler - Retrieves a single {{ENTITY_NAME_LOWER}}.
 */
final readonly class Get{{ENTITY_NAME}}Handler
{
    public function __construct(
        private {{ENTITY_NAME}}RepositoryInterface $repository
    ) {}

    public function handle(Get{{ENTITY_NAME}}Query $query): {{ENTITY_NAME}}
    {
        $id = {{ENTITY_NAME}}Id::fromString($query->id);
        $entity = $this->repository->findById($id);

        if (!$entity->canBeEditedBy($query->userId)) {
            throw new \DomainException('You cannot access this {{ENTITY_NAME_LOWER}}');
        }

        return $entity;
    }
}
PHP;
    }

    private function templateListQuery(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Query;

/**
 * List{{ENTITY_NAME_PLURAL}}Query - Request to list {{ENTITY_NAME_PLURAL_LOWER}}.
 */
final readonly class List{{ENTITY_NAME_PLURAL}}Query
{
    public function __construct(
        public int $userId,
        public int $limit = 50,
        public int $offset = 0
    ) {}
}
PHP;
    }

    private function templateListHandler(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Application\Query;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;

/**
 * List{{ENTITY_NAME_PLURAL}}Handler - Retrieves {{ENTITY_NAME_PLURAL_LOWER}} for a user.
 */
final readonly class List{{ENTITY_NAME_PLURAL}}Handler
{
    public function __construct(
        private {{ENTITY_NAME}}RepositoryInterface $repository
    ) {}

    /**
     * @return {{ENTITY_NAME}}[]
     */
    public function handle(List{{ENTITY_NAME_PLURAL}}Query $query): array
    {
        return $this->repository->findByAuthorId($query->userId);
    }

    public function count(List{{ENTITY_NAME_PLURAL}}Query $query): int
    {
        return $this->repository->countByAuthorId($query->userId);
    }
}
PHP;
    }

    private function templateMysqlRepository(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Infrastructure\Persistence;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound;
use XoopsDatabase;

/**
 * MySql{{ENTITY_NAME}}Repository - MySQL implementation.
 */
final class MySql{{ENTITY_NAME}}Repository implements {{ENTITY_NAME}}RepositoryInterface
{
    private const TABLE = '{{MODULE_NAME_LOWER}}_{{ENTITY_NAME_LOWER}}';

    public function __construct(
        private readonly XoopsDatabase $db
    ) {}

    public function findById({{ENTITY_NAME}}Id $id): {{ENTITY_NAME}}
    {
        $entity = $this->findByIdOrNull($id);

        if ($entity === null) {
            throw {{ENTITY_NAME}}NotFound::withId($id);
        }

        return $entity;
    }

    public function findByIdOrNull({{ENTITY_NAME}}Id $id): ?{{ENTITY_NAME}}
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

    public function findByAuthorId(int $authorId): array
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE author_id = %d ORDER BY updated_at DESC",
            $this->db->prefix(self::TABLE),
            $authorId
        );

        $result = $this->db->query($sql);
        $entities = [];

        while ($row = $this->db->fetchArray($result)) {
            $entities[] = $this->hydrate($row);
        }

        return $entities;
    }

    public function save({{ENTITY_NAME}} $entity): void
    {
        if ($this->exists($entity->getId())) {
            $this->update($entity);
        } else {
            $this->insert($entity);
        }
    }

    public function delete({{ENTITY_NAME}} $entity): void
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE id = %s",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($entity->getId()->toString())
        );

        $this->db->queryF($sql);
    }

    public function exists({{ENTITY_NAME}}Id $id): bool
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

    public function countByAuthorId(int $authorId): int
    {
        $sql = sprintf(
            "SELECT COUNT(*) FROM %s WHERE author_id = %d",
            $this->db->prefix(self::TABLE),
            $authorId
        );

        $result = $this->db->query($sql);
        [$count] = $this->db->fetchRow($result);

        return (int) $count;
    }

    private function insert({{ENTITY_NAME}} $entity): void
    {
        $sql = sprintf(
            "INSERT INTO %s (id, author_id, title, content, status, created_at, updated_at)
             VALUES (%s, %d, %s, %s, %s, %s, %s)",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($entity->getId()->toString()),
            $entity->getAuthorId(),
            $this->db->quoteString($entity->getTitle()->toString()),
            $this->db->quoteString($entity->getContent()->toString()),
            $this->db->quoteString($entity->getStatus()->value),
            $this->db->quoteString($entity->getCreatedAt()->format('Y-m-d H:i:s')),
            $this->db->quoteString($entity->getUpdatedAt()->format('Y-m-d H:i:s'))
        );

        $this->db->queryF($sql);
    }

    private function update({{ENTITY_NAME}} $entity): void
    {
        $sql = sprintf(
            "UPDATE %s SET
                title = %s,
                content = %s,
                status = %s,
                updated_at = %s
             WHERE id = %s",
            $this->db->prefix(self::TABLE),
            $this->db->quoteString($entity->getTitle()->toString()),
            $this->db->quoteString($entity->getContent()->toString()),
            $this->db->quoteString($entity->getStatus()->value),
            $this->db->quoteString($entity->getUpdatedAt()->format('Y-m-d H:i:s')),
            $this->db->quoteString($entity->getId()->toString())
        );

        $this->db->queryF($sql);
    }

    private function hydrate(array $row): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::reconstitute(
            id: {{ENTITY_NAME}}Id::fromString($row['id']),
            authorId: (int) $row['author_id'],
            title: {{ENTITY_NAME}}Title::create($row['title']),
            content: {{ENTITY_NAME}}Content::create($row['content']),
            status: {{ENTITY_NAME}}Status::from($row['status']),
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at'])
        );
    }
}
PHP;
    }

    private function templateContainer(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Infrastructure\Xoops;

use {{NAMESPACE}}\Application\Command\Create{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Command\Update{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Command\Delete{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Query\Get{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Query\List{{ENTITY_NAME_PLURAL}}Handler;
use {{NAMESPACE}}\Infrastructure\Persistence\MySql{{ENTITY_NAME}}Repository;
use {{NAMESPACE}}\Presentation\Controller\{{ENTITY_NAME}}Controller;

/**
 * Container - Simple dependency injection container.
 */
final class Container
{
    private array $services = [];

    public function __construct(
        private readonly \XoopsDatabase $db
    ) {}

    public function get{{ENTITY_NAME}}Repository(): MySql{{ENTITY_NAME}}Repository
    {
        return $this->services[MySql{{ENTITY_NAME}}Repository::class] ??=
            new MySql{{ENTITY_NAME}}Repository($this->db);
    }

    public function getCreate{{ENTITY_NAME}}Handler(): Create{{ENTITY_NAME}}Handler
    {
        return $this->services[Create{{ENTITY_NAME}}Handler::class] ??=
            new Create{{ENTITY_NAME}}Handler($this->get{{ENTITY_NAME}}Repository());
    }

    public function getUpdate{{ENTITY_NAME}}Handler(): Update{{ENTITY_NAME}}Handler
    {
        return $this->services[Update{{ENTITY_NAME}}Handler::class] ??=
            new Update{{ENTITY_NAME}}Handler($this->get{{ENTITY_NAME}}Repository());
    }

    public function getDelete{{ENTITY_NAME}}Handler(): Delete{{ENTITY_NAME}}Handler
    {
        return $this->services[Delete{{ENTITY_NAME}}Handler::class] ??=
            new Delete{{ENTITY_NAME}}Handler($this->get{{ENTITY_NAME}}Repository());
    }

    public function getGet{{ENTITY_NAME}}Handler(): Get{{ENTITY_NAME}}Handler
    {
        return $this->services[Get{{ENTITY_NAME}}Handler::class] ??=
            new Get{{ENTITY_NAME}}Handler($this->get{{ENTITY_NAME}}Repository());
    }

    public function getList{{ENTITY_NAME_PLURAL}}Handler(): List{{ENTITY_NAME_PLURAL}}Handler
    {
        return $this->services[List{{ENTITY_NAME_PLURAL}}Handler::class] ??=
            new List{{ENTITY_NAME_PLURAL}}Handler($this->get{{ENTITY_NAME}}Repository());
    }

    public function get{{ENTITY_NAME}}Controller(): {{ENTITY_NAME}}Controller
    {
        return $this->services[{{ENTITY_NAME}}Controller::class] ??=
            new {{ENTITY_NAME}}Controller(
                $this->getCreate{{ENTITY_NAME}}Handler(),
                $this->getUpdate{{ENTITY_NAME}}Handler(),
                $this->getGet{{ENTITY_NAME}}Handler(),
                $this->getList{{ENTITY_NAME_PLURAL}}Handler()
            );
    }
}
PHP;
    }

    private function templateController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Presentation\Controller;

use {{NAMESPACE}}\Application\Command\Create{{ENTITY_NAME}}Command;
use {{NAMESPACE}}\Application\Command\Create{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Command\Update{{ENTITY_NAME}}Command;
use {{NAMESPACE}}\Application\Command\Update{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Query\Get{{ENTITY_NAME}}Query;
use {{NAMESPACE}}\Application\Query\Get{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Application\Query\List{{ENTITY_NAME_PLURAL}}Query;
use {{NAMESPACE}}\Application\Query\List{{ENTITY_NAME_PLURAL}}Handler;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}Exception;

/**
 * {{ENTITY_NAME}}Controller - Handles HTTP requests.
 */
final class {{ENTITY_NAME}}Controller
{
    public function __construct(
        private readonly Create{{ENTITY_NAME}}Handler $createHandler,
        private readonly Update{{ENTITY_NAME}}Handler $updateHandler,
        private readonly Get{{ENTITY_NAME}}Handler $getHandler,
        private readonly List{{ENTITY_NAME_PLURAL}}Handler $listHandler
    ) {}

    /**
     * Display list of items.
     */
    public function index(\Smarty $tpl, int $userId): void
    {
        $query = new List{{ENTITY_NAME_PLURAL}}Query(userId: $userId);
        $items = $this->listHandler->handle($query);

        $tpl->assign('items', $this->formatItems($items));
        $tpl->assign('totalItems', count($items));
    }

    /**
     * Display a single item.
     */
    public function view(\Smarty $tpl, string $id, int $userId): void
    {
        try {
            $query = new Get{{ENTITY_NAME}}Query(id: $id, userId: $userId);
            $item = $this->getHandler->handle($query);

            $tpl->assign('item', $this->formatItem($item));
        } catch ({{ENTITY_NAME}}Exception $e) {
            $tpl->assign('error', $e->getMessage());
        }
    }

    /**
     * Create a new item.
     */
    public function create(int $userId, string $title, string $content): array
    {
        try {
            $command = new Create{{ENTITY_NAME}}Command(
                authorId: $userId,
                title: $title,
                content: $content
            );

            $item = $this->createHandler->handle($command);

            return ['success' => true, 'item' => $this->formatItem($item)];
        } catch ({{ENTITY_NAME}}Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function formatItem($item): array
    {
        return [
            'id' => $item->getId()->toString(),
            'title' => $item->getTitle()->toString(),
            'content' => $item->getContent()->toString(),
            'preview' => $item->getContent()->getPreview(),
            'status' => $item->getStatus()->value,
            'statusLabel' => $item->getStatus()->label(),
            'createdAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $item->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    private function formatItems(array $items): array
    {
        return array_map([$this, 'formatItem'], $items);
    }
}
PHP;
    }

    private function templateXoopsVersion(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * {{MODULE_NAME}} Module - XOOPS Version File
 *
 * @package    {{MODULE_NAME}}
 * @author     {{AUTHOR}}
 * @copyright  {{YEAR}}
 * @license    GPL-2.0-or-later
 */

$modversion = [
    'name'        => '{{MODULE_NAME}}',
    'version'     => '1.0.0',
    'description' => '{{MODULE_NAME}} module built with XOOPS 4.0 architecture',
    'author'      => '{{AUTHOR}}',
    'license'     => 'GPL-2.0-or-later',
    'dirname'     => '{{MODULE_NAME_LOWER}}',

    // Requirements
    'min_php'     => '8.2',
    'min_xoops'   => '2.6.0',
    'min_admin'   => '1.2',

    // Architecture flag
    'architecture' => 'clean',

    // Database tables
    'tables' => [
        '{{MODULE_NAME_LOWER}}_{{ENTITY_NAME_LOWER}}',
    ],

    // Admin
    'hasAdmin'    => 1,
    'adminindex'  => 'admin/index.php',
    'adminmenu'   => 'admin/menu.php',

    // User side
    'hasMain'     => 1,

    // Templates
    'templates' => [
        ['file' => '{{MODULE_NAME_LOWER}}_index.tpl', 'description' => 'Index page'],
        ['file' => '{{MODULE_NAME_LOWER}}_view.tpl', 'description' => 'View page'],
        ['file' => '{{MODULE_NAME_LOWER}}_form.tpl', 'description' => 'Form page'],
    ],
];
PHP;
    }

    private function templateMysqlSchema(): string
    {
        return <<<'SQL'
-- {{MODULE_NAME}} Module Database Schema
-- Generated: {{DATE}}

CREATE TABLE `{{MODULE_NAME_LOWER}}_{{ENTITY_NAME_LOWER}}` (
    `id` CHAR(26) NOT NULL COMMENT 'ULID primary key',
    `author_id` INT(10) UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` MEDIUMTEXT,
    `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_author` (`author_id`),
    KEY `idx_status` (`status`),
    KEY `idx_updated` (`updated_at`),

    CONSTRAINT `fk_{{MODULE_NAME_LOWER}}_{{ENTITY_NAME_LOWER}}_author`
        FOREIGN KEY (`author_id`)
        REFERENCES `users` (`uid`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    private function templateComposerJson(): string
    {
        return <<<'JSON'
{
    "name": "xoops/{{MODULE_NAME_LOWER}}",
    "description": "{{MODULE_NAME}} module for XOOPS CMS",
    "type": "xoops-module",
    "license": "GPL-2.0-or-later",
    "authors": [
        {
            "name": "{{AUTHOR}}"
        }
    ],
    "require": {
        "php": ">=8.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^1.10"
    },
    "autoload": {
        "psr-4": {
            "{{NAMESPACE}}\\": ""
        }
    },
    "autoload-dev": {
        "psr-4": {
            "{{NAMESPACE}}\\Tests\\": "tests/"
        }
    }
}
JSON;
    }

    private function templateLanguageMain(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

// Module name
define('_MD_{{MODULE_NAME_UPPER}}_NAME', '{{MODULE_NAME}}');

// Common
define('_MD_{{MODULE_NAME_UPPER}}_HOME', 'Home');
define('_MD_{{MODULE_NAME_UPPER}}_NO_ITEMS', 'No items found.');
define('_MD_{{MODULE_NAME_UPPER}}_CREATE', 'Create New');
define('_MD_{{MODULE_NAME_UPPER}}_EDIT', 'Edit');
define('_MD_{{MODULE_NAME_UPPER}}_DELETE', 'Delete');
define('_MD_{{MODULE_NAME_UPPER}}_SAVE', 'Save');
define('_MD_{{MODULE_NAME_UPPER}}_CANCEL', 'Cancel');

// Form labels
define('_MD_{{MODULE_NAME_UPPER}}_TITLE', 'Title');
define('_MD_{{MODULE_NAME_UPPER}}_CONTENT', 'Content');
define('_MD_{{MODULE_NAME_UPPER}}_STATUS', 'Status');

// Messages
define('_MD_{{MODULE_NAME_UPPER}}_SAVED', 'Saved successfully.');
define('_MD_{{MODULE_NAME_UPPER}}_DELETED', 'Deleted successfully.');
define('_MD_{{MODULE_NAME_UPPER}}_ERROR', 'An error occurred.');
PHP;
    }

    private function templateLanguageModinfo(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

// Module info
define('_MI_{{MODULE_NAME_UPPER}}_NAME', '{{MODULE_NAME}}');
define('_MI_{{MODULE_NAME_UPPER}}_DESC', '{{MODULE_NAME}} module built with XOOPS 4.0 architecture');

// Admin menu
define('_MI_{{MODULE_NAME_UPPER}}_ADMIN_INDEX', 'Dashboard');
define('_MI_{{MODULE_NAME_UPPER}}_ADMIN_MANAGE', 'Manage');
define('_MI_{{MODULE_NAME_UPPER}}_ADMIN_ABOUT', 'About');
PHP;
    }

    private function templateTemplateIndex(): string
    {
        return <<<'SMARTY'
<{include file="db:{{MODULE_NAME_LOWER}}_header.tpl"}>

<div class="{{MODULE_NAME_LOWER}}-container">
    <div class="{{MODULE_NAME_LOWER}}-header">
        <h2><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_NAME}></h2>
        <a href="<{$xoops_url}>/modules/{{MODULE_NAME_LOWER}}/edit.php" class="btn btn-primary">
            <{$smarty.const._MD_{{MODULE_NAME_UPPER}}_CREATE}>
        </a>
    </div>

    <{if $totalItems == 0}>
        <div class="{{MODULE_NAME_LOWER}}-empty">
            <p><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_NO_ITEMS}></p>
        </div>
    <{else}>
        <div class="{{MODULE_NAME_LOWER}}-list">
            <{foreach from=$items item=item}>
                <article class="{{MODULE_NAME_LOWER}}-card">
                    <h3 class="item-title">
                        <a href="<{$xoops_url}>/modules/{{MODULE_NAME_LOWER}}/view.php?id=<{$item.id}>">
                            <{$item.title}>
                        </a>
                    </h3>
                    <p class="item-preview"><{$item.preview}></p>
                    <footer class="item-meta">
                        <span class="item-status"><{$item.statusLabel}></span>
                        <span class="item-date"><{$item.updatedAt|date_format:"%b %d, %Y"}></span>
                    </footer>
                </article>
            <{/foreach}>
        </div>
    <{/if}>
</div>

<{include file="db:{{MODULE_NAME_LOWER}}_footer.tpl"}>
SMARTY;
    }

    private function templateTemplateView(): string
    {
        return <<<'SMARTY'
<{include file="db:{{MODULE_NAME_LOWER}}_header.tpl"}>

<div class="{{MODULE_NAME_LOWER}}-container">
    <{if $error}>
        <div class="alert alert-danger"><{$error}></div>
    <{else}>
        <article class="{{MODULE_NAME_LOWER}}-detail">
            <header>
                <h1><{$item.title}></h1>
                <div class="item-meta">
                    <span class="status"><{$item.statusLabel}></span>
                    <time datetime="<{$item.updatedAt}>"><{$item.updatedAt|date_format:"%B %d, %Y"}></time>
                </div>
            </header>

            <div class="item-content">
                <{$item.content|nl2br}>
            </div>

            <footer class="item-actions">
                <a href="<{$xoops_url}>/modules/{{MODULE_NAME_LOWER}}/edit.php?id=<{$item.id}>" class="btn">
                    <{$smarty.const._MD_{{MODULE_NAME_UPPER}}_EDIT}>
                </a>
            </footer>
        </article>
    <{/if}>
</div>

<{include file="db:{{MODULE_NAME_LOWER}}_footer.tpl"}>
SMARTY;
    }

    private function templateTemplateForm(): string
    {
        return <<<'SMARTY'
<{include file="db:{{MODULE_NAME_LOWER}}_header.tpl"}>

<div class="{{MODULE_NAME_LOWER}}-container">
    <h2><{if $item.id}><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_EDIT}><{else}><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_CREATE}><{/if}></h2>

    <form method="post" action="<{$xoops_url}>/modules/{{MODULE_NAME_LOWER}}/save.php" class="{{MODULE_NAME_LOWER}}-form">
        <{if $item.id}>
            <input type="hidden" name="id" value="<{$item.id}>">
        <{/if}>

        <div class="form-group">
            <label for="title"><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_TITLE}></label>
            <input type="text" name="title" id="title" value="<{$item.title}>" required>
        </div>

        <div class="form-group">
            <label for="content"><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_CONTENT}></label>
            <textarea name="content" id="content" rows="10"><{$item.content}></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_SAVE}></button>
            <a href="<{$xoops_url}>/modules/{{MODULE_NAME_LOWER}}/" class="btn"><{$smarty.const._MD_{{MODULE_NAME_UPPER}}_CANCEL}></a>
        </div>
    </form>
</div>

<{include file="db:{{MODULE_NAME_LOWER}}_footer.tpl"}>
SMARTY;
    }

    private function templateIndexPhp(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * {{MODULE_NAME}} Module - Main Index
 */

use {{NAMESPACE}}\Infrastructure\Xoops\Container;

require_once dirname(__DIR__, 2) . '/mainfile.php';

$GLOBALS['xoopsOption']['template_main'] = '{{MODULE_NAME_LOWER}}_index.tpl';
require_once XOOPS_ROOT_PATH . '/header.php';

if (!$xoopsUser) {
    redirect_header(XOOPS_URL . '/user.php', 3, _NOPERM);
    exit;
}

$container = new Container($GLOBALS['xoopsDB']);
$controller = $container->get{{ENTITY_NAME}}Controller();
$controller->index($xoopsTpl, $xoopsUser->uid());

require_once XOOPS_ROOT_PATH . '/footer.php';
PHP;
    }

    private function templateViewPhp(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * {{MODULE_NAME}} Module - View Page
 */

use {{NAMESPACE}}\Infrastructure\Xoops\Container;

require_once dirname(__DIR__, 2) . '/mainfile.php';

$GLOBALS['xoopsOption']['template_main'] = '{{MODULE_NAME_LOWER}}_view.tpl';
require_once XOOPS_ROOT_PATH . '/header.php';

if (!$xoopsUser) {
    redirect_header(XOOPS_URL . '/user.php', 3, _NOPERM);
    exit;
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    redirect_header(XOOPS_URL . '/modules/{{MODULE_NAME_LOWER}}/', 3, 'Invalid ID');
    exit;
}

$container = new Container($GLOBALS['xoopsDB']);
$controller = $container->get{{ENTITY_NAME}}Controller();
$controller->view($xoopsTpl, $id, $xoopsUser->uid());

require_once XOOPS_ROOT_PATH . '/footer.php';
PHP;
    }

    private function templateReadme(): string
    {
        return <<<'MARKDOWN'
# {{MODULE_NAME}} Module

A modern XOOPS module built with Clean Architecture and Domain-Driven Design.

## Requirements

- PHP 8.4+
- XOOPS 2.6.0+
- XMF Library

## Installation

1. Copy the `{{MODULE_NAME_LOWER}}` folder to `modules/`
2. Install via XOOPS module manager
3. Configure permissions

## Architecture

```
{{MODULE_NAME_LOWER}}/
├── Domain/           # Core business logic
├── Application/      # Use cases and commands
├── Infrastructure/   # Framework integrations
└── Presentation/     # Controllers and templates
```

## Features

- ULID-based identifiers
- Clean Architecture separation
- CQRS pattern (Commands/Queries)
- Type-safe value objects
- Comprehensive validation

## License

GPL-2.0-or-later
MARKDOWN;
    }

    private function templateApiController(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Infrastructure\Api\Controller;

// API Controller implementation
// See: Adding REST API to Your Module tutorial

final class {{ENTITY_NAME_PLURAL}}ApiController
{
    // Implementation similar to NotesApiController
}
PHP;
    }

    private function templateApiIndex(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * {{MODULE_NAME}} Module - REST API v1 Entry Point
 */

// See: Adding REST API to Your Module tutorial
// for full implementation

header('Content-Type: application/json');
echo json_encode(['message' => 'API endpoint - implement me!']);
PHP;
    }

    private function templateApiHtaccess(): string
    {
        return <<<'APACHE'
RewriteEngine On
RewriteBase /modules/{{MODULE_NAME_LOWER}}/api/v1/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
APACHE;
    }

    private function templateOpenapi(): string
    {
        return <<<'YAML'
openapi: 3.1.0
info:
  title: {{MODULE_NAME}} Module API
  version: 1.0.0

servers:
  - url: /modules/{{MODULE_NAME_LOWER}}/api/v1

paths:
  /{{ENTITY_NAME_PLURAL_LOWER}}:
    get:
      summary: List {{ENTITY_NAME_PLURAL_LOWER}}
      responses:
        '200':
          description: Success
YAML;
    }

    private function templateAdminIndex(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * {{MODULE_NAME}} Module - Admin Index
 */

require_once dirname(__DIR__, 3) . '/include/cp_header.php';

$GLOBALS['xoopsOption']['template_main'] = '{{MODULE_NAME_LOWER}}_admin_index.tpl';

// Admin implementation
echo 'Admin Dashboard - implement me!';

require_once XOOPS_ROOT_PATH . '/footer.php';
PHP;
    }

    private function templateAdminMenu(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$adminmenu = [
    [
        'title' => _MI_{{MODULE_NAME_UPPER}}_ADMIN_INDEX,
        'link'  => 'admin/index.php',
        'icon'  => 'fa fa-dashboard',
    ],
    [
        'title' => _MI_{{MODULE_NAME_UPPER}}_ADMIN_MANAGE,
        'link'  => 'admin/manage.php',
        'icon'  => 'fa fa-list',
    ],
    [
        'title' => _MI_{{MODULE_NAME_UPPER}}_ADMIN_ABOUT,
        'link'  => 'admin/about.php',
        'icon'  => 'fa fa-info-circle',
    ],
];
PHP;
    }

    private function templateAdminTemplate(): string
    {
        return <<<'SMARTY'
<div class="{{MODULE_NAME_LOWER}}-admin">
    <h1><{$smarty.const._MI_{{MODULE_NAME_UPPER}}_NAME}></h1>
    <p>Admin dashboard content here.</p>
</div>
SMARTY;
    }

    private function templateLanguageAdmin(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

// Admin language strings
define('_AM_{{MODULE_NAME_UPPER}}_DASHBOARD', 'Dashboard');
define('_AM_{{MODULE_NAME_UPPER}}_MANAGE', 'Manage Items');
define('_AM_{{MODULE_NAME_UPPER}}_SETTINGS', 'Settings');
PHP;
    }

    private function templateBlocksPhp(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * {{MODULE_NAME}} Module - Blocks
 */

function {{MODULE_NAME_LOWER}}_block_recent(array $options): array
{
    // Implement block logic
    return [
        'items' => [],
    ];
}
PHP;
    }

    private function templateBlockTemplate(): string
    {
        return <<<'SMARTY'
<div class="{{MODULE_NAME_LOWER}}-block-recent">
    <{if $items}>
        <ul>
            <{foreach from=$items item=item}>
                <li><a href="<{$item.url}>"><{$item.title}></a></li>
            <{/foreach}>
        </ul>
    <{else}>
        <p>No items.</p>
    <{/if}>
</div>
SMARTY;
    }

    private function templateLanguageBlocks(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

// Block language strings
define('_MB_{{MODULE_NAME_UPPER}}_RECENT', 'Recent Items');
define('_MB_{{MODULE_NAME_UPPER}}_RECENT_DESC', 'Display recent items');
PHP;
    }
}

// ============================================================================
// Main Execution
// ============================================================================

if (php_sapi_name() === 'cli') {
    $config = Config::fromArgs($argv);
    $generator = new FileGenerator($config);
    $generator->generate();
}
