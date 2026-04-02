---
title: Module Development
description: Comprehensive guide to developing XOOPS modules using modern PHP practices
tags:
  - xoops
  - module-development
  - php
  - mvc
  - tutorial
created: 2025-01-28
updated: 2025-01-28
---

# Module Development

This section provides comprehensive documentation for developing XOOPS modules using modern PHP practices, design patterns, and best practices.

## Overview

XOOPS module development has evolved significantly over the years. Modern modules leverage:

- **MVC Architecture** - Clean separation of concerns
- **PHP 8.x Features** - Type declarations, attributes, named arguments
- **Design Patterns** - Repository, DTO, Service Layer patterns
- **Testing** - PHPUnit with modern testing practices
- **XMF Framework** - XOOPS Module Framework utilities

## Documentation Structure

### Tutorials

Step-by-step guides for building XOOPS modules from scratch.

- [[Tutorials/Hello-World-Module]] - Your first XOOPS module
- [[Tutorials/Building-a-CRUD-Module]] - Complete Create, Read, Update, Delete functionality

### Design Patterns

Architectural patterns used in modern XOOPS module development.

- [[Patterns/MVC-Pattern]] - Model-View-Controller architecture
- [[Patterns/Repository-Pattern]] - Data access abstraction
- [[Patterns/DTO-Pattern]] - Data Transfer Objects for clean data flow

### Best Practices

Guidelines for writing maintainable, high-quality code.

- [[Best-Practices/Clean-Code]] - Clean code principles for XOOPS
- [[Best-Practices/Code-Smells]] - Common anti-patterns and how to fix them
- [[Best-Practices/Testing]] - PHPUnit testing strategies

### Examples

Real-world module analysis and implementation examples.

- [[Publisher-Module-Analysis]] - Deep dive into the Publisher module

## Module Directory Structure

A well-organized XOOPS module follows this directory structure:

```
/modules/mymodule/
    /admin/
        admin_header.php
        admin_footer.php
        index.php
        menu.php
    /assets/
        /css/
        /js/
        /images/
    /blocks/
        myblock.php
    /class/
        /Controller/
        /Entity/
        /Repository/
        /Service/
    /include/
        common.php
        install.php
        uninstall.php
        update.php
    /language/
        /english/
            admin.php
            main.php
            modinfo.php
    /preloads/
        core.php
    /sql/
        mysql.sql
    /templates/
        /admin/
        /blocks/
        main_index.tpl
    /test/
        bootstrap.php
        /Unit/
        /Integration/
    index.php
    xoops_version.php
```

## Key Files Explained

### xoops_version.php

The module definition file that tells XOOPS about your module:

```php
<?php
$modversion = [];

// Basic Information
$modversion['name']        = 'My Module';
$modversion['version']     = 1.00;
$modversion['description'] = 'A sample XOOPS module';
$modversion['author']      = 'Your Name';
$modversion['credits']     = 'Your Team';
$modversion['license']     = 'GPL 2.0 or later';
$modversion['dirname']     = 'mymodule';
$modversion['image']       = 'assets/images/logo.png';

// Module Flags
$modversion['hasMain']     = 1;  // Has frontend pages
$modversion['hasAdmin']    = 1;  // Has admin section
$modversion['system_menu'] = 1;  // Show in admin menu

// Admin Configuration
$modversion['adminindex']  = 'admin/index.php';
$modversion['adminmenu']   = 'admin/menu.php';

// Database
$modversion['sqlfile']['mysql'] = 'sql/mysql.sql';
$modversion['tables'] = [
    'mymodule_items',
    'mymodule_categories',
];

// Templates
$modversion['templates'][] = [
    'file'        => 'mymodule_index.tpl',
    'description' => 'Index page template',
];

// Blocks
$modversion['blocks'][] = [
    'file'        => 'myblock.php',
    'name'        => 'My Block',
    'description' => 'Displays recent items',
    'show_func'   => 'mymodule_block_show',
    'edit_func'   => 'mymodule_block_edit',
    'template'    => 'mymodule_block.tpl',
];

// Module Preferences
$modversion['config'][] = [
    'name'        => 'items_per_page',
    'title'       => '_MI_MYMODULE_ITEMS_PER_PAGE',
    'description' => '_MI_MYMODULE_ITEMS_PER_PAGE_DESC',
    'formtype'    => 'textbox',
    'valuetype'   => 'int',
    'default'     => 10,
];
```

### Common Include File

Create a common bootstrap file for your module:

```php
<?php
// include/common.php

if (!defined('XOOPS_ROOT_PATH')) {
    die('XOOPS root path not defined');
}

// Module constants
define('MYMODULE_DIRNAME', 'mymodule');
define('MYMODULE_PATH', XOOPS_ROOT_PATH . '/modules/' . MYMODULE_DIRNAME);
define('MYMODULE_URL', XOOPS_URL . '/modules/' . MYMODULE_DIRNAME);

// Autoload classes
require_once MYMODULE_PATH . '/class/autoload.php';
```

## PHP Version Requirements

XOOPS 2.5.12 requires PHP 8.2 as a minimum. Modern modules should leverage:

- **Constructor Property Promotion**
- **Named Arguments**
- **Union Types**
- **Match Expressions**
- **Attributes**
- **Nullsafe Operator**

## Getting Started

1. Start with the [[Tutorials/Hello-World-Module]] tutorial
2. Progress to [[Tutorials/Building-a-CRUD-Module]]
3. Study the [[Patterns/MVC-Pattern]] for architecture guidance
4. Apply [[Best-Practices/Clean-Code]] practices throughout
5. Implement [[Best-Practices/Testing]] from the beginning

## Related Resources

- [[../05-XMF-Framework/XMF-Framework]] - XOOPS Module Framework utilities
- [[Database-Operations]] - Working with the XOOPS database
- [[../04-API-Reference/Template/Template-System]] - Smarty templating in XOOPS
- [[../02-Core-Concepts/Security/Security-Best-Practices]] - Securing your module

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-01-28 | Initial documentation |
