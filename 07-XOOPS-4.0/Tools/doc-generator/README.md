# XOOPS 4.0 Module Documentation Generator

Automatically generates documentation from module source code including API docs, entity diagrams, and README files.

## Features

- **README Generation** - Module overview with entities, value objects, commands, queries
- **API Documentation** - REST endpoint documentation from controller analysis
- **Mermaid Diagrams** - ER diagrams, class diagrams, state diagrams, architecture overview
- **Code Analysis** - Extracts docblocks, constraints, relationships automatically

## Usage

```bash
# Generate all documentation
php generate-docs.php ./modules/articles

# Output to specific directory
php generate-docs.php ./modules/blog --output=./docs/blog

# Generate only specific parts
php generate-docs.php ./modules/notes --api --diagrams
php generate-docs.php ./modules/notes --readme

# Show help
php generate-docs.php --help
```

## Options

| Option | Description |
|--------|-------------|
| `--output=<path>` | Output directory (default: module/docs) |
| `--format=<format>` | Output format: markdown, html (default: markdown) |
| `--api` | Generate API documentation only |
| `--diagrams` | Generate diagrams only |
| `--readme` | Generate README only |
| `--all` | Generate all documentation (default) |
| `--help` | Show help message |

## Generated Files

```
docs/
├── index.md      # Documentation index
├── README.md     # Module overview
├── API.md        # REST API documentation
└── Diagrams.md   # Mermaid diagrams
```

## What Gets Analyzed

### Domain Layer
- **Entities** - Properties, methods, relationships
- **Value Objects** - Constraints (min/max length), methods
- **Exceptions** - Factory methods

### Application Layer
- **Commands** - Parameters, associated handlers
- **Queries** - Parameters, associated handlers

### Infrastructure Layer
- **API Controllers** - Endpoints, HTTP methods, parameters

## Example Output

### README.md

```markdown
# Articles Module

## Overview

A XOOPS 4.0 module built with Clean Architecture and Domain-Driven Design.

## Features

- 2 domain entities
- 5 value objects
- 3 commands
- 2 queries

## Entities

### Article

**Properties:**

- `id`: ArticleId
- `title`: ArticleTitle
- `content`: ArticleContent
- `status`: ArticleStatus

## Commands

| Command | Handler | Parameters |
|---------|---------|------------|
| CreateArticleCommand | CreateArticleHandler | `userId`, `title`, `content` |
```

### API.md

```markdown
# Articles API Reference

## Base URL

/modules/articles/api/v1

## Endpoints

### GET /

List all articles.

**Response:**

{
  "data": [ ... ]
}

### POST /

Create a new article.

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `title` | string | Article title |
| `content` | string | Article content |
```

### Diagrams.md

Contains Mermaid diagrams that render in:
- GitHub
- GitLab
- Obsidian
- MkDocs (with superfences extension)
- VS Code (with Mermaid extension)

## Integration

### GitHub Actions

```yaml
- name: Generate Documentation
  run: php tools/generate-docs.php . --output=docs
```

### MkDocs

The generated Markdown files work directly with MkDocs Material theme.

### Obsidian

Generated files are compatible with Obsidian's Mermaid rendering.

## Customization

To customize the output:

1. Fork/copy `generate-docs.php`
2. Modify the `DocGenerator` class methods
3. Update templates in the `generate*()` methods

## Related Tools

- [[../Module-Generator|generate-module.php]] - Scaffold new modules
- [[../Test-Generator|generate-tests.php]] - Generate test suites
