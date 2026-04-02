#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * XOOPS 4.0 Module Documentation Generator
 *
 * Automatically generates documentation from module source code:
 * - API documentation from controllers
 * - Entity relationship diagrams (Mermaid)
 * - Value object documentation
 * - README.md with module overview
 *
 * Usage:
 *   php generate-docs.php <module-path> [options]
 *
 * Options:
 *   --output=<path>    Output directory (default: module/docs)
 *   --format=<format>  Output format: markdown, html (default: markdown)
 *   --api              Generate API documentation only
 *   --diagrams         Generate diagrams only
 *   --readme           Generate README only
 *   --all              Generate all documentation (default)
 *   --help             Show this help message
 *
 * Examples:
 *   php generate-docs.php ./modules/articles
 *   php generate-docs.php ./modules/blog --output=./docs/blog
 *   php generate-docs.php ./modules/notes --api --diagrams
 */

namespace XoopsDocGenerator;

// ============================================================================
// Configuration
// ============================================================================

final class DocConfig
{
    public function __construct(
        public readonly string $modulePath,
        public readonly string $moduleName,
        public readonly string $outputPath,
        public readonly string $format,
        public readonly bool $generateApi,
        public readonly bool $generateDiagrams,
        public readonly bool $generateReadme,
        public readonly string $namespace
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

        $moduleName = basename($modulePath);
        $outputPath = $options['output'] ?? $modulePath . '/docs';

        // Determine what to generate
        $generateAll = isset($options['all']) ||
            (!isset($options['api']) && !isset($options['diagrams']) && !isset($options['readme']));

        return new self(
            modulePath: $modulePath,
            moduleName: ucfirst($moduleName),
            outputPath: $outputPath,
            format: $options['format'] ?? 'markdown',
            generateApi: $generateAll || isset($options['api']),
            generateDiagrams: $generateAll || isset($options['diagrams']),
            generateReadme: $generateAll || isset($options['readme']),
            namespace: ucfirst($moduleName)
        );
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
XOOPS 4.0 Module Documentation Generator

Automatically generates documentation from module source code.

Usage:
  php generate-docs.php <module-path> [options]

Options:
  --output=<path>    Output directory (default: module/docs)
  --format=<format>  Output format: markdown, html (default: markdown)
  --api              Generate API documentation only
  --diagrams         Generate diagrams only
  --readme           Generate README only
  --all              Generate all documentation (default)
  --help             Show this help message

Examples:
  php generate-docs.php ./modules/articles
  php generate-docs.php ./modules/blog --output=./docs/blog
  php generate-docs.php ./modules/notes --api --diagrams

HELP;
    }
}

// ============================================================================
// Code Analyzer
// ============================================================================

final class CodeAnalyzer
{
    private array $entities = [];
    private array $valueObjects = [];
    private array $commands = [];
    private array $queries = [];
    private array $apiEndpoints = [];
    private array $exceptions = [];

    public function __construct(
        private readonly string $modulePath,
        private readonly string $namespace
    ) {}

    public function analyze(): self
    {
        $this->analyzeEntities();
        $this->analyzeValueObjects();
        $this->analyzeCommands();
        $this->analyzeQueries();
        $this->analyzeApiEndpoints();
        $this->analyzeExceptions();

        return $this;
    }

    public function getEntities(): array { return $this->entities; }
    public function getValueObjects(): array { return $this->valueObjects; }
    public function getCommands(): array { return $this->commands; }
    public function getQueries(): array { return $this->queries; }
    public function getApiEndpoints(): array { return $this->apiEndpoints; }
    public function getExceptions(): array { return $this->exceptions; }

    private function analyzeEntities(): void
    {
        $entityDir = $this->modulePath . '/Domain/Entity';
        if (!is_dir($entityDir)) return;

        foreach (glob($entityDir . '/*.php') as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');

            $entity = [
                'name' => $className,
                'file' => $file,
                'properties' => $this->extractProperties($content),
                'methods' => $this->extractMethods($content),
                'docblock' => $this->extractClassDocblock($content),
                'relationships' => $this->extractRelationships($content),
            ];

            $this->entities[$className] = $entity;
        }
    }

    private function analyzeValueObjects(): void
    {
        $voDir = $this->modulePath . '/Domain/ValueObject';
        if (!is_dir($voDir)) return;

        foreach (glob($voDir . '/*.php') as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');

            $vo = [
                'name' => $className,
                'file' => $file,
                'constraints' => $this->extractConstraints($content),
                'methods' => $this->extractMethods($content),
                'docblock' => $this->extractClassDocblock($content),
                'isEnum' => str_contains($content, 'enum '),
            ];

            $this->valueObjects[$className] = $vo;
        }
    }

    private function analyzeCommands(): void
    {
        $cmdDir = $this->modulePath . '/Application/Command';
        if (!is_dir($cmdDir)) return;

        foreach (glob($cmdDir . '/*Command.php') as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');

            $command = [
                'name' => $className,
                'file' => $file,
                'properties' => $this->extractConstructorParams($content),
                'docblock' => $this->extractClassDocblock($content),
                'handler' => str_replace('Command', 'Handler', $className),
            ];

            $this->commands[$className] = $command;
        }
    }

    private function analyzeQueries(): void
    {
        $queryDir = $this->modulePath . '/Application/Query';
        if (!is_dir($queryDir)) return;

        foreach (glob($queryDir . '/*Query.php') as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');

            $query = [
                'name' => $className,
                'file' => $file,
                'properties' => $this->extractConstructorParams($content),
                'docblock' => $this->extractClassDocblock($content),
                'handler' => str_replace('Query', 'Handler', $className),
            ];

            $this->queries[$className] = $query;
        }
    }

    private function analyzeApiEndpoints(): void
    {
        $apiDir = $this->modulePath . '/Infrastructure/Api/Controller';
        if (!is_dir($apiDir)) {
            $apiDir = $this->modulePath . '/api/v1';
        }
        if (!is_dir($apiDir)) return;

        foreach (glob($apiDir . '/*.php') as $file) {
            $content = file_get_contents($file);

            // Extract route methods
            preg_match_all(
                '/public\s+function\s+(\w+)\s*\([^)]*\).*?(?=public\s+function|\}$)/s',
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $methodName = $match[1];
                $methodContent = $match[0];

                // Skip private/helper methods
                if (in_array($methodName, ['__construct', 'formatNote', 'formatNotes', 'validate'])) {
                    continue;
                }

                $endpoint = [
                    'method' => $this->inferHttpMethod($methodName),
                    'name' => $methodName,
                    'path' => $this->inferPath($methodName),
                    'docblock' => $this->extractMethodDocblock($content, $methodName),
                    'parameters' => $this->extractMethodParams($methodContent),
                ];

                $this->apiEndpoints[] = $endpoint;
            }
        }
    }

    private function analyzeExceptions(): void
    {
        $exDir = $this->modulePath . '/Domain/Exception';
        if (!is_dir($exDir)) return;

        foreach (glob($exDir . '/*.php') as $file) {
            $content = file_get_contents($file);

            // Extract all exception classes from the file
            preg_match_all('/(?:final\s+)?class\s+(\w+)\s+extends/', $content, $matches);

            foreach ($matches[1] as $className) {
                $this->exceptions[$className] = [
                    'name' => $className,
                    'file' => $file,
                    'factories' => $this->extractStaticFactories($content, $className),
                ];
            }
        }
    }

    // === Helper Methods ===

    private function extractProperties(string $content): array
    {
        $properties = [];

        preg_match_all(
            '/(?:private|protected|public)\s+(?:readonly\s+)?(\??\w+)\s+\$(\w+)/',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $properties[] = [
                'type' => $match[1],
                'name' => $match[2],
            ];
        }

        return $properties;
    }

    private function extractMethods(string $content): array
    {
        $methods = [];

        preg_match_all(
            '/public\s+(?:static\s+)?function\s+(\w+)\s*\([^)]*\)(?:\s*:\s*(\??\w+))?/',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $methods[] = [
                'name' => $match[1],
                'returnType' => $match[2] ?? 'void',
            ];
        }

        return $methods;
    }

    private function extractConstructorParams(string $content): array
    {
        $params = [];

        if (preg_match('/public\s+function\s+__construct\s*\(([^)]+)\)/s', $content, $match)) {
            preg_match_all(
                '/(?:public\s+)?(\??\w+)\s+\$(\w+)(?:\s*=\s*([^,)]+))?/',
                $match[1],
                $paramMatches,
                PREG_SET_ORDER
            );

            foreach ($paramMatches as $pm) {
                $params[] = [
                    'type' => $pm[1],
                    'name' => $pm[2],
                    'default' => isset($pm[3]) ? trim($pm[3]) : null,
                ];
            }
        }

        return $params;
    }

    private function extractConstraints(string $content): array
    {
        $constraints = [];

        if (preg_match('/MIN_LENGTH\s*=\s*(\d+)/', $content, $match)) {
            $constraints['minLength'] = (int) $match[1];
        }
        if (preg_match('/MAX_LENGTH\s*=\s*(\d+)/', $content, $match)) {
            $constraints['maxLength'] = (int) $match[1];
        }

        return $constraints;
    }

    private function extractClassDocblock(string $content): string
    {
        if (preg_match('/\/\*\*\s*\n([^*]|\*[^\/])*\*\/\s*\n\s*(?:final\s+)?(?:readonly\s+)?(?:class|enum|interface)/', $content, $match)) {
            return trim(preg_replace('/^\s*\*\s?/m', '', $match[0]));
        }
        return '';
    }

    private function extractMethodDocblock(string $content, string $methodName): string
    {
        $pattern = '/\/\*\*([^*]|\*[^\/])*\*\/\s*\n\s*public\s+function\s+' . preg_quote($methodName) . '/';
        if (preg_match($pattern, $content, $match)) {
            return trim(preg_replace('/^\s*\*\s?/m', '', $match[0]));
        }
        return '';
    }

    private function extractMethodParams(string $content): array
    {
        $params = [];

        if (preg_match('/function\s+\w+\s*\(([^)]*)\)/', $content, $match)) {
            preg_match_all('/(\??\w+)\s+\$(\w+)/', $match[1], $paramMatches, PREG_SET_ORDER);

            foreach ($paramMatches as $pm) {
                $params[] = [
                    'type' => $pm[1],
                    'name' => $pm[2],
                ];
            }
        }

        return $params;
    }

    private function extractRelationships(string $content): array
    {
        $relationships = [];

        // Look for ID value objects that suggest relationships
        preg_match_all('/(\w+)Id\s+\$/', $content, $matches);

        foreach ($matches[1] as $related) {
            if ($related !== basename($this->modulePath)) {
                $relationships[] = $related;
            }
        }

        return array_unique($relationships);
    }

    private function extractStaticFactories(string $content, string $className): array
    {
        $factories = [];

        preg_match_all(
            '/public\s+static\s+function\s+(\w+)\s*\([^)]*\)\s*:\s*self/',
            $content,
            $matches
        );

        return $matches[1] ?? [];
    }

    private function inferHttpMethod(string $methodName): string
    {
        return match (true) {
            $methodName === 'index' || $methodName === 'show' || str_starts_with($methodName, 'get') || $methodName === 'search' => 'GET',
            $methodName === 'store' || str_starts_with($methodName, 'create') || $methodName === 'archive' || $methodName === 'restore' => 'POST',
            $methodName === 'update' => 'PUT',
            $methodName === 'patch' => 'PATCH',
            $methodName === 'destroy' || str_starts_with($methodName, 'delete') => 'DELETE',
            default => 'GET',
        };
    }

    private function inferPath(string $methodName): string
    {
        return match ($methodName) {
            'index' => '/',
            'store' => '/',
            'show' => '/{id}',
            'update' => '/{id}',
            'patch' => '/{id}',
            'destroy' => '/{id}',
            'search' => '/search',
            'archive' => '/{id}/archive',
            'restore' => '/{id}/restore',
            default => '/' . strtolower(preg_replace('/([A-Z])/', '-$1', $methodName)),
        };
    }
}

// ============================================================================
// Documentation Generator
// ============================================================================

final class DocGenerator
{
    private int $filesCreated = 0;

    public function __construct(
        private readonly DocConfig $config,
        private readonly CodeAnalyzer $analyzer
    ) {}

    public function generate(): void
    {
        $this->output("\n📚 Generating Documentation for: {$this->config->moduleName}\n\n");

        // Create output directory
        if (!is_dir($this->config->outputPath)) {
            mkdir($this->config->outputPath, 0755, true);
        }

        if ($this->config->generateReadme) {
            $this->generateReadme();
        }

        if ($this->config->generateApi) {
            $this->generateApiDocs();
        }

        if ($this->config->generateDiagrams) {
            $this->generateDiagrams();
        }

        $this->generateIndex();

        $this->output("\n✅ Documentation generated successfully!\n");
        $this->output("   Files created: {$this->filesCreated}\n");
        $this->output("   Output: {$this->config->outputPath}\n\n");
    }

    private function generateReadme(): void
    {
        $this->output("📄 Generating README...\n");

        $entities = $this->analyzer->getEntities();
        $valueObjects = $this->analyzer->getValueObjects();
        $commands = $this->analyzer->getCommands();
        $queries = $this->analyzer->getQueries();

        $content = "# {$this->config->moduleName} Module\n\n";

        // Overview
        $content .= "## Overview\n\n";
        $content .= "A XOOPS 4.0 module built with Clean Architecture and Domain-Driven Design.\n\n";

        // Features
        $content .= "## Features\n\n";
        $content .= "- " . count($entities) . " domain entities\n";
        $content .= "- " . count($valueObjects) . " value objects\n";
        $content .= "- " . count($commands) . " commands\n";
        $content .= "- " . count($queries) . " queries\n";
        $content .= "- ULID-based identifiers\n";
        $content .= "- Full test coverage\n\n";

        // Entities
        if (!empty($entities)) {
            $content .= "## Entities\n\n";
            foreach ($entities as $entity) {
                $content .= "### {$entity['name']}\n\n";
                if (!empty($entity['docblock'])) {
                    $content .= "{$entity['docblock']}\n\n";
                }

                if (!empty($entity['properties'])) {
                    $content .= "**Properties:**\n\n";
                    foreach ($entity['properties'] as $prop) {
                        $content .= "- `{$prop['name']}`: {$prop['type']}\n";
                    }
                    $content .= "\n";
                }
            }
        }

        // Value Objects
        if (!empty($valueObjects)) {
            $content .= "## Value Objects\n\n";
            foreach ($valueObjects as $vo) {
                $content .= "### {$vo['name']}\n\n";

                if ($vo['isEnum']) {
                    $content .= "*Enum type*\n\n";
                }

                if (!empty($vo['constraints'])) {
                    $content .= "**Constraints:**\n\n";
                    if (isset($vo['constraints']['minLength'])) {
                        $content .= "- Minimum length: {$vo['constraints']['minLength']}\n";
                    }
                    if (isset($vo['constraints']['maxLength'])) {
                        $content .= "- Maximum length: {$vo['constraints']['maxLength']}\n";
                    }
                    $content .= "\n";
                }
            }
        }

        // Commands
        if (!empty($commands)) {
            $content .= "## Commands\n\n";
            $content .= "| Command | Handler | Parameters |\n";
            $content .= "|---------|---------|------------|\n";
            foreach ($commands as $cmd) {
                $params = array_map(fn($p) => "`{$p['name']}`", $cmd['properties']);
                $content .= "| {$cmd['name']} | {$cmd['handler']} | " . implode(', ', $params) . " |\n";
            }
            $content .= "\n";
        }

        // Queries
        if (!empty($queries)) {
            $content .= "## Queries\n\n";
            $content .= "| Query | Handler | Parameters |\n";
            $content .= "|-------|---------|------------|\n";
            foreach ($queries as $query) {
                $params = array_map(fn($p) => "`{$p['name']}`", $query['properties']);
                $content .= "| {$query['name']} | {$query['handler']} | " . implode(', ', $params) . " |\n";
            }
            $content .= "\n";
        }

        // Installation
        $content .= "## Installation\n\n";
        $content .= "1. Copy to `modules/{$this->config->moduleName}/`\n";
        $content .= "2. Run `composer install`\n";
        $content .= "3. Install via XOOPS admin panel\n\n";

        // Requirements
        $content .= "## Requirements\n\n";
        $content .= "- PHP 8.4+\n";
        $content .= "- XOOPS 2.6.0+\n";
        $content .= "- XMF Library\n\n";

        $this->writeFile('README.md', $content);
    }

    private function generateApiDocs(): void
    {
        $endpoints = $this->analyzer->getApiEndpoints();
        if (empty($endpoints)) {
            $this->output("   ⚠️  No API endpoints found, skipping API docs\n");
            return;
        }

        $this->output("📄 Generating API Documentation...\n");

        $content = "# {$this->config->moduleName} API Reference\n\n";
        $content .= "REST API documentation for the {$this->config->moduleName} module.\n\n";

        $content .= "## Base URL\n\n";
        $content .= "```\n/modules/" . strtolower($this->config->moduleName) . "/api/v1\n```\n\n";

        $content .= "## Authentication\n\n";
        $content .= "All endpoints require a valid JWT token in the Authorization header:\n\n";
        $content .= "```\nAuthorization: Bearer <token>\n```\n\n";

        $content .= "## Endpoints\n\n";

        foreach ($endpoints as $endpoint) {
            $content .= "### {$endpoint['method']} {$endpoint['path']}\n\n";

            if (!empty($endpoint['docblock'])) {
                // Extract description from docblock
                $lines = explode("\n", $endpoint['docblock']);
                foreach ($lines as $line) {
                    if (!str_contains($line, '@') && !str_contains($line, '*/') && !str_contains($line, '/*')) {
                        $desc = trim($line);
                        if ($desc) {
                            $content .= "{$desc}\n\n";
                            break;
                        }
                    }
                }
            }

            if (!empty($endpoint['parameters'])) {
                $content .= "**Parameters:**\n\n";
                $content .= "| Name | Type | Description |\n";
                $content .= "|------|------|-------------|\n";
                foreach ($endpoint['parameters'] as $param) {
                    $content .= "| `{$param['name']}` | {$param['type']} | |\n";
                }
                $content .= "\n";
            }

            $content .= "**Response:**\n\n";
            $content .= "```json\n";
            $content .= "{\n";
            $content .= "  \"data\": { ... }\n";
            $content .= "}\n";
            $content .= "```\n\n";
            $content .= "---\n\n";
        }

        // Error responses
        $content .= "## Error Responses\n\n";
        $content .= "| Status | Description |\n";
        $content .= "|--------|-------------|\n";
        $content .= "| 400 | Bad Request - Invalid JSON |\n";
        $content .= "| 401 | Unauthorized - Invalid or missing token |\n";
        $content .= "| 403 | Forbidden - No permission |\n";
        $content .= "| 404 | Not Found - Resource doesn't exist |\n";
        $content .= "| 422 | Validation Error |\n";
        $content .= "| 500 | Server Error |\n\n";

        $content .= "**Error Response Format:**\n\n";
        $content .= "```json\n";
        $content .= "{\n";
        $content .= "  \"error\": {\n";
        $content .= "    \"code\": 422,\n";
        $content .= "    \"message\": \"Validation failed\",\n";
        $content .= "    \"details\": {\n";
        $content .= "      \"title\": [\"The title field is required\"]\n";
        $content .= "    }\n";
        $content .= "  }\n";
        $content .= "}\n";
        $content .= "```\n";

        $this->writeFile('API.md', $content);
    }

    private function generateDiagrams(): void
    {
        $this->output("📄 Generating Diagrams...\n");

        $entities = $this->analyzer->getEntities();
        $valueObjects = $this->analyzer->getValueObjects();

        $content = "# {$this->config->moduleName} Diagrams\n\n";

        // Entity Relationship Diagram
        $content .= "## Entity Relationship Diagram\n\n";
        $content .= "```mermaid\nerDiagram\n";

        foreach ($entities as $entity) {
            $content .= "    {$entity['name']} {\n";
            foreach ($entity['properties'] as $prop) {
                $type = str_replace('?', '', $prop['type']);
                $content .= "        {$type} {$prop['name']}\n";
            }
            $content .= "    }\n";

            // Relationships
            foreach ($entity['relationships'] as $related) {
                $content .= "    {$entity['name']} ||--o{ {$related} : has\n";
            }
        }

        $content .= "```\n\n";

        // Class Diagram
        $content .= "## Domain Model Class Diagram\n\n";
        $content .= "```mermaid\nclassDiagram\n";

        foreach ($entities as $entity) {
            $content .= "    class {$entity['name']} {\n";
            foreach ($entity['properties'] as $prop) {
                $content .= "        -{$prop['type']} {$prop['name']}\n";
            }
            foreach ($entity['methods'] as $method) {
                $content .= "        +{$method['name']}() {$method['returnType']}\n";
            }
            $content .= "    }\n";
        }

        foreach ($valueObjects as $vo) {
            if (!$vo['isEnum']) {
                $content .= "    class {$vo['name']} {\n";
                $content .= "        <<value object>>\n";
                foreach ($vo['methods'] as $method) {
                    $content .= "        +{$method['name']}() {$method['returnType']}\n";
                }
                $content .= "    }\n";
            }
        }

        // Link entities to their value objects
        foreach ($entities as $entity) {
            foreach ($entity['properties'] as $prop) {
                $type = str_replace('?', '', $prop['type']);
                if (isset($valueObjects[$type])) {
                    $content .= "    {$entity['name']} --> {$type}\n";
                }
            }
        }

        $content .= "```\n\n";

        // State Diagram (if status enum exists)
        $statusVo = null;
        foreach ($valueObjects as $vo) {
            if (str_contains($vo['name'], 'Status') && $vo['isEnum']) {
                $statusVo = $vo;
                break;
            }
        }

        if ($statusVo) {
            $content .= "## Entity Lifecycle\n\n";
            $content .= "```mermaid\nstateDiagram-v2\n";
            $content .= "    [*] --> Draft: create()\n";
            $content .= "    Draft --> Published: publish()\n";
            $content .= "    Draft --> Archived: archive()\n";
            $content .= "    Published --> Archived: archive()\n";
            $content .= "    Archived --> Draft: restore()\n";
            $content .= "```\n\n";
        }

        // Architecture Overview
        $content .= "## Architecture Overview\n\n";
        $content .= "```mermaid\nflowchart TB\n";
        $content .= "    subgraph Presentation\n";
        $content .= "        C[Controllers]\n";
        $content .= "        T[Templates]\n";
        $content .= "    end\n";
        $content .= "    \n";
        $content .= "    subgraph Application\n";
        $content .= "        CMD[Commands]\n";
        $content .= "        QRY[Queries]\n";
        $content .= "        H[Handlers]\n";
        $content .= "    end\n";
        $content .= "    \n";
        $content .= "    subgraph Domain\n";
        $content .= "        E[Entities]\n";
        $content .= "        VO[Value Objects]\n";
        $content .= "        RI[Repository Interface]\n";
        $content .= "    end\n";
        $content .= "    \n";
        $content .= "    subgraph Infrastructure\n";
        $content .= "        R[MySQL Repository]\n";
        $content .= "        DB[(Database)]\n";
        $content .= "    end\n";
        $content .= "    \n";
        $content .= "    C --> CMD\n";
        $content .= "    C --> QRY\n";
        $content .= "    CMD --> H\n";
        $content .= "    QRY --> H\n";
        $content .= "    H --> E\n";
        $content .= "    H --> VO\n";
        $content .= "    H --> RI\n";
        $content .= "    RI -.-> R\n";
        $content .= "    R --> DB\n";
        $content .= "```\n";

        $this->writeFile('Diagrams.md', $content);
    }

    private function generateIndex(): void
    {
        $this->output("📄 Generating Index...\n");

        $content = "# {$this->config->moduleName} Documentation\n\n";
        $content .= "Welcome to the {$this->config->moduleName} module documentation.\n\n";

        $content .= "## Contents\n\n";

        if ($this->config->generateReadme) {
            $content .= "- [README](README.md) - Module overview and features\n";
        }
        if ($this->config->generateApi) {
            $content .= "- [API Reference](API.md) - REST API documentation\n";
        }
        if ($this->config->generateDiagrams) {
            $content .= "- [Diagrams](Diagrams.md) - Architecture and entity diagrams\n";
        }

        $content .= "\n## Quick Links\n\n";
        $content .= "- [Installation](#installation)\n";
        $content .= "- [Configuration](#configuration)\n";
        $content .= "- [Usage](#usage)\n\n";

        $content .= "## Generated\n\n";
        $content .= "This documentation was automatically generated by the XOOPS 4.0 Documentation Generator.\n\n";
        $content .= "Last updated: " . date('Y-m-d H:i:s') . "\n";

        $this->writeFile('index.md', $content);
    }

    private function writeFile(string $filename, string $content): void
    {
        $path = $this->config->outputPath . '/' . $filename;
        file_put_contents($path, $content);
        $this->filesCreated++;
        $this->output("   ✓ Created: {$filename}\n");
    }

    private function output(string $message): void
    {
        echo $message;
    }
}

// ============================================================================
// Main Execution
// ============================================================================

if (php_sapi_name() === 'cli') {
    $config = DocConfig::fromArgs($argv);
    $analyzer = (new CodeAnalyzer($config->modulePath, $config->namespace))->analyze();
    $generator = new DocGenerator($config, $analyzer);
    $generator->generate();
}
