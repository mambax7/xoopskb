#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * XOOPS 4.0 Test Suite Generator
 *
 * Generates PHPUnit tests for value objects, entities, and handlers.
 * Can be used standalone or as an extension to generate-module.php
 *
 * Usage:
 *   php generate-tests.php <module-path> [options]
 *
 * Options:
 *   --entity=<name>     Entity name to generate tests for (default: auto-detect)
 *   --coverage          Generate code coverage configuration
 *   --integration       Include integration test stubs
 *   --help              Show this help message
 *
 * Examples:
 *   php generate-tests.php ./modules/articles
 *   php generate-tests.php ./modules/blog --entity=Post
 *   php generate-tests.php ./modules/notes --coverage --integration
 */

namespace XoopsTestGenerator;

// ============================================================================
// Configuration
// ============================================================================

final class TestConfig
{
    public function __construct(
        public readonly string $modulePath,
        public readonly string $moduleName,
        public readonly string $moduleNameLower,
        public readonly string $entityName,
        public readonly string $entityNameLower,
        public readonly string $entityNamePlural,
        public readonly string $namespace,
        public readonly bool $withCoverage,
        public readonly bool $withIntegration
    ) {}

    public static function fromArgs(array $args): self
    {
        $options = self::parseArgs($args);

        if (isset($options['help']) || empty($options['_positional'])) {
            self::showHelp();
            exit(0);
        }

        $modulePath = rtrim($options['_positional'][0], '/');

        if (!is_dir($modulePath)) {
            echo "Error: Module path does not exist: {$modulePath}\n";
            exit(1);
        }

        // Auto-detect module name from path
        $moduleName = basename($modulePath);
        $moduleNameLower = strtolower($moduleName);

        // Auto-detect entity name from Domain/Entity directory
        $entityName = $options['entity'] ?? self::detectEntityName($modulePath);

        if (!$entityName) {
            echo "Error: Could not detect entity name. Use --entity=<name> option.\n";
            exit(1);
        }

        return new self(
            modulePath: $modulePath,
            moduleName: ucfirst($moduleNameLower),
            moduleNameLower: $moduleNameLower,
            entityName: ucfirst($entityName),
            entityNameLower: strtolower($entityName),
            entityNamePlural: self::pluralize($entityName),
            namespace: ucfirst($moduleNameLower),
            withCoverage: isset($options['coverage']),
            withIntegration: isset($options['integration'])
        );
    }

    private static function detectEntityName(string $path): ?string
    {
        $entityDir = $path . '/Domain/Entity';

        if (!is_dir($entityDir)) {
            return null;
        }

        $files = glob($entityDir . '/*.php');

        foreach ($files as $file) {
            $name = basename($file, '.php');
            // Skip common non-entity files
            if (!in_array($name, ['Interface', 'Abstract', 'Base', 'Trait'])) {
                return $name;
            }
        }

        return null;
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

    private static function parseArgs(array $args): array
    {
        $options = ['_positional' => []];
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

    private static function showHelp(): void
    {
        echo <<<'HELP'
XOOPS 4.0 Test Suite Generator

Generates PHPUnit tests for value objects, entities, and handlers.

Usage:
  php generate-tests.php <module-path> [options]

Options:
  --entity=<name>     Entity name to generate tests for (default: auto-detect)
  --coverage          Generate code coverage configuration
  --integration       Include integration test stubs
  --help              Show this help message

Examples:
  php generate-tests.php ./modules/articles
  php generate-tests.php ./modules/blog --entity=Post
  php generate-tests.php ./modules/notes --coverage --integration

HELP;
    }
}

// ============================================================================
// Test Generator
// ============================================================================

final class TestGenerator
{
    private int $filesCreated = 0;

    public function __construct(
        private readonly TestConfig $config
    ) {}

    public function generate(): void
    {
        $basePath = $this->config->modulePath;

        $this->output("\n🧪 Generating Test Suite for: {$this->config->moduleName}\n");
        $this->output("   Entity: {$this->config->entityName}\n\n");

        // Create test directories
        $this->createDirectories($basePath);

        // Generate test files
        $this->generateValueObjectTests($basePath);
        $this->generateEntityTests($basePath);
        $this->generateCommandHandlerTests($basePath);
        $this->generateQueryHandlerTests($basePath);
        $this->generateRepositoryTests($basePath);

        if ($this->config->withIntegration) {
            $this->generateIntegrationTests($basePath);
        }

        // Generate PHPUnit configuration
        $this->generatePhpUnitConfig($basePath);

        // Generate test bootstrap
        $this->generateBootstrap($basePath);

        // Generate test utilities
        $this->generateTestUtilities($basePath);

        $this->output("\n✅ Test suite generated successfully!\n");
        $this->output("   Files created: {$this->filesCreated}\n\n");
        $this->output("Run tests with: cd {$basePath} && ./vendor/bin/phpunit\n\n");
    }

    private function createDirectories(string $basePath): void
    {
        $dirs = [
            'tests',
            'tests/Unit',
            'tests/Unit/Domain',
            'tests/Unit/Domain/ValueObject',
            'tests/Unit/Domain/Entity',
            'tests/Unit/Application',
            'tests/Unit/Application/Command',
            'tests/Unit/Application/Query',
            'tests/Unit/Infrastructure',
            'tests/Fixtures',
        ];

        if ($this->config->withIntegration) {
            $dirs[] = 'tests/Integration';
            $dirs[] = 'tests/Integration/Repository';
        }

        foreach ($dirs as $dir) {
            $path = $basePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                $this->output("  📁 Created: {$dir}/\n");
            }
        }
    }

    // ========================================================================
    // Value Object Tests
    // ========================================================================

    private function generateValueObjectTests(string $basePath): void
    {
        $this->output("\n📋 Generating Value Object Tests...\n");

        // EntityId Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Domain/ValueObject/{$this->config->entityName}IdTest.php",
            $this->templateEntityIdTest()
        );

        // Title Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Domain/ValueObject/{$this->config->entityName}TitleTest.php",
            $this->templateTitleTest()
        );

        // Content Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Domain/ValueObject/{$this->config->entityName}ContentTest.php",
            $this->templateContentTest()
        );

        // Status Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Domain/ValueObject/{$this->config->entityName}StatusTest.php",
            $this->templateStatusTest()
        );
    }

    // ========================================================================
    // Entity Tests
    // ========================================================================

    private function generateEntityTests(string $basePath): void
    {
        $this->output("\n📋 Generating Entity Tests...\n");

        $this->writeFile(
            "{$basePath}/tests/Unit/Domain/Entity/{$this->config->entityName}Test.php",
            $this->templateEntityTest()
        );
    }

    // ========================================================================
    // Command Handler Tests
    // ========================================================================

    private function generateCommandHandlerTests(string $basePath): void
    {
        $this->output("\n📋 Generating Command Handler Tests...\n");

        // Create Handler Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Application/Command/Create{$this->config->entityName}HandlerTest.php",
            $this->templateCreateHandlerTest()
        );

        // Update Handler Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Application/Command/Update{$this->config->entityName}HandlerTest.php",
            $this->templateUpdateHandlerTest()
        );

        // Delete Handler Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Application/Command/Delete{$this->config->entityName}HandlerTest.php",
            $this->templateDeleteHandlerTest()
        );
    }

    // ========================================================================
    // Query Handler Tests
    // ========================================================================

    private function generateQueryHandlerTests(string $basePath): void
    {
        $this->output("\n📋 Generating Query Handler Tests...\n");

        // Get Handler Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Application/Query/Get{$this->config->entityName}HandlerTest.php",
            $this->templateGetHandlerTest()
        );

        // List Handler Test
        $this->writeFile(
            "{$basePath}/tests/Unit/Application/Query/List{$this->config->entityNamePlural}HandlerTest.php",
            $this->templateListHandlerTest()
        );
    }

    // ========================================================================
    // Repository Tests
    // ========================================================================

    private function generateRepositoryTests(string $basePath): void
    {
        $this->output("\n📋 Generating Repository Tests...\n");

        // In-Memory Repository for testing
        $this->writeFile(
            "{$basePath}/tests/Fixtures/InMemory{$this->config->entityName}Repository.php",
            $this->templateInMemoryRepository()
        );
    }

    // ========================================================================
    // Integration Tests
    // ========================================================================

    private function generateIntegrationTests(string $basePath): void
    {
        $this->output("\n📋 Generating Integration Tests...\n");

        $this->writeFile(
            "{$basePath}/tests/Integration/Repository/MySql{$this->config->entityName}RepositoryTest.php",
            $this->templateIntegrationRepositoryTest()
        );
    }

    // ========================================================================
    // PHPUnit Configuration
    // ========================================================================

    private function generatePhpUnitConfig(string $basePath): void
    {
        $this->output("\n📋 Generating PHPUnit Configuration...\n");

        $this->writeFile(
            "{$basePath}/phpunit.xml",
            $this->templatePhpUnitXml()
        );
    }

    // ========================================================================
    // Bootstrap and Utilities
    // ========================================================================

    private function generateBootstrap(string $basePath): void
    {
        $this->output("\n📋 Generating Test Bootstrap...\n");

        $this->writeFile(
            "{$basePath}/tests/bootstrap.php",
            $this->templateBootstrap()
        );
    }

    private function generateTestUtilities(string $basePath): void
    {
        $this->output("\n📋 Generating Test Utilities...\n");

        // Entity Factory
        $this->writeFile(
            "{$basePath}/tests/Fixtures/{$this->config->entityName}Factory.php",
            $this->templateEntityFactory()
        );

        // Mother object
        $this->writeFile(
            "{$basePath}/tests/Fixtures/{$this->config->entityName}Mother.php",
            $this->templateEntityMother()
        );
    }

    // ========================================================================
    // Template Helpers
    // ========================================================================

    private function replace(string $content): string
    {
        $replacements = [
            '{{MODULE_NAME}}' => $this->config->moduleName,
            '{{MODULE_NAME_LOWER}}' => $this->config->moduleNameLower,
            '{{ENTITY_NAME}}' => $this->config->entityName,
            '{{ENTITY_NAME_LOWER}}' => $this->config->entityNameLower,
            '{{ENTITY_NAME_PLURAL}}' => $this->config->entityNamePlural,
            '{{ENTITY_NAME_PLURAL_LOWER}}' => strtolower($this->config->entityNamePlural),
            '{{NAMESPACE}}' => $this->config->namespace,
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
        $relativePath = str_replace($this->config->modulePath . '/', '', $path);
        $this->output("  📄 Created: {$relativePath}\n");
    }

    private function output(string $message): void
    {
        echo $message;
    }

    // ========================================================================
    // Templates
    // ========================================================================

    private function templateEntityIdTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Id;

#[CoversClass({{ENTITY_NAME}}Id::class)]
final class {{ENTITY_NAME}}IdTest extends TestCase
{
    #[Test]
    public function it_generates_valid_ulid(): void
    {
        $id = {{ENTITY_NAME}}Id::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9A-HJKMNP-TV-Z]{26}$/',
            $id->toString()
        );
    }

    #[Test]
    public function it_creates_from_valid_string(): void
    {
        $ulidString = '01HV8X5Z0KDMVR8SDPY62J9ACP';
        $id = {{ENTITY_NAME}}Id::fromString($ulidString);

        $this->assertSame($ulidString, $id->toString());
    }

    #[Test]
    public function it_throws_on_invalid_format(): void
    {
        $this->expectException(Invalid{{ENTITY_NAME}}Id::class);

        {{ENTITY_NAME}}Id::fromString('invalid-id');
    }

    #[Test]
    #[DataProvider('invalidUlidProvider')]
    public function it_rejects_invalid_ulids(string $invalid): void
    {
        $this->expectException(Invalid{{ENTITY_NAME}}Id::class);

        {{ENTITY_NAME}}Id::fromString($invalid);
    }

    public static function invalidUlidProvider(): array
    {
        return [
            'too short' => ['01HV8X5Z0KDMVR8SDPY62J9AC'],
            'too long' => ['01HV8X5Z0KDMVR8SDPY62J9ACPP'],
            'invalid chars' => ['01HV8X5Z0KDMVR8SDPY62J9ACL'], // L is invalid
            'lowercase' => ['01hv8x5z0kdmvr8sdpy62j9acp'],
            'empty' => [''],
            'spaces' => ['01HV8X5Z0KDMVR8 DPY62J9ACP'],
        ];
    }

    #[Test]
    public function it_extracts_timestamp(): void
    {
        $id = {{ENTITY_NAME}}Id::generate();
        $timestamp = $id->getTimestamp();

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $timestamp->getTimestamp();

        $this->assertLessThan(2, abs($diff), 'Timestamp should be within 2 seconds of now');
    }

    #[Test]
    public function it_maintains_chronological_order(): void
    {
        $id1 = {{ENTITY_NAME}}Id::generate();
        usleep(1000); // 1ms delay
        $id2 = {{ENTITY_NAME}}Id::generate();

        $this->assertLessThan(
            0,
            $id1->compareTo($id2),
            'Earlier ID should compare less than later ID'
        );
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $ulidString = '01HV8X5Z0KDMVR8SDPY62J9ACP';
        $id1 = {{ENTITY_NAME}}Id::fromString($ulidString);
        $id2 = {{ENTITY_NAME}}Id::fromString($ulidString);
        $id3 = {{ENTITY_NAME}}Id::generate();

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $id = {{ENTITY_NAME}}Id::generate();

        $this->assertSame($id->toString(), (string) $id);
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $ulidString = '01HV8X5Z0KDMVR8SDPY62J9ACP';
        $id = {{ENTITY_NAME}}Id::fromString($ulidString);

        $this->assertSame("\"{$ulidString}\"", json_encode($id));
    }

    #[Test]
    public function generated_ids_are_unique(): void
    {
        $ids = [];
        for ($i = 0; $i < 1000; $i++) {
            $ids[] = {{ENTITY_NAME}}Id::generate()->toString();
        }

        $uniqueIds = array_unique($ids);
        $this->assertCount(1000, $uniqueIds, 'All generated IDs should be unique');
    }
}
PHP;
    }

    private function templateTitleTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Title;

#[CoversClass({{ENTITY_NAME}}Title::class)]
final class {{ENTITY_NAME}}TitleTest extends TestCase
{
    #[Test]
    public function it_creates_valid_title(): void
    {
        $title = {{ENTITY_NAME}}Title::create('My Test Title');

        $this->assertSame('My Test Title', $title->toString());
    }

    #[Test]
    public function it_trims_whitespace(): void
    {
        $title = {{ENTITY_NAME}}Title::create('  Trimmed Title  ');

        $this->assertSame('Trimmed Title', $title->toString());
    }

    #[Test]
    public function it_accepts_minimum_length(): void
    {
        $title = {{ENTITY_NAME}}Title::create('A');

        $this->assertSame('A', $title->toString());
    }

    #[Test]
    public function it_accepts_maximum_length(): void
    {
        $maxTitle = str_repeat('a', 200);
        $title = {{ENTITY_NAME}}Title::create($maxTitle);

        $this->assertSame(200, mb_strlen($title->toString()));
    }

    #[Test]
    public function it_rejects_empty_title(): void
    {
        $this->expectException(Invalid{{ENTITY_NAME}}Title::class);

        {{ENTITY_NAME}}Title::create('');
    }

    #[Test]
    public function it_rejects_whitespace_only_title(): void
    {
        $this->expectException(Invalid{{ENTITY_NAME}}Title::class);

        {{ENTITY_NAME}}Title::create('   ');
    }

    #[Test]
    public function it_rejects_too_long_title(): void
    {
        $this->expectException(Invalid{{ENTITY_NAME}}Title::class);

        {{ENTITY_NAME}}Title::create(str_repeat('a', 201));
    }

    #[Test]
    #[DataProvider('validTitlesProvider')]
    public function it_accepts_valid_titles(string $input, string $expected): void
    {
        $title = {{ENTITY_NAME}}Title::create($input);

        $this->assertSame($expected, $title->toString());
    }

    public static function validTitlesProvider(): array
    {
        return [
            'simple' => ['Hello', 'Hello'],
            'with spaces' => ['Hello World', 'Hello World'],
            'with numbers' => ['Test 123', 'Test 123'],
            'unicode' => ['Héllo Wörld', 'Héllo Wörld'],
            'emoji' => ['Test 🎉', 'Test 🎉'],
            'trim left' => ['  Test', 'Test'],
            'trim right' => ['Test  ', 'Test'],
            'trim both' => ['  Test  ', 'Test'],
        ];
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $title1 = {{ENTITY_NAME}}Title::create('Test');
        $title2 = {{ENTITY_NAME}}Title::create('Test');
        $title3 = {{ENTITY_NAME}}Title::create('Different');

        $this->assertTrue($title1->equals($title2));
        $this->assertFalse($title1->equals($title3));
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $title = {{ENTITY_NAME}}Title::create('Stringable Test');

        $this->assertSame('Stringable Test', (string) $title);
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $title = {{ENTITY_NAME}}Title::create('JSON Test');

        $this->assertSame('"JSON Test"', json_encode($title));
    }
}
PHP;
    }

    private function templateContentTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Content;

#[CoversClass({{ENTITY_NAME}}Content::class)]
final class {{ENTITY_NAME}}ContentTest extends TestCase
{
    #[Test]
    public function it_creates_valid_content(): void
    {
        $content = {{ENTITY_NAME}}Content::create('This is test content.');

        $this->assertSame('This is test content.', $content->toString());
    }

    #[Test]
    public function it_allows_empty_content(): void
    {
        $content = {{ENTITY_NAME}}Content::create('');

        $this->assertSame('', $content->toString());
        $this->assertTrue($content->isEmpty());
    }

    #[Test]
    public function it_creates_empty_content_via_factory(): void
    {
        $content = {{ENTITY_NAME}}Content::empty();

        $this->assertTrue($content->isEmpty());
    }

    #[Test]
    public function it_rejects_too_long_content(): void
    {
        $this->expectException(Invalid{{ENTITY_NAME}}Content::class);

        {{ENTITY_NAME}}Content::create(str_repeat('a', 50001));
    }

    #[Test]
    public function it_accepts_maximum_length(): void
    {
        $maxContent = str_repeat('a', 50000);
        $content = {{ENTITY_NAME}}Content::create($maxContent);

        $this->assertSame(50000, mb_strlen($content->toString()));
    }

    #[Test]
    public function it_calculates_word_count(): void
    {
        $content = {{ENTITY_NAME}}Content::create('This is a test with seven words.');

        $this->assertSame(7, $content->getWordCount());
    }

    #[Test]
    public function it_returns_zero_word_count_for_empty(): void
    {
        $content = {{ENTITY_NAME}}Content::empty();

        $this->assertSame(0, $content->getWordCount());
    }

    #[Test]
    public function it_creates_preview(): void
    {
        $longText = str_repeat('word ', 100);
        $content = {{ENTITY_NAME}}Content::create($longText);

        $preview = $content->getPreview(50);

        $this->assertSame(53, mb_strlen($preview)); // 50 + "..."
        $this->assertStringEndsWith('...', $preview);
    }

    #[Test]
    public function it_returns_full_content_if_shorter_than_preview(): void
    {
        $content = {{ENTITY_NAME}}Content::create('Short text');

        $preview = $content->getPreview(150);

        $this->assertSame('Short text', $preview);
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $content1 = {{ENTITY_NAME}}Content::create('Test');
        $content2 = {{ENTITY_NAME}}Content::create('Test');
        $content3 = {{ENTITY_NAME}}Content::create('Different');

        $this->assertTrue($content1->equals($content2));
        $this->assertFalse($content1->equals($content3));
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $content = {{ENTITY_NAME}}Content::create('Stringable Test');

        $this->assertSame('Stringable Test', (string) $content);
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $content = {{ENTITY_NAME}}Content::create('JSON Test');

        $this->assertSame('"JSON Test"', json_encode($content));
    }
}
PHP;
    }

    private function templateStatusTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;

#[CoversClass({{ENTITY_NAME}}Status::class)]
final class {{ENTITY_NAME}}StatusTest extends TestCase
{
    #[Test]
    public function it_has_expected_cases(): void
    {
        $this->assertSame('draft', {{ENTITY_NAME}}Status::Draft->value);
        $this->assertSame('published', {{ENTITY_NAME}}Status::Published->value);
        $this->assertSame('archived', {{ENTITY_NAME}}Status::Archived->value);
    }

    #[Test]
    #[DataProvider('allowedTransitionsProvider')]
    public function it_allows_valid_transitions({{ENTITY_NAME}}Status $from, {{ENTITY_NAME}}Status $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    public static function allowedTransitionsProvider(): array
    {
        return [
            'draft to published' => [{{ENTITY_NAME}}Status::Draft, {{ENTITY_NAME}}Status::Published],
            'draft to archived' => [{{ENTITY_NAME}}Status::Draft, {{ENTITY_NAME}}Status::Archived],
            'published to archived' => [{{ENTITY_NAME}}Status::Published, {{ENTITY_NAME}}Status::Archived],
            'archived to draft' => [{{ENTITY_NAME}}Status::Archived, {{ENTITY_NAME}}Status::Draft],
        ];
    }

    #[Test]
    #[DataProvider('disallowedTransitionsProvider')]
    public function it_disallows_invalid_transitions({{ENTITY_NAME}}Status $from, {{ENTITY_NAME}}Status $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public static function disallowedTransitionsProvider(): array
    {
        return [
            'published to draft' => [{{ENTITY_NAME}}Status::Published, {{ENTITY_NAME}}Status::Draft],
            'archived to published' => [{{ENTITY_NAME}}Status::Archived, {{ENTITY_NAME}}Status::Published],
        ];
    }

    #[Test]
    public function it_returns_allowed_transitions(): void
    {
        $allowed = {{ENTITY_NAME}}Status::Draft->allowedTransitions();

        $this->assertContains({{ENTITY_NAME}}Status::Published, $allowed);
        $this->assertContains({{ENTITY_NAME}}Status::Archived, $allowed);
        $this->assertNotContains({{ENTITY_NAME}}Status::Draft, $allowed);
    }

    #[Test]
    public function it_provides_labels(): void
    {
        $this->assertSame('Draft', {{ENTITY_NAME}}Status::Draft->label());
        $this->assertSame('Published', {{ENTITY_NAME}}Status::Published->label());
        $this->assertSame('Archived', {{ENTITY_NAME}}Status::Archived->label());
    }

    #[Test]
    public function it_creates_from_string(): void
    {
        $status = {{ENTITY_NAME}}Status::from('draft');

        $this->assertSame({{ENTITY_NAME}}Status::Draft, $status);
    }

    #[Test]
    public function it_throws_on_invalid_string(): void
    {
        $this->expectException(\ValueError::class);

        {{ENTITY_NAME}}Status::from('invalid');
    }
}
PHP;
    }

    private function templateEntityTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;
use {{NAMESPACE}}\Domain\Exception\InvalidStatusTransition;
use {{NAMESPACE}}\Tests\Fixtures\{{ENTITY_NAME}}Mother;

#[CoversClass({{ENTITY_NAME}}::class)]
final class {{ENTITY_NAME}}Test extends TestCase
{
    #[Test]
    public function it_creates_new_entity(): void
    {
        $entity = {{ENTITY_NAME}}::create(
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Test Title'),
            content: {{ENTITY_NAME}}Content::create('Test content')
        );

        $this->assertInstanceOf({{ENTITY_NAME}}Id::class, $entity->getId());
        $this->assertSame(1, $entity->getAuthorId());
        $this->assertSame('Test Title', $entity->getTitle()->toString());
        $this->assertSame('Test content', $entity->getContent()->toString());
        $this->assertSame({{ENTITY_NAME}}Status::Draft, $entity->getStatus());
    }

    #[Test]
    public function it_starts_in_draft_status(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();

        $this->assertSame({{ENTITY_NAME}}Status::Draft, $entity->getStatus());
        $this->assertFalse($entity->isPublished());
        $this->assertFalse($entity->isArchived());
    }

    #[Test]
    public function it_updates_title(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();
        $originalUpdatedAt = $entity->getUpdatedAt();

        usleep(1000);
        $entity->updateTitle({{ENTITY_NAME}}Title::create('New Title'));

        $this->assertSame('New Title', $entity->getTitle()->toString());
        $this->assertGreaterThan($originalUpdatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_skips_update_if_title_unchanged(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withTitle('Same Title');
        $originalUpdatedAt = $entity->getUpdatedAt();

        usleep(1000);
        $entity->updateTitle({{ENTITY_NAME}}Title::create('Same Title'));

        $this->assertEquals($originalUpdatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_updates_content(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();
        $originalUpdatedAt = $entity->getUpdatedAt();

        usleep(1000);
        $entity->updateContent({{ENTITY_NAME}}Content::create('New content'));

        $this->assertSame('New content', $entity->getContent()->toString());
        $this->assertGreaterThan($originalUpdatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_publishes_from_draft(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();

        $entity->publish();

        $this->assertSame({{ENTITY_NAME}}Status::Published, $entity->getStatus());
        $this->assertTrue($entity->isPublished());
    }

    #[Test]
    public function it_archives_from_draft(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();

        $entity->archive();

        $this->assertSame({{ENTITY_NAME}}Status::Archived, $entity->getStatus());
        $this->assertTrue($entity->isArchived());
    }

    #[Test]
    public function it_archives_from_published(): void
    {
        $entity = {{ENTITY_NAME}}Mother::published();

        $entity->archive();

        $this->assertSame({{ENTITY_NAME}}Status::Archived, $entity->getStatus());
    }

    #[Test]
    public function it_restores_from_archived_to_draft(): void
    {
        $entity = {{ENTITY_NAME}}Mother::archived();

        $entity->restore();

        $this->assertSame({{ENTITY_NAME}}Status::Draft, $entity->getStatus());
    }

    #[Test]
    public function it_throws_on_invalid_transition(): void
    {
        $entity = {{ENTITY_NAME}}Mother::published();

        $this->expectException(InvalidStatusTransition::class);

        $entity->restore(); // published -> draft is not allowed
    }

    #[Test]
    public function it_checks_edit_permission(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(42);

        $this->assertTrue($entity->canBeEditedBy(42));
        $this->assertFalse($entity->canBeEditedBy(99));
    }

    #[Test]
    public function it_reconstitutes_from_persistence(): void
    {
        $id = {{ENTITY_NAME}}Id::generate();
        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-15 15:30:00');

        $entity = {{ENTITY_NAME}}::reconstitute(
            id: $id,
            authorId: 5,
            title: {{ENTITY_NAME}}Title::create('Reconstituted'),
            content: {{ENTITY_NAME}}Content::create('From DB'),
            status: {{ENTITY_NAME}}Status::Published,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        $this->assertTrue($id->equals($entity->getId()));
        $this->assertSame(5, $entity->getAuthorId());
        $this->assertSame('Reconstituted', $entity->getTitle()->toString());
        $this->assertSame({{ENTITY_NAME}}Status::Published, $entity->getStatus());
        $this->assertEquals($createdAt, $entity->getCreatedAt());
        $this->assertEquals($updatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_sets_timestamps_on_creation(): void
    {
        $before = new \DateTimeImmutable();
        $entity = {{ENTITY_NAME}}Mother::draft();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $entity->getCreatedAt());
        $this->assertLessThanOrEqual($after, $entity->getCreatedAt());
        $this->assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
    }
}
PHP;
    }

    private function templateCreateHandlerTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Application\Command;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Application\Command\Create{{ENTITY_NAME}}Command;
use {{NAMESPACE}}\Application\Command\Create{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;
use {{NAMESPACE}}\Domain\Exception\Invalid{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Tests\Fixtures\InMemory{{ENTITY_NAME}}Repository;

#[CoversClass(Create{{ENTITY_NAME}}Handler::class)]
#[CoversClass(Create{{ENTITY_NAME}}Command::class)]
final class Create{{ENTITY_NAME}}HandlerTest extends TestCase
{
    private InMemory{{ENTITY_NAME}}Repository $repository;
    private Create{{ENTITY_NAME}}Handler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemory{{ENTITY_NAME}}Repository();
        $this->handler = new Create{{ENTITY_NAME}}Handler($this->repository);
    }

    #[Test]
    public function it_creates_entity_with_valid_data(): void
    {
        $command = new Create{{ENTITY_NAME}}Command(
            authorId: 1,
            title: 'Test Title',
            content: 'Test content'
        );

        $entity = $this->handler->handle($command);

        $this->assertInstanceOf({{ENTITY_NAME}}::class, $entity);
        $this->assertSame('Test Title', $entity->getTitle()->toString());
        $this->assertSame('Test content', $entity->getContent()->toString());
        $this->assertSame(1, $entity->getAuthorId());
        $this->assertSame({{ENTITY_NAME}}Status::Draft, $entity->getStatus());
    }

    #[Test]
    public function it_persists_created_entity(): void
    {
        $command = new Create{{ENTITY_NAME}}Command(
            authorId: 1,
            title: 'Persisted Title',
            content: 'Content'
        );

        $entity = $this->handler->handle($command);

        $found = $this->repository->findByIdOrNull($entity->getId());
        $this->assertNotNull($found);
        $this->assertTrue($entity->getId()->equals($found->getId()));
    }

    #[Test]
    public function it_creates_with_empty_content(): void
    {
        $command = new Create{{ENTITY_NAME}}Command(
            authorId: 1,
            title: 'Title Only'
        );

        $entity = $this->handler->handle($command);

        $this->assertTrue($entity->getContent()->isEmpty());
    }

    #[Test]
    public function it_throws_on_invalid_title(): void
    {
        $command = new Create{{ENTITY_NAME}}Command(
            authorId: 1,
            title: '', // invalid
            content: 'Content'
        );

        $this->expectException(Invalid{{ENTITY_NAME}}Title::class);

        $this->handler->handle($command);
    }

    #[Test]
    public function it_throws_on_too_long_title(): void
    {
        $command = new Create{{ENTITY_NAME}}Command(
            authorId: 1,
            title: str_repeat('a', 201),
            content: 'Content'
        );

        $this->expectException(Invalid{{ENTITY_NAME}}Title::class);

        $this->handler->handle($command);
    }
}
PHP;
    }

    private function templateUpdateHandlerTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Application\Command;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Application\Command\Update{{ENTITY_NAME}}Command;
use {{NAMESPACE}}\Application\Command\Update{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound;
use {{NAMESPACE}}\Tests\Fixtures\InMemory{{ENTITY_NAME}}Repository;
use {{NAMESPACE}}\Tests\Fixtures\{{ENTITY_NAME}}Mother;

#[CoversClass(Update{{ENTITY_NAME}}Handler::class)]
#[CoversClass(Update{{ENTITY_NAME}}Command::class)]
final class Update{{ENTITY_NAME}}HandlerTest extends TestCase
{
    private InMemory{{ENTITY_NAME}}Repository $repository;
    private Update{{ENTITY_NAME}}Handler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemory{{ENTITY_NAME}}Repository();
        $this->handler = new Update{{ENTITY_NAME}}Handler($this->repository);
    }

    #[Test]
    public function it_updates_title(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $command = new Update{{ENTITY_NAME}}Command(
            id: $entity->getId()->toString(),
            userId: 1,
            title: 'Updated Title'
        );

        $updated = $this->handler->handle($command);

        $this->assertSame('Updated Title', $updated->getTitle()->toString());
    }

    #[Test]
    public function it_updates_content(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $command = new Update{{ENTITY_NAME}}Command(
            id: $entity->getId()->toString(),
            userId: 1,
            content: 'Updated content'
        );

        $updated = $this->handler->handle($command);

        $this->assertSame('Updated content', $updated->getContent()->toString());
    }

    #[Test]
    public function it_updates_both_title_and_content(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $command = new Update{{ENTITY_NAME}}Command(
            id: $entity->getId()->toString(),
            userId: 1,
            title: 'New Title',
            content: 'New content'
        );

        $updated = $this->handler->handle($command);

        $this->assertSame('New Title', $updated->getTitle()->toString());
        $this->assertSame('New content', $updated->getContent()->toString());
    }

    #[Test]
    public function it_throws_when_entity_not_found(): void
    {
        $command = new Update{{ENTITY_NAME}}Command(
            id: '01HV8X5Z0KDMVR8SDPY62J9ACP',
            userId: 1,
            title: 'Title'
        );

        $this->expectException({{ENTITY_NAME}}NotFound::class);

        $this->handler->handle($command);
    }

    #[Test]
    public function it_throws_when_user_cannot_edit(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $command = new Update{{ENTITY_NAME}}Command(
            id: $entity->getId()->toString(),
            userId: 999, // different user
            title: 'Title'
        );

        $this->expectException(\DomainException::class);

        $this->handler->handle($command);
    }
}
PHP;
    }

    private function templateDeleteHandlerTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Application\Command;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Application\Command\Delete{{ENTITY_NAME}}Command;
use {{NAMESPACE}}\Application\Command\Delete{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound;
use {{NAMESPACE}}\Tests\Fixtures\InMemory{{ENTITY_NAME}}Repository;
use {{NAMESPACE}}\Tests\Fixtures\{{ENTITY_NAME}}Mother;

#[CoversClass(Delete{{ENTITY_NAME}}Handler::class)]
#[CoversClass(Delete{{ENTITY_NAME}}Command::class)]
final class Delete{{ENTITY_NAME}}HandlerTest extends TestCase
{
    private InMemory{{ENTITY_NAME}}Repository $repository;
    private Delete{{ENTITY_NAME}}Handler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemory{{ENTITY_NAME}}Repository();
        $this->handler = new Delete{{ENTITY_NAME}}Handler($this->repository);
    }

    #[Test]
    public function it_deletes_entity(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);
        $id = $entity->getId();

        $command = new Delete{{ENTITY_NAME}}Command(
            id: $id->toString(),
            userId: 1
        );

        $this->handler->handle($command);

        $this->assertFalse($this->repository->exists($id));
    }

    #[Test]
    public function it_throws_when_entity_not_found(): void
    {
        $command = new Delete{{ENTITY_NAME}}Command(
            id: '01HV8X5Z0KDMVR8SDPY62J9ACP',
            userId: 1
        );

        $this->expectException({{ENTITY_NAME}}NotFound::class);

        $this->handler->handle($command);
    }

    #[Test]
    public function it_throws_when_user_cannot_delete(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $command = new Delete{{ENTITY_NAME}}Command(
            id: $entity->getId()->toString(),
            userId: 999 // different user
        );

        $this->expectException(\DomainException::class);

        $this->handler->handle($command);
    }
}
PHP;
    }

    private function templateGetHandlerTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Application\Query;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Application\Query\Get{{ENTITY_NAME}}Query;
use {{NAMESPACE}}\Application\Query\Get{{ENTITY_NAME}}Handler;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound;
use {{NAMESPACE}}\Tests\Fixtures\InMemory{{ENTITY_NAME}}Repository;
use {{NAMESPACE}}\Tests\Fixtures\{{ENTITY_NAME}}Mother;

#[CoversClass(Get{{ENTITY_NAME}}Handler::class)]
#[CoversClass(Get{{ENTITY_NAME}}Query::class)]
final class Get{{ENTITY_NAME}}HandlerTest extends TestCase
{
    private InMemory{{ENTITY_NAME}}Repository $repository;
    private Get{{ENTITY_NAME}}Handler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemory{{ENTITY_NAME}}Repository();
        $this->handler = new Get{{ENTITY_NAME}}Handler($this->repository);
    }

    #[Test]
    public function it_returns_entity_for_owner(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $query = new Get{{ENTITY_NAME}}Query(
            id: $entity->getId()->toString(),
            userId: 1
        );

        $result = $this->handler->handle($query);

        $this->assertTrue($entity->getId()->equals($result->getId()));
    }

    #[Test]
    public function it_throws_when_entity_not_found(): void
    {
        $query = new Get{{ENTITY_NAME}}Query(
            id: '01HV8X5Z0KDMVR8SDPY62J9ACP',
            userId: 1
        );

        $this->expectException({{ENTITY_NAME}}NotFound::class);

        $this->handler->handle($query);
    }

    #[Test]
    public function it_throws_when_user_cannot_access(): void
    {
        $entity = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity);

        $query = new Get{{ENTITY_NAME}}Query(
            id: $entity->getId()->toString(),
            userId: 999 // different user
        );

        $this->expectException(\DomainException::class);

        $this->handler->handle($query);
    }
}
PHP;
    }

    private function templateListHandlerTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Unit\Application\Query;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Application\Query\List{{ENTITY_NAME_PLURAL}}Query;
use {{NAMESPACE}}\Application\Query\List{{ENTITY_NAME_PLURAL}}Handler;
use {{NAMESPACE}}\Tests\Fixtures\InMemory{{ENTITY_NAME}}Repository;
use {{NAMESPACE}}\Tests\Fixtures\{{ENTITY_NAME}}Mother;

#[CoversClass(List{{ENTITY_NAME_PLURAL}}Handler::class)]
#[CoversClass(List{{ENTITY_NAME_PLURAL}}Query::class)]
final class List{{ENTITY_NAME_PLURAL}}HandlerTest extends TestCase
{
    private InMemory{{ENTITY_NAME}}Repository $repository;
    private List{{ENTITY_NAME_PLURAL}}Handler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemory{{ENTITY_NAME}}Repository();
        $this->handler = new List{{ENTITY_NAME_PLURAL}}Handler($this->repository);
    }

    #[Test]
    public function it_returns_entities_for_user(): void
    {
        $entity1 = {{ENTITY_NAME}}Mother::withAuthor(1);
        $entity2 = {{ENTITY_NAME}}Mother::withAuthor(1);
        $entity3 = {{ENTITY_NAME}}Mother::withAuthor(2); // different user

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $query = new List{{ENTITY_NAME_PLURAL}}Query(userId: 1);

        $results = $this->handler->handle($query);

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_returns_empty_array_when_no_entities(): void
    {
        $query = new List{{ENTITY_NAME_PLURAL}}Query(userId: 1);

        $results = $this->handler->handle($query);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    #[Test]
    public function it_counts_entities_for_user(): void
    {
        $entity1 = {{ENTITY_NAME}}Mother::withAuthor(1);
        $entity2 = {{ENTITY_NAME}}Mother::withAuthor(1);
        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $query = new List{{ENTITY_NAME_PLURAL}}Query(userId: 1);

        $count = $this->handler->count($query);

        $this->assertSame(2, $count);
    }
}
PHP;
    }

    private function templateInMemoryRepository(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Fixtures;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\Repository\{{ENTITY_NAME}}RepositoryInterface;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound;

/**
 * InMemory{{ENTITY_NAME}}Repository - Test double for {{ENTITY_NAME}} persistence.
 */
final class InMemory{{ENTITY_NAME}}Repository implements {{ENTITY_NAME}}RepositoryInterface
{
    /** @var array<string, {{ENTITY_NAME}}> */
    private array $entities = [];

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
        return $this->entities[$id->toString()] ?? null;
    }

    public function findByAuthorId(int $authorId): array
    {
        return array_values(array_filter(
            $this->entities,
            fn ({{ENTITY_NAME}} $e) => $e->getAuthorId() === $authorId
        ));
    }

    public function save({{ENTITY_NAME}} $entity): void
    {
        $this->entities[$entity->getId()->toString()] = $entity;
    }

    public function delete({{ENTITY_NAME}} $entity): void
    {
        unset($this->entities[$entity->getId()->toString()]);
    }

    public function exists({{ENTITY_NAME}}Id $id): bool
    {
        return isset($this->entities[$id->toString()]);
    }

    public function countByAuthorId(int $authorId): int
    {
        return count($this->findByAuthorId($authorId));
    }

    /**
     * Helper: Clear all entities (for test setup).
     */
    public function clear(): void
    {
        $this->entities = [];
    }

    /**
     * Helper: Get all entities.
     *
     * @return {{ENTITY_NAME}}[]
     */
    public function all(): array
    {
        return array_values($this->entities);
    }
}
PHP;
    }

    private function templateEntityFactory(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Fixtures;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;

/**
 * {{ENTITY_NAME}}Factory - Creates {{ENTITY_NAME}} entities for testing.
 *
 * Use this when you need fine-grained control over entity construction.
 * For simpler cases, use {{ENTITY_NAME}}Mother instead.
 */
final class {{ENTITY_NAME}}Factory
{
    private ?{{ENTITY_NAME}}Id $id = null;
    private int $authorId = 1;
    private string $title = 'Test Title';
    private string $content = 'Test content';
    private {{ENTITY_NAME}}Status $status = {{ENTITY_NAME}}Status::Draft;
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;

    public static function new(): self
    {
        return new self();
    }

    public function withId({{ENTITY_NAME}}Id $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function withAuthorId(int $authorId): self
    {
        $clone = clone $this;
        $clone->authorId = $authorId;
        return $clone;
    }

    public function withTitle(string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;
        return $clone;
    }

    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        return $clone;
    }

    public function withStatus({{ENTITY_NAME}}Status $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function draft(): self
    {
        return $this->withStatus({{ENTITY_NAME}}Status::Draft);
    }

    public function published(): self
    {
        return $this->withStatus({{ENTITY_NAME}}Status::Published);
    }

    public function archived(): self
    {
        return $this->withStatus({{ENTITY_NAME}}Status::Archived);
    }

    public function withCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $clone = clone $this;
        $clone->createdAt = $createdAt;
        return $clone;
    }

    public function withUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $clone = clone $this;
        $clone->updatedAt = $updatedAt;
        return $clone;
    }

    public function build(): {{ENTITY_NAME}}
    {
        $id = $this->id ?? {{ENTITY_NAME}}Id::generate();
        $createdAt = $this->createdAt ?? new \DateTimeImmutable();
        $updatedAt = $this->updatedAt ?? $createdAt;

        return {{ENTITY_NAME}}::reconstitute(
            id: $id,
            authorId: $this->authorId,
            title: {{ENTITY_NAME}}Title::create($this->title),
            content: {{ENTITY_NAME}}Content::create($this->content),
            status: $this->status,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Build multiple entities.
     *
     * @return {{ENTITY_NAME}}[]
     */
    public function buildMany(int $count): array
    {
        $entities = [];
        for ($i = 0; $i < $count; $i++) {
            $entities[] = $this->withTitle("{$this->title} {$i}")->build();
        }
        return $entities;
    }
}
PHP;
    }

    private function templateEntityMother(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Fixtures;

use {{NAMESPACE}}\Domain\Entity\{{ENTITY_NAME}};
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Content;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Status;

/**
 * {{ENTITY_NAME}}Mother - Object Mother pattern for test data.
 *
 * Provides named factory methods for common test scenarios.
 * Use this for quick, readable test setup.
 */
final class {{ENTITY_NAME}}Mother
{
    /**
     * Create a draft entity with default values.
     */
    public static function draft(): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::create(
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Test Title'),
            content: {{ENTITY_NAME}}Content::create('Test content')
        );
    }

    /**
     * Create a published entity.
     */
    public static function published(): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::reconstitute(
            id: {{ENTITY_NAME}}Id::generate(),
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Published Title'),
            content: {{ENTITY_NAME}}Content::create('Published content'),
            status: {{ENTITY_NAME}}Status::Published,
            createdAt: new \DateTimeImmutable('-1 day'),
            updatedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Create an archived entity.
     */
    public static function archived(): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::reconstitute(
            id: {{ENTITY_NAME}}Id::generate(),
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Archived Title'),
            content: {{ENTITY_NAME}}Content::create('Archived content'),
            status: {{ENTITY_NAME}}Status::Archived,
            createdAt: new \DateTimeImmutable('-7 days'),
            updatedAt: new \DateTimeImmutable('-1 day')
        );
    }

    /**
     * Create an entity with a specific author.
     */
    public static function withAuthor(int $authorId): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::reconstitute(
            id: {{ENTITY_NAME}}Id::generate(),
            authorId: $authorId,
            title: {{ENTITY_NAME}}Title::create('Test Title'),
            content: {{ENTITY_NAME}}Content::create('Test content'),
            status: {{ENTITY_NAME}}Status::Draft,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Create an entity with a specific title.
     */
    public static function withTitle(string $title): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::reconstitute(
            id: {{ENTITY_NAME}}Id::generate(),
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create($title),
            content: {{ENTITY_NAME}}Content::create('Test content'),
            status: {{ENTITY_NAME}}Status::Draft,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Create an entity with a specific ID.
     */
    public static function withId({{ENTITY_NAME}}Id $id): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::reconstitute(
            id: $id,
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Test Title'),
            content: {{ENTITY_NAME}}Content::create('Test content'),
            status: {{ENTITY_NAME}}Status::Draft,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Create an entity with long content.
     */
    public static function withLongContent(): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::create(
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Long Content Test'),
            content: {{ENTITY_NAME}}Content::create(str_repeat('Lorem ipsum ', 1000))
        );
    }

    /**
     * Create an entity with empty content.
     */
    public static function withEmptyContent(): {{ENTITY_NAME}}
    {
        return {{ENTITY_NAME}}::create(
            authorId: 1,
            title: {{ENTITY_NAME}}Title::create('Empty Content'),
            content: {{ENTITY_NAME}}Content::empty()
        );
    }

    /**
     * Create multiple draft entities.
     *
     * @return {{ENTITY_NAME}}[]
     */
    public static function manyDrafts(int $count): array
    {
        $entities = [];
        for ($i = 1; $i <= $count; $i++) {
            $entities[] = {{ENTITY_NAME}}::create(
                authorId: 1,
                title: {{ENTITY_NAME}}Title::create("Test Title {$i}"),
                content: {{ENTITY_NAME}}Content::create("Content {$i}")
            );
        }
        return $entities;
    }
}
PHP;
    }

    private function templateIntegrationRepositoryTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use {{NAMESPACE}}\Infrastructure\Persistence\MySql{{ENTITY_NAME}}Repository;
use {{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Id;
use {{NAMESPACE}}\Domain\Exception\{{ENTITY_NAME}}NotFound;
use {{NAMESPACE}}\Tests\Fixtures\{{ENTITY_NAME}}Mother;

/**
 * Integration tests for MySql{{ENTITY_NAME}}Repository.
 *
 * These tests require a database connection.
 * Set DB_DSN, DB_USER, DB_PASS environment variables.
 *
 * @group integration
 */
#[CoversClass(MySql{{ENTITY_NAME}}Repository::class)]
final class MySql{{ENTITY_NAME}}RepositoryTest extends TestCase
{
    private static ?\PDO $pdo = null;
    private MySql{{ENTITY_NAME}}Repository $repository;

    public static function setUpBeforeClass(): void
    {
        $dsn = getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=xoops_test';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        try {
            self::$pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            self::markTestSkipped('Database connection not available: ' . $e->getMessage());
        }
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('Database not available');
        }

        // Create database adapter wrapper that works with XOOPS
        // This is a simplified example - adjust for your XOOPS setup
        // $this->repository = new MySql{{ENTITY_NAME}}Repository($xoopsDb);

        $this->markTestIncomplete('Database adapter setup required');
    }

    protected function tearDown(): void
    {
        // Clean up test data
        // self::$pdo->exec('DELETE FROM {{MODULE_NAME_LOWER}}_{{ENTITY_NAME_LOWER}}');
    }

    #[Test]
    public function it_saves_and_retrieves_entity(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();

        $this->repository->save($entity);
        $found = $this->repository->findById($entity->getId());

        $this->assertTrue($entity->getId()->equals($found->getId()));
        $this->assertSame($entity->getTitle()->toString(), $found->getTitle()->toString());
    }

    #[Test]
    public function it_throws_when_not_found(): void
    {
        $this->expectException({{ENTITY_NAME}}NotFound::class);

        $this->repository->findById({{ENTITY_NAME}}Id::generate());
    }

    #[Test]
    public function it_updates_existing_entity(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();
        $this->repository->save($entity);

        $entity->updateTitle(\{{NAMESPACE}}\Domain\ValueObject\{{ENTITY_NAME}}Title::create('Updated'));
        $this->repository->save($entity);

        $found = $this->repository->findById($entity->getId());
        $this->assertSame('Updated', $found->getTitle()->toString());
    }

    #[Test]
    public function it_deletes_entity(): void
    {
        $entity = {{ENTITY_NAME}}Mother::draft();
        $this->repository->save($entity);

        $this->repository->delete($entity);

        $this->assertFalse($this->repository->exists($entity->getId()));
    }

    #[Test]
    public function it_finds_by_author(): void
    {
        $entity1 = {{ENTITY_NAME}}Mother::withAuthor(42);
        $entity2 = {{ENTITY_NAME}}Mother::withAuthor(42);
        $entity3 = {{ENTITY_NAME}}Mother::withAuthor(99);

        $this->repository->save($entity1);
        $this->repository->save($entity2);
        $this->repository->save($entity3);

        $found = $this->repository->findByAuthorId(42);

        $this->assertCount(2, $found);
    }

    #[Test]
    public function it_counts_by_author(): void
    {
        $entity1 = {{ENTITY_NAME}}Mother::withAuthor(42);
        $entity2 = {{ENTITY_NAME}}Mother::withAuthor(42);
        $this->repository->save($entity1);
        $this->repository->save($entity2);

        $count = $this->repository->countByAuthorId(42);

        $this->assertSame(2, $count);
    }
}
PHP;
    }

    private function templatePhpUnitXml(): string
    {
        $coverage = $this->config->withCoverage ? <<<'XML'

  <source>
    <include>
      <directory suffix=".php">Domain</directory>
      <directory suffix=".php">Application</directory>
      <directory suffix=".php">Infrastructure</directory>
    </include>
    <exclude>
      <directory suffix=".php">tests</directory>
    </exclude>
  </source>
XML : '';

        $integration = $this->config->withIntegration ? <<<'XML'

    <testsuite name="Integration">
      <directory>tests/Integration</directory>
    </testsuite>
XML : '';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         requireCoverageMetadata="false"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true">

  <testsuites>
    <testsuite name="Unit">
      <directory>tests/Unit</directory>
    </testsuite>{$integration}
  </testsuites>
{$coverage}
</phpunit>
XML;
    }

    private function templateBootstrap(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 */

// Composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    echo "Please run 'composer install' first.\n";
    exit(1);
}

require_once $autoloader;

// Set timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
PHP;
    }
}

// ============================================================================
// Main Execution
// ============================================================================

if (php_sapi_name() === 'cli') {
    $config = TestConfig::fromArgs($argv);
    $generator = new TestGenerator($config);
    $generator->generate();
}
