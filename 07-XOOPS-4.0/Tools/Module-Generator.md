# XOOPS Module Generator

## Overview

The XOOPS Module Generator is a CLI tool that scaffolds complete module structures following modern best practices, including PSR-4 autoloading, clean architecture patterns, and XOOPS 4.0 conventions.

## Installation

### Via Composer

```bash
composer global require xoops/module-generator
```

### Via XOOPS CLI

```bash
xoops module:generator install
```

## Quick Start

### Generate a Basic Module

```bash
xoops generate:module MyModule
```

This creates a complete module structure in `modules/mymodule/`.

### Generate with Options

```bash
xoops generate:module MyModule \
  --author="Your Name" \
  --description="Module description" \
  --with-admin \
  --with-blocks \
  --with-templates \
  --with-api
```

## Command Reference

### `generate:module`

Creates a new module scaffold.

```bash
xoops generate:module <name> [options]
```

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--author` | Module author name | Git config |
| `--description` | Module description | Empty |
| `--namespace` | PHP namespace | XoopsModules\\{Name} |
| `--with-admin` | Include admin panel | true |
| `--with-blocks` | Include sample blocks | true |
| `--with-templates` | Include templates | true |
| `--with-api` | Include REST API | false |
| `--with-tests` | Include test suite | false |
| `--architecture` | Architecture style | clean |

### `generate:entity`

Creates a domain entity with repository.

```bash
xoops generate:entity MyModule Article \
  --properties="title:string,content:text,status:enum" \
  --with-handler \
  --with-form
```

**Generated files:**
- `src/Entity/Article.php`
- `src/Repository/ArticleRepository.php`
- `src/Repository/ArticleRepositoryInterface.php`
- `src/Handler/ArticleHandler.php` (if `--with-handler`)
- `src/Form/ArticleForm.php` (if `--with-form`)

### `generate:service`

Creates a service class.

```bash
xoops generate:service MyModule ArticleService \
  --methods="create,update,delete,findById,findAll"
```

### `generate:controller`

Creates a controller with actions.

```bash
xoops generate:controller MyModule ArticleController \
  --actions="index,show,create,edit,delete" \
  --resource
```

### `generate:block`

Creates a block with template.

```bash
xoops generate:block MyModule RecentArticles \
  --options="limit:int:5,category:select"
```

### `generate:migration`

Creates a database migration.

```bash
xoops generate:migration MyModule create_articles_table
```

## Generated Structure

### Basic Module

```
modules/mymodule/
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   └── Helper.php
├── config/
│   ├── routes.php
│   └── services.php
├── templates/
│   ├── admin/
│   └── blocks/
├── language/
│   └── english/
├── sql/
│   └── mysql.sql
├── admin/
│   ├── index.php
│   └── menu.php
├── module.json
├── composer.json
└── README.md
```

### With Clean Architecture

```
modules/mymodule/
├── src/
│   ├── Application/
│   │   ├── Command/
│   │   ├── Query/
│   │   └── Service/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   ├── Event/
│   │   └── Repository/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   └── Service/
│   └── Presentation/
│       ├── Controller/
│       └── Form/
├── config/
├── templates/
└── ...
```

## Configuration

### Generator Config File

Create `.xoops-generator.json` in your project:

```json
{
    "defaults": {
        "author": "Your Name",
        "license": "GPL-2.0-or-later",
        "namespace_prefix": "XoopsModules",
        "architecture": "clean",
        "php_version": "8.4"
    },
    "templates": {
        "entity": "templates/entity.php.twig",
        "service": "templates/service.php.twig"
    },
    "hooks": {
        "post_generate": "composer dump-autoload"
    }
}
```

## Custom Templates

### Override Default Templates

Place custom Twig templates in `.xoops-generator/templates/`:

```twig
{# .xoops-generator/templates/entity.php.twig #}
<?php

declare(strict_types=1);

namespace {{ namespace }}\Entity;

final class {{ class_name }}
{
{% for property in properties %}
    private {{ property.type }} ${{ property.name }};
{% endfor %}

    // Custom template content...
}
```

## Interactive Mode

Run without arguments for interactive prompts:

```bash
xoops generate:module

? Module name: MyModule
? Author: Your Name
? Description: My awesome module
? Include admin panel? Yes
? Include blocks? Yes
? Include REST API? No
? Architecture style? Clean Architecture

Generating module...
✓ Created modules/mymodule/
✓ Created 23 files
✓ Module ready!
```

## Integration

### With Composer

Generated modules include `composer.json`:

```json
{
    "name": "xoops-modules/mymodule",
    "autoload": {
        "psr-4": {
            "XoopsModules\\MyModule\\": "src/"
        }
    }
}
```

### With PHPUnit

If `--with-tests` is specified:

```
tests/
├── Unit/
│   ├── Entity/
│   └── Service/
├── Integration/
├── bootstrap.php
└── phpunit.xml
```

## Related Documentation

- [[VS-Code-Snippets]] - IDE snippets
- [[../../03-Module-Development/Module-Structure]] - Directory structure guide
- [[Test-Generator]] - Generate tests
- [[../../03-Module-Development/Best-Practices/Code-Organization]] - Architecture patterns
