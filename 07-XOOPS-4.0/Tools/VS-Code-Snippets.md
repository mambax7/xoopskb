# VS Code Snippets for XOOPS Development

## Overview

This collection of VS Code snippets accelerates XOOPS module development by providing quick templates for common patterns, classes, and structures.

## Installation

### Manual Installation

1. Open VS Code
2. Press `Ctrl+Shift+P` (Windows/Linux) or `Cmd+Shift+P` (Mac)
3. Type "Preferences: Configure User Snippets"
4. Select "php.json"
5. Add the snippets below

### Extension Installation

Install the XOOPS Snippets extension:

```bash
code --install-extension xoops.xoops-snippets
```

## Available Snippets

### Module Structure

#### `xoops-module` - Module Manifest

```json
{
  "XOOPS Module Manifest": {
    "prefix": "xoops-module",
    "body": [
      "<?php",
      "",
      "declare(strict_types=1);",
      "",
      "\\$modversion = [",
      "    'name'        => '${1:Module Name}',",
      "    'version'     => '${2:1.0.0}',",
      "    'description' => '${3:Module description}',",
      "    'author'      => '${4:Your Name}',",
      "    'license'     => 'GPL 2.0',",
      "    'dirname'     => basename(__DIR__),",
      "",
      "    'hasAdmin'    => 1,",
      "    'adminindex'  => 'admin/index.php',",
      "    'adminmenu'   => 'admin/menu.php',",
      "    'hasMain'     => 1,",
      "",
      "    'sqlfile'     => ['mysql' => 'sql/mysql.sql'],",
      "    'tables'      => ['${5:tablename}'],",
      "",
      "    'templates'   => [",
      "        ['file' => '${6:template}.tpl', 'description' => '${7:Description}'],",
      "    ],",
      "];",
      ""
    ],
    "description": "XOOPS module xoops_version.php"
  }
}
```

#### `xoops-entity` - Domain Entity

```json
{
  "XOOPS Entity": {
    "prefix": "xoops-entity",
    "body": [
      "<?php",
      "",
      "declare(strict_types=1);",
      "",
      "namespace XoopsModules\\\\${1:ModuleName}\\\\Entity;",
      "",
      "use XoopsModules\\\\${1}\\\\ValueObject\\\\${2:EntityName}Id;",
      "",
      "final class ${2}",
      "{",
      "    private bool \\$isNew = true;",
      "",
      "    public function __construct(",
      "        private ${2}Id \\$id,",
      "        private string \\$${3:property},",
      "        private \\\\DateTimeImmutable \\$createdAt,",
      "        private ?\\\\DateTimeImmutable \\$updatedAt = null",
      "    ) {}",
      "",
      "    public static function create(string \\$${3}): self",
      "    {",
      "        return new self(",
      "            id: ${2}Id::generate(),",
      "            ${3}: \\$${3},",
      "            createdAt: new \\\\DateTimeImmutable()",
      "        );",
      "    }",
      "",
      "    public function getId(): ${2}Id",
      "    {",
      "        return \\$this->id;",
      "    }",
      "",
      "    public function get${3/(.*)/${3:/capitalize}/}(): string",
      "    {",
      "        return \\$this->${3};",
      "    }",
      "",
      "    public function isNew(): bool",
      "    {",
      "        return \\$this->isNew;",
      "    }",
      "}",
      ""
    ],
    "description": "XOOPS domain entity class"
  }
}
```

#### `xoops-service` - Service Class

```json
{
  "XOOPS Service": {
    "prefix": "xoops-service",
    "body": [
      "<?php",
      "",
      "declare(strict_types=1);",
      "",
      "namespace XoopsModules\\\\${1:ModuleName}\\\\Service;",
      "",
      "use XoopsModules\\\\${1}\\\\Repository\\\\${2:Entity}RepositoryInterface;",
      "use XoopsModules\\\\${1}\\\\Entity\\\\${2};",
      "use XoopsModules\\\\${1}\\\\DTO\\\\Create${2}DTO;",
      "",
      "final class ${2}Service",
      "{",
      "    public function __construct(",
      "        private readonly ${2}RepositoryInterface \\$repository",
      "    ) {}",
      "",
      "    public function create(Create${2}DTO \\$dto): ${2}",
      "    {",
      "        \\$entity = ${2}::create(\\$dto->${3:property});",
      "        \\$this->repository->save(\\$entity);",
      "        return \\$entity;",
      "    }",
      "",
      "    public function findById(int \\$id): ?${2}",
      "    {",
      "        return \\$this->repository->findById(\\$id);",
      "    }",
      "}",
      ""
    ],
    "description": "XOOPS service class"
  }
}
```

#### `xoops-repository` - Repository Interface

```json
{
  "XOOPS Repository Interface": {
    "prefix": "xoops-repository",
    "body": [
      "<?php",
      "",
      "declare(strict_types=1);",
      "",
      "namespace XoopsModules\\\\${1:ModuleName}\\\\Repository;",
      "",
      "use XoopsModules\\\\${1}\\\\Entity\\\\${2:Entity};",
      "",
      "interface ${2}RepositoryInterface",
      "{",
      "    public function findById(int \\$id): ?${2};",
      "    public function findAll(): array;",
      "    public function save(${2} \\$entity): void;",
      "    public function delete(${2} \\$entity): void;",
      "}",
      ""
    ],
    "description": "XOOPS repository interface"
  }
}
```

#### `xoops-handler` - Object Handler

```json
{
  "XOOPS Object Handler": {
    "prefix": "xoops-handler",
    "body": [
      "<?php",
      "",
      "declare(strict_types=1);",
      "",
      "namespace XoopsModules\\\\${1:ModuleName}\\\\Handler;",
      "",
      "use XoopsPersistableObjectHandler;",
      "",
      "class ${2:Entity}Handler extends XoopsPersistableObjectHandler",
      "{",
      "    public function __construct(\\\\XoopsDatabase \\$db = null)",
      "    {",
      "        parent::__construct(",
      "            \\$db,",
      "            '${3:table_name}',",
      "            ${2}::class,",
      "            '${4:id_field}',",
      "            '${5:title_field}'",
      "        );",
      "    }",
      "}",
      ""
    ],
    "description": "XOOPS object handler class"
  }
}
```

### Form Elements

#### `xoops-form` - Theme Form

```json
{
  "XOOPS Theme Form": {
    "prefix": "xoops-form",
    "body": [
      "\\$form = new \\\\XoopsThemeForm(",
      "    '${1:Form Title}',",
      "    '${2:formname}',",
      "    '${3:action.php}',",
      "    'post',",
      "    true",
      ");",
      "",
      "\\$form->addElement(new \\\\XoopsFormText('${4:Label}', '${5:name}', 50, 255, \\$${6:value}));",
      "\\$form->addElement(new \\\\XoopsFormButton('', 'submit', _SUBMIT, 'submit'));",
      "",
      "echo \\$form->render();"
    ],
    "description": "XOOPS theme form"
  }
}
```

### Database

#### `xoops-criteria` - Criteria Query

```json
{
  "XOOPS Criteria": {
    "prefix": "xoops-criteria",
    "body": [
      "\\$criteria = new \\\\CriteriaCompo();",
      "\\$criteria->add(new \\\\Criteria('${1:field}', ${2:value}));",
      "\\$criteria->setSort('${3:sort_field}');",
      "\\$criteria->setOrder('${4|ASC,DESC|}');",
      "\\$criteria->setLimit(${5:10});",
      "\\$criteria->setStart(${6:0});",
      "",
      "\\$items = \\$handler->getObjects(\\$criteria);"
    ],
    "description": "XOOPS criteria query"
  }
}
```

### Templates

#### `xoops-block-tpl` - Block Template

```json
{
  "XOOPS Block Template": {
    "prefix": "xoops-block-tpl",
    "body": [
      "<div class=\"block-${1:name}\">",
      "    <{foreach item=item from=\\$block.items}>",
      "    <div class=\"block-item\">",
      "        <h4><a href=\"<{\\$item.link}>\"><{\\$item.title}></a></h4>",
      "        <{if \\$block.show_summary}>",
      "            <p><{\\$item.summary}></p>",
      "        <{/if}>",
      "    </div>",
      "    <{/foreach}>",
      "</div>"
    ],
    "description": "XOOPS block Smarty template"
  }
}
```

## Keyboard Shortcuts

| Shortcut | Snippet | Description |
|----------|---------|-------------|
| `xm` | `xoops-module` | Module manifest |
| `xe` | `xoops-entity` | Entity class |
| `xs` | `xoops-service` | Service class |
| `xr` | `xoops-repository` | Repository interface |
| `xh` | `xoops-handler` | Object handler |
| `xf` | `xoops-form` | Theme form |
| `xc` | `xoops-criteria` | Criteria query |

## Related Documentation

- [[Module-Generator]] - Scaffold complete modules
- [[../../03-Module-Development/Best-Practices/Code-Organization]] - Project structure
- [[Best-Practices]] - Development guidelines
