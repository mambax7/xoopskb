---
title: XOOPS Module System
description: Module lifecycle, XoopsModule class, module installation/uninstallation, module hooks, and module management
created: 2024-01-31
updated: 2024-01-31
version: 2.5.11
tags:
  - api
  - module
  - xoopsmodule
  - lifecycle
  - installation
  - hooks
aliases:
  - Module System
  - Module Lifecycle
  - Module Management
---

# XOOPS Module System

The XOOPS Module System provides a complete framework for developing, installing, managing, and extending module functionality. Modules are self-contained packages that extend XOOPS with additional features and capabilities.

## Module Architecture

```mermaid
graph TD
    A[Module Package] -->|contains| B[xoops_version.php]
    A -->|contains| C[Admin Interface]
    A -->|contains| D[User Interface]
    A -->|contains| E[Class Files]
    A -->|contains| F[SQL Schema]

    B -->|defines| G[Module Metadata]
    B -->|defines| H[Admin Pages]
    B -->|defines| I[User Pages]
    B -->|defines| J[Blocks]
    B -->|defines| K[Hooks]

    L[Module Manager] -->|reads| B
    L -->|controls| M[Installation]
    L -->|controls| N[Activation]
    L -->|controls| O[Update]
    L -->|controls| P[Uninstallation]
```

## Module Structure

Standard XOOPS module directory structure:

```
mymodule/
├── xoops_version.php          # Module manifest and configuration
├── admin.php                  # Admin main page
├── index.php                  # User main page
├── admin/                     # Admin pages directory
│   ├── main.php
│   ├── manage.php
│   └── settings.php
├── class/                     # Module classes
│   ├── Handler/
│   │   ├── ItemHandler.php
│   │   └── CategoryHandler.php
│   └── Objects/
│       ├── Item.php
│       └── Category.php
├── sql/                       # Database schemas
│   ├── mysql.sql
│   └── postgres.sql
├── include/                   # Include files
│   ├── common.inc.php
│   └── functions.php
├── templates/                 # Module templates
│   ├── admin/
│   │   └── main.tpl
│   └── user/
│       ├── index.tpl
│       └── item.tpl
├── blocks/                    # Module blocks
│   └── blocks.php
├── tests/                     # Unit tests
├── language/                  # Language files
│   ├── english/
│   │   └── main.php
│   └── spanish/
│       └── main.php
└── docs/                      # Documentation
```

## XoopsModule Class

The XoopsModule class represents an installed XOOPS module.

### Class Overview

```php
namespace Xoops\Core\Module;

class XoopsModule extends XoopsObject
{
    protected int $moduleid = 0;
    protected string $name = '';
    protected string $dirname = '';
    protected string $version = '';
    protected string $description = '';
    protected array $config = [];
    protected array $blocks = [];
    protected array $adminPages = [];
    protected array $userPages = [];
}
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$moduleid` | int | Unique module ID |
| `$name` | string | Module display name |
| `$dirname` | string | Module directory name |
| `$version` | string | Current module version |
| `$description` | string | Module description |
| `$config` | array | Module configuration |
| `$blocks` | array | Module blocks |
| `$adminPages` | array | Admin panel pages |
| `$userPages` | array | User-facing pages |

### Constructor

```php
public function __construct()
```

Creates a new module instance and initializes variables.

### Core Methods

#### getName

Gets the module's display name.

```php
public function getName(): string
```

**Returns:** `string` - Module display name

**Example:**
```php
$module = new XoopsModule();
$module->setVar('name', 'Publisher');
echo $module->getName(); // "Publisher"
```

#### getDirname

Gets the module's directory name.

```php
public function getDirname(): string
```

**Returns:** `string` - Module directory name

**Example:**
```php
echo $module->getDirname(); // "publisher"
```

#### getVersion

Gets the current module version.

```php
public function getVersion(): string
```

**Returns:** `string` - Version string

**Example:**
```php
echo $module->getVersion(); // "2.1.0"
```

#### getDescription

Gets the module description.

```php
public function getDescription(): string
```

**Returns:** `string` - Module description

**Example:**
```php
$desc = $module->getDescription();
```

#### getConfig

Retrieves module configuration.

```php
public function getConfig(string $key = null): mixed
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | string | Configuration key (null for all) |

**Returns:** `mixed` - Configuration value or array

**Example:**
```php
$config = $module->getConfig();
$itemsPerPage = $module->getConfig('items_per_page');
```

#### setConfig

Sets module configuration.

```php
public function setConfig(string $key, mixed $value): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | string | Configuration key |
| `$value` | mixed | Configuration value |

**Example:**
```php
$module->setConfig('items_per_page', 20);
$module->setConfig('enable_cache', true);
```

#### getPath

Gets the full file system path to the module.

```php
public function getPath(): string
```

**Returns:** `string` - Absolute module directory path

**Example:**
```php
$path = $module->getPath(); // "/var/www/xoops/modules/publisher"
$classPath = $module->getPath() . '/class';
```

#### getUrl

Gets the URL to the module.

```php
public function getUrl(): string
```

**Returns:** `string` - Module URL

**Example:**
```php
$url = $module->getUrl(); // "http://example.com/modules/publisher"
```

## Module Installation Process

### xoops_module_install Function

The module installation function defined in `xoops_version.php`:

```php
function xoops_module_install_modulename($module)
{
    // $module is an XoopsModule instance

    // Create database tables
    // Initialize default configuration
    // Create default folders
    // Set up file permissions

    return true; // Success
}
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module` | XoopsModule | The module being installed |

**Returns:** `bool` - True on success, false on failure

**Example:**
```php
function xoops_module_install_publisher($module)
{
    // Get module path
    $modulePath = $module->getPath();

    // Create uploads directory
    $uploadsPath = XOOPS_ROOT_PATH . '/uploads/publisher';
    if (!is_dir($uploadsPath)) {
        mkdir($uploadsPath, 0755, true);
    }

    // Get database connection
    global $xoopsDB;

    // Execute SQL installation script
    $sqlFile = $modulePath . '/sql/mysql.sql';
    if (file_exists($sqlFile)) {
        $sqlQueries = file_get_contents($sqlFile);
        // Execute queries (simplified)
        $xoopsDB->queryFromFile($sqlFile);
    }

    // Set default configuration
    $module->setConfig('items_per_page', 10);
    $module->setConfig('enable_comments', true);

    return true;
}
```

### xoops_module_uninstall Function

The module uninstallation function:

```php
function xoops_module_uninstall_modulename($module)
{
    // Drop database tables
    // Remove uploaded files
    // Clean up configuration

    return true;
}
```

**Example:**
```php
function xoops_module_uninstall_publisher($module)
{
    global $xoopsDB;

    // Drop tables
    $tables = ['publisher_items', 'publisher_categories', 'publisher_comments'];
    foreach ($tables as $table) {
        $xoopsDB->query('DROP TABLE IF EXISTS ' . $xoopsDB->prefix($table));
    }

    // Remove upload folder
    $uploadsPath = XOOPS_ROOT_PATH . '/uploads/publisher';
    if (is_dir($uploadsPath)) {
        // Recursive directory deletion
        $this->recursiveRemoveDir($uploadsPath);
    }

    return true;
}
```

## Module Hooks

Module hooks allow modules to integrate with other modules and the system.

### Hook Declaration

In `xoops_version.php`:

```php
$modversion['hooks'] = [
    'system.page.footer' => [
        'function' => 'publisher_page_footer'
    ],
    'user.profile.view' => [
        'function' => 'publisher_user_articles'
    ],
];
```

### Hook Implementation

```php
// In a module file (e.g., include/hooks.php)

function publisher_page_footer()
{
    // Return HTML for footer
    return '<div class="publisher-footer">Publisher Footer Content</div>';
}

function publisher_user_articles($user_id)
{
    global $xoopsDB;

    // Get user's articles
    $result = $xoopsDB->query(
        'SELECT * FROM ' . $xoopsDB->prefix('publisher_articles') .
        ' WHERE author_id = ? ORDER BY published DESC LIMIT 5',
        [$user_id]
    );

    $articles = [];
    while ($row = $xoopsDB->fetchAssoc($result)) {
        $articles[] = $row;
    }

    return $articles;
}
```

### Available System Hooks

| Hook | Parameters | Description |
|------|-----------|-------------|
| `system.page.header` | None | Page header output |
| `system.page.footer` | None | Page footer output |
| `user.login.success` | $user object | After user login |
| `user.logout` | $user object | After user logout |
| `user.profile.view` | $user_id | Viewing user profile |
| `module.install` | $module object | Module installation |
| `module.uninstall` | $module object | Module uninstallation |

## Module Manager Service

The ModuleManager service handles module operations.

### Methods

#### getModule

Retrieves a module by name.

```php
public function getModule(string $dirname): ?XoopsModule
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$dirname` | string | Module directory name |

**Returns:** `?XoopsModule` - Module instance or null

**Example:**
```php
$moduleManager = $kernel->getService('module');
$publisher = $moduleManager->getModule('publisher');
if ($publisher) {
    echo $publisher->getName();
}
```

#### getAllModules

Gets all installed modules.

```php
public function getAllModules(bool $activeOnly = true): array
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$activeOnly` | bool | Only return active modules |

**Returns:** `array` - Array of XoopsModule objects

**Example:**
```php
$activeModules = $moduleManager->getAllModules(true);
foreach ($activeModules as $module) {
    echo $module->getName() . " - " . $module->getVersion() . "\n";
}
```

#### isModuleActive

Checks if a module is active.

```php
public function isModuleActive(string $dirname): bool
```

**Example:**
```php
if ($moduleManager->isModuleActive('publisher')) {
    // Publisher module is active
}
```

#### activateModule

Activates a module.

```php
public function activateModule(string $dirname): bool
```

**Example:**
```php
if ($moduleManager->activateModule('publisher')) {
    echo "Publisher activated";
}
```

#### deactivateModule

Deactivates a module.

```php
public function deactivateModule(string $dirname): bool
```

**Example:**
```php
if ($moduleManager->deactivateModule('publisher')) {
    echo "Publisher deactivated";
}
```

## Module Configuration (xoops_version.php)

Complete module manifest example:

```php
<?php
/**
 * Module manifest for Publisher
 */

$modversion = [
    'name' => 'Publisher',
    'version' => '2.1.0',
    'description' => 'Professional content publishing module',
    'author' => 'XOOPS Community',
    'credits' => 'Based on original work by...',
    'license' => 'GPL v2',
    'official' => 1,
    'image' => 'images/logo.png',
    'dirname' => 'publisher',
    'onInstall' => 'xoops_module_install_publisher',
    'onUpdate' => 'xoops_module_update_publisher',
    'onUninstall' => 'xoops_module_uninstall_publisher',

    // Admin pages
    'hasAdmin' => 1,
    'adminindex' => 'admin/main.php',
    'adminmenu' => [
        [
            'title' => 'Dashboard',
            'link' => 'admin/main.php',
            'icon' => 'dashboard.png'
        ],
        [
            'title' => 'Manage Items',
            'link' => 'admin/items.php',
            'icon' => 'items.png'
        ],
        [
            'title' => 'Settings',
            'link' => 'admin/settings.php',
            'icon' => 'settings.png'
        ]
    ],

    // User pages
    'hasMain' => 1,
    'main_file' => 'index.php',

    // Blocks
    'blocks' => [
        [
            'file' => 'blocks/recent.php',
            'name' => 'Recent Articles',
            'description' => 'Display recent published articles',
            'show_func' => 'publisher_recent_show',
            'edit_func' => 'publisher_recent_edit',
            'options' => '5|0|0',
            'template' => 'publisher_block_recent.tpl'
        ],
        [
            'file' => 'blocks/featured.php',
            'name' => 'Featured Articles',
            'description' => 'Display featured articles',
            'show_func' => 'publisher_featured_show',
            'edit_func' => 'publisher_featured_edit'
        ]
    ],

    // Module hooks
    'hooks' => [
        'system.page.footer' => [
            'function' => 'publisher_page_footer'
        ],
        'user.profile.view' => [
            'function' => 'publisher_user_articles'
        ]
    ],

    // Configuration items
    'config' => [
        [
            'name' => 'items_per_page',
            'title' => '_MI_PUBLISHER_ITEMS_PER_PAGE',
            'description' => '_MI_PUBLISHER_ITEMS_PER_PAGE_DESC',
            'formtype' => 'text',
            'valuetype' => 'int',
            'default' => '10'
        ],
        [
            'name' => 'enable_comments',
            'title' => '_MI_PUBLISHER_ENABLE_COMMENTS',
            'description' => '_MI_PUBLISHER_ENABLE_COMMENTS_DESC',
            'formtype' => 'yesno',
            'valuetype' => 'int',
            'default' => '1'
        ]
    ]
];

function xoops_module_install_publisher($module)
{
    // Installation logic
    return true;
}

function xoops_module_update_publisher($module)
{
    // Update logic
    return true;
}

function xoops_module_uninstall_publisher($module)
{
    // Uninstallation logic
    return true;
}
```

## Best Practices

1. **Namespace Your Classes** - Use module-specific namespaces to avoid conflicts

2. **Use Handlers** - Always use handler classes for database operations

3. **Internationalize Content** - Use language constants for all user-facing strings

4. **Create Installation Scripts** - Provide SQL schemas for database tables

5. **Document Hooks** - Clearly document what hooks your module provides

6. **Version Your Module** - Increment version numbers with releases

7. **Test Installation** - Thoroughly test install/uninstall processes

8. **Handle Permissions** - Check user permissions before allowing actions

## Complete Module Example

```php
<?php
/**
 * Custom Article Module Main Page
 */

include __DIR__ . '/include/common.inc.php';

// Get module instance
$module = xoops_getModuleByDirname('mymodule');

// Check if module is active
if (!$module) {
    die('Module not found');
}

// Get module configuration
$itemsPerPage = $module->getConfig('items_per_page');

// Get item handler
$itemHandler = xoops_getModuleHandler('item', 'mymodule');

// Fetch items with pagination
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('status', 1));
$items = $itemHandler->getObjects($criteria, $itemsPerPage);

// Prepare template
$xoopsTpl->assign('items', $items);
$xoopsTpl->assign('module_name', $module->getName());
$xoopsTpl->display($module->getPath() . '/templates/user/index.tpl');
```

## Related Documentation

- [[../Kernel/Kernel-Classes]] - Kernel initialization and core services
- [[../Template/Template-System]] - Module templates and theme integration
- [[../Database/QueryBuilder]] - Database query building
- [[../Core/XoopsObject]] - Base object class

---

*See also: [XOOPS Module Development Guide](https://github.com/XOOPS/XoopsCore25/wiki/Module-Development)*
