---
title: Getting Started with XMF
description: Installation, basic concepts, and first steps with the XOOPS Module Framework
sidebar_position: 1
---

# Getting Started with XMF

<span class="version-badge version-25x">2.5.x ✅</span> <span class="version-badge version-40x">4.0.x ✅</span>

This guide covers the fundamental concepts of the XOOPS Module Framework (XMF) and how to start using it in your modules.

## Prerequisites

- XOOPS 2.5.8 or later installed
- PHP 8.2 or later
- Basic understanding of PHP object-oriented programming

## Understanding Namespaces

XMF uses PHP namespaces to organize its classes and avoid naming conflicts. All XMF classes are in the `Xmf` namespace.

### Global Space Problem

Without namespaces, all PHP classes share a global space. This can cause conflicts:

```php
<?php
// This would conflict with PHP's built-in ArrayObject
class ArrayObject {
    public function doStuff() {
        // ...
    }
}
// Fatal error: Cannot redeclare class ArrayObject
```

### Namespaces Solution

Namespaces create isolated naming contexts:

```php
<?php
namespace MyNamespace;

class ArrayObject {
    public function doStuff() {
        // ...
    }
}
// No conflict - this is \MyNamespace\ArrayObject
```

### Using XMF Namespaces

You can reference XMF classes in several ways:

**Full namespace path:**
```php
$helper = \Xmf\Module\Helper::getHelper('mymodule');
```

**With use statement:**
```php
use Xmf\Module\Helper;

$helper = Helper::getHelper('mymodule');
```

**Multiple imports:**
```php
use Xmf\Request;
use Xmf\Module\Helper;
use Xmf\Module\Helper\Permission;

$input = Request::getString('input', '');
$helper = Helper::getHelper('mymodule');
$perm = new Permission();
```

## Autoloading

One of XMF's greatest conveniences is automatic class loading. You never need to manually include XMF class files.

### Traditional XOOPS Loading

The old way required explicit loading:

```php
XoopsLoad('xoopsrequest');
$cleanInput = XoopsRequest::getString('input', '');
```

### XMF Autoloading

With XMF, classes load automatically when referenced:

```php
$input = Xmf\Request::getString('input', '');
```

Or with a use statement:

```php
use Xmf\Request;

$input = Request::getString('input', '');
$id = Request::getInt('id', 0);
$op = Request::getCmd('op', 'display');
```

The autoloader follows the [PSR-4](http://www.php-fig.org/psr/psr-4/) standard and also manages dependencies that XMF relies on.

## Basic Usage Examples

### Reading Request Input

```php
use Xmf\Request;

// Get integer value with default of 0
$id = Request::getInt('id', 0);

// Get string value with default empty string
$title = Request::getString('title', '');

// Get command (alphanumeric, lowercase)
$op = Request::getCmd('op', 'list');

// Get email with validation
$email = Request::getEmail('email', '');

// Get from specific hash (POST, GET, etc.)
$formData = Request::getString('data', '', 'POST');
```

### Using the Module Helper

```php
use Xmf\Module\Helper;

// Get helper for your module
$helper = Helper::getHelper('mymodule');

// Read module configuration
$itemsPerPage = $helper->getConfig('items_per_page', 10);
$enableFeature = $helper->getConfig('enable_feature', false);

// Access the module object
$module = $helper->getModule();
$version = $module->getVar('version');

// Get a handler
$itemHandler = $helper->getHandler('items');

// Load language file
$helper->loadLanguage('admin');

// Check if current module
if ($helper->isCurrentModule()) {
    // We are in this module
}

// Check admin rights
if ($helper->isUserAdmin()) {
    // User has admin access
}
```

### Path and URL Helpers

```php
use Xmf\Module\Helper;

$helper = Helper::getHelper('mymodule');

// Get module URL
$moduleUrl = $helper->url('images/logo.png');
// Returns: https://example.com/modules/mymodule/images/logo.png

// Get module path
$modulePath = $helper->path('templates/view.tpl');
// Returns: /var/www/html/modules/mymodule/templates/view.tpl

// Upload paths
$uploadUrl = $helper->uploadUrl('files/document.pdf');
$uploadPath = $helper->uploadPath('files/document.pdf');
```

## Debugging with XMF

XMF provides helpful debugging tools:

```php
// Dump a variable with nice formatting
\Xmf\Debug::dump($myVariable);

// Dump multiple variables
\Xmf\Debug::dump($var1, $var2, $var3);

// Dump POST data
\Xmf\Debug::dump($_POST);

// Show a backtrace
\Xmf\Debug::backtrace();
```

The debug output is collapsible and displays objects and arrays in an easy-to-read format.

## Project Structure Recommendation

When building XMF-based modules, organize your code:

```
mymodule/
  admin/
    index.php
    menu.php
  class/
    Helper.php          # Optional custom helper
    ItemHandler.php     # Your handlers
  include/
    common.php
  language/
    english/
      main.php
      admin.php
      modinfo.php
  templates/
    mymodule_index.tpl
  index.php
  xoops_version.php
```

## Common Include Pattern

A typical module entry point:

```php
<?php
// mymodule/index.php

use Xmf\Request;
use Xmf\Module\Helper;

require_once dirname(dirname(__DIR__)) . '/mainfile.php';

$helper = Helper::getHelper(basename(__DIR__));

// Get operation from request
$op = Request::getCmd('op', 'list');
$id = Request::getInt('id', 0);

// Include XOOPS header
require_once XOOPS_ROOT_PATH . '/header.php';

// Your module logic here
switch ($op) {
    case 'view':
        // Handle view
        break;
    case 'list':
    default:
        // Handle list
        break;
}

// Include XOOPS footer
require_once XOOPS_ROOT_PATH . '/footer.php';
```

## Next Steps

Now that you understand the basics, explore:

- [[XMF-Request]] - Detailed request handling documentation
- [[XMF-Module-Helper]] - Complete module helper reference
- [[../Recipes/Permission-Helper]] - Managing user permissions
- [[../Recipes/Module-Admin-Pages]] - Building admin interfaces

## See Also

- [[../XMF-Framework]] - Framework overview
- [[../Reference/JWT]] - JSON Web Token support
- [[../Reference/Database]] - Database utilities

---

#xmf #getting-started #namespaces #autoloading #basics
