---
title: XoopsModule API Reference
description: Complete API reference for XoopsModule and module system classes
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: api-reference
---

# XoopsModule API Reference

> Complete API documentation for the XOOPS module system.

---

## Module System Architecture

```mermaid
graph TB
    subgraph "Module Loading"
        A[Request] --> B[Router]
        B --> C{Module Exists?}
        C -->|Yes| D[Load xoops_version.php]
        C -->|No| E[404 Error]
        D --> F[Initialize Module]
        F --> G[Check Permissions]
        G --> H[Execute Controller]
    end

    subgraph "Module Components"
        I[XoopsModule] --> J[Config]
        I --> K[Templates]
        I --> L[Blocks]
        I --> M[Handlers]
        I --> N[Preloads]
    end

    H --> I
```

---

## XoopsModule Class

### Class Definition

```php
class XoopsModule extends XoopsObject
{
    // Properties
    public $modinfo;      // Module info array
    public $adminmenu;    // Admin menu items

    // Methods
    public function __construct();
    public function loadInfo(string $dirname, bool $verbose = true): bool;
    public function getInfo(string $name = null): mixed;
    public function setInfo(string $name, mixed $value): bool;
    public function mainLink(): string;
    public function subLink(): string;
    public function loadAdminMenu(): void;
    public function getAdminMenu(): array;
    public function loadConfig(): bool;
    public function getConfig(string $name = null): mixed;
}
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `mid` | int | Module ID |
| `name` | string | Display name |
| `version` | string | Version number |
| `dirname` | string | Directory name |
| `isactive` | int | Active status (0/1) |
| `hasmain` | int | Has main area |
| `hasadmin` | int | Has admin area |
| `hassearch` | int | Has search function |
| `hasconfig` | int | Has configuration |
| `hascomments` | int | Has comments |
| `hasnotification` | int | Has notifications |

### Key Methods

```php
// Get module instance
$module = $GLOBALS['xoopsModule'];

// Or load by dirname
$moduleHandler = xoops_getHandler('module');
$module = $moduleHandler->getByDirname('mymodule');

// Get module info
$version = $module->getVar('version');
$name = $module->getVar('name');
$dirname = $module->getVar('dirname');

// Get module config
$config = $module->getConfig();
$specificConfig = $module->getConfig('items_per_page');

// Check if module has feature
$hasAdmin = $module->getVar('hasadmin');
$hasSearch = $module->getVar('hassearch');

// Get module path
$modulePath = XOOPS_ROOT_PATH . '/modules/' . $module->getVar('dirname');
$moduleUrl = XOOPS_URL . '/modules/' . $module->getVar('dirname');
```

---

## XoopsModuleHandler

### Class Definition

```php
class XoopsModuleHandler extends XoopsPersistableObjectHandler
{
    public function create(bool $isNew = true): XoopsModule;
    public function get(int $id): ?XoopsModule;
    public function getByDirname(string $dirname): ?XoopsModule;
    public function insert(XoopsObject $module, bool $force = false): bool;
    public function delete(XoopsObject $module, bool $force = false): bool;
    public function getList(?CriteriaElement $criteria = null): array;
    public function getObjects(?CriteriaElement $criteria = null): array;
}
```

### Usage Examples

```php
// Get handler
$moduleHandler = xoops_getHandler('module');

// Get all active modules
$criteria = new Criteria('isactive', 1);
$activeModules = $moduleHandler->getObjects($criteria);

// Get module by dirname
$publisherModule = $moduleHandler->getByDirname('publisher');

// Get modules with admin
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('isactive', 1));
$criteria->add(new Criteria('hasadmin', 1));
$adminModules = $moduleHandler->getObjects($criteria);

// Check if module is installed
$module = $moduleHandler->getByDirname('mymodule');
if ($module && $module->getVar('isactive')) {
    // Module is installed and active
}
```

---

## Module Lifecycle

```mermaid
stateDiagram-v2
    [*] --> Uninstalled

    Uninstalled --> Installing: Install Module
    Installing --> Installed: Success
    Installing --> Uninstalled: Failure

    Installed --> Active: Activate
    Installed --> Uninstalling: Uninstall

    Active --> Inactive: Deactivate
    Active --> Updating: Update Available

    Inactive --> Active: Activate
    Inactive --> Uninstalling: Uninstall

    Updating --> Active: Update Success
    Updating --> Active: Update Failure

    Uninstalling --> Uninstalled: Success
    Uninstalling --> Installed: Failure

    Uninstalled --> [*]
```

---

## xoops_version.php Structure

```php
<?php
// Module metadata
$modversion['name']        = _MI_MYMODULE_NAME;
$modversion['version']     = '1.0.0';
$modversion['description'] = _MI_MYMODULE_DESC;
$modversion['author']      = 'Your Name';
$modversion['credits']     = 'XOOPS Community';
$modversion['license']     = 'GPL 2.0+';
$modversion['license_url'] = 'https://www.gnu.org/licenses/gpl-2.0.html';
$modversion['image']       = 'assets/images/logo.png';
$modversion['dirname']     = basename(__DIR__);

// Requirements
$modversion['min_php']     = '8.2';
$modversion['min_xoops']   = '2.5.10';
$modversion['min_admin']   = '1.2';
$modversion['min_db']      = ['mysql' => '5.7', 'mysqli' => '5.7'];

// Features
$modversion['hasMain']     = 1;
$modversion['hasAdmin']    = 1;
$modversion['hasSearch']   = 1;
$modversion['hasComments'] = 1;
$modversion['hasNotification'] = 1;

// Admin Menu
$modversion['adminindex']  = 'admin/index.php';
$modversion['adminmenu']   = 'admin/menu.php';

// Database tables
$modversion['sqlfile']['mysql'] = 'sql/mysql.sql';
$modversion['tables'] = [
    $modversion['dirname'] . '_items',
    $modversion['dirname'] . '_categories',
];

// Templates
$modversion['templates'] = [
    ['file' => 'mymodule_index.tpl', 'description' => 'Index template'],
    ['file' => 'mymodule_item.tpl', 'description' => 'Item template'],
];

// Blocks
$modversion['blocks'][] = [
    'file'        => 'blocks/recent.php',
    'name'        => _MI_MYMODULE_BLOCK_RECENT,
    'description' => _MI_MYMODULE_BLOCK_RECENT_DESC,
    'show_func'   => 'mymodule_block_recent_show',
    'edit_func'   => 'mymodule_block_recent_edit',
    'options'     => '10|0',
    'template'    => 'mymodule_block_recent.tpl',
];

// Config options
$modversion['config'][] = [
    'name'        => 'items_per_page',
    'title'       => '_MI_MYMODULE_ITEMS_PER_PAGE',
    'description' => '_MI_MYMODULE_ITEMS_PER_PAGE_DESC',
    'formtype'    => 'textbox',
    'valuetype'   => 'int',
    'default'     => 10,
];

// Search
$modversion['search'] = [
    'file' => 'include/search.inc.php',
    'func' => 'mymodule_search',
];

// Comments
$modversion['comments'] = [
    'itemName' => 'item_id',
    'pageName' => 'item.php',
    'callbackFile' => 'include/comment_functions.php',
    'callback' => [
        'approve' => 'mymodule_comment_approve',
        'update'  => 'mymodule_comment_update',
    ],
];

// Notifications
$modversion['notification'] = [
    'lookup_file' => 'include/notification.inc.php',
    'lookup_func' => 'mymodule_notify_iteminfo',
    'category' => [
        [
            'name'           => 'global',
            'title'          => _MI_MYMODULE_NOTIFY_GLOBAL,
            'description'    => _MI_MYMODULE_NOTIFY_GLOBAL_DESC,
            'subscribe_from' => ['index.php'],
        ],
        [
            'name'           => 'item',
            'title'          => _MI_MYMODULE_NOTIFY_ITEM,
            'description'    => _MI_MYMODULE_NOTIFY_ITEM_DESC,
            'subscribe_from' => ['item.php'],
            'item_name'      => 'item_id',
            'allow_bookmark' => 1,
        ],
    ],
    'event' => [
        [
            'name'          => 'new_item',
            'category'      => 'global',
            'title'         => _MI_MYMODULE_NOTIFY_NEW_ITEM,
            'caption'       => _MI_MYMODULE_NOTIFY_NEW_ITEM_CAP,
            'description'   => _MI_MYMODULE_NOTIFY_NEW_ITEM_DESC,
            'mail_template' => 'notify_newitem',
            'mail_subject'  => _MI_MYMODULE_NOTIFY_NEW_ITEM_SBJ,
        ],
    ],
];
```

---

## Module Helper Pattern

```php
<?php
namespace XoopsModules\MyModule;

class Helper extends \Xmf\Module\Helper
{
    public function __construct()
    {
        $this->dirname = basename(dirname(__DIR__));
    }

    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function getHandler(string $name): ?object
    {
        return $this->getHandlerByName($name);
    }

    public function getConfig(string $name = null)
    {
        return parent::getConfig($name);
    }
}

// Usage
$helper = Helper::getInstance();
$itemHandler = $helper->getHandler('Item');
$perPage = $helper->getConfig('items_per_page');
```

---

## Module Installation Flow

```mermaid
sequenceDiagram
    participant Admin
    participant System
    participant Database
    participant FileSystem

    Admin->>System: Install Module
    System->>FileSystem: Read xoops_version.php
    FileSystem-->>System: Module Config

    System->>Database: Create tables (mysql.sql)
    Database-->>System: Tables created

    System->>Database: Insert module record
    System->>Database: Insert config options
    System->>Database: Insert templates
    System->>Database: Insert blocks

    System->>FileSystem: Compile templates
    FileSystem-->>System: Templates compiled

    System->>Database: Set module active
    System-->>Admin: Installation complete
```

---

## Related Documentation

- [[../Core/XoopsObject|XoopsObject API]]
- [[../../03-Module-Development/Module-Development|Module Development Guide]]
- [[../../02-Core-Concepts/Architecture/XOOPS-Architecture|XOOPS Architecture]]

---

#xoops #api #module #xoopsmodule #reference
