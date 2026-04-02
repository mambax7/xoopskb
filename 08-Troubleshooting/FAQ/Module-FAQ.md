---
title: Module FAQ
description: Frequently asked questions about XOOPS modules
created: 2025-01-31
updated: 2025-01-31
version: 1.0.0
category: FAQ
---

# Module Frequently Asked Questions

> Common questions and answers about XOOPS modules, installation, and management.

---

## Installation & Activation

### Q: How do I install a module in XOOPS?

**A:**
1. Download the module zip file
2. Go to XOOPS Admin > Modules > Manage Modules
3. Click "Browse" and select the zip file
4. Click "Upload"
5. The module appears in the list (usually deactivated)
6. Click the activation icon to enable it

Alternatively, extract the zip directly into `/xoops_root/modules/` and navigate to the admin panel.

---

### Q: Module upload fails with "Permission denied"

**A:** This is a file permission issue:

```bash
# Fix module directory permissions
chmod 755 /path/to/xoops/modules

# Fix upload directory (if uploading)
chmod 777 /path/to/xoops/uploads

# Fix ownership if needed
chown -R www-data:www-data /path/to/xoops
```

See [[../Common-Issues/Module-Installation-Failures|Module Installation Failures]] for more details.

---

### Q: Why can't I see the module in the admin panel after installation?

**A:** Check the following:

1. **Module not activated** - Click the eye icon in Modules list
2. **Missing admin page** - Module must have `hasAdmin = 1` in xoopsversion.php
3. **Language files missing** - Need `language/english/admin.php`
4. **Cache not cleared** - Clear cache and refresh browser

```bash
# Clear XOOPS cache
rm -rf /path/to/xoops/xoops_data/caches/*
```

---

### Q: How do I uninstall a module?

**A:**
1. Go to XOOPS Admin > Modules > Manage Modules
2. Deactivate the module (click the eye icon)
3. Click the trash/delete icon
4. Manually delete the module folder if you want complete removal:

```bash
rm -rf /path/to/xoops/modules/modulename
```

---

## Module Management

### Q: What's the difference between disabling and uninstalling?

**A:**
- **Disable**: Deactivate the module (click eye icon). Database tables remain.
- **Uninstall**: Remove the module. Deletes database tables and removes from list.

To truly remove, also delete the folder:
```bash
rm -rf modules/modulename
```

---

### Q: How do I check if a module is properly installed?

**A:** Use the debug script:

```php
<?php
// Create admin/debug_modules.php
require_once XOOPS_ROOT_PATH . '/mainfile.php';

if (!is_object($xoopsUser) || !$xoopsUser->isAdmin()) {
    exit('Admin only');
}

echo "<h1>Module Debug</h1>";

// List all modules
$module_handler = xoops_getHandler('module');
$modules = $module_handler->getObjects();

foreach ($modules as $module) {
    echo "<h2>" . $module->getVar('name') . "</h2>";
    echo "Status: " . ($module->getVar('isactive') ? "Active" : "Inactive") . "<br>";
    echo "Directory: " . $module->getVar('dirname') . "<br>";
    echo "Mid: " . $module->getVar('mid') . "<br>";
    echo "Version: " . $module->getVar('version') . "<br>";
}
?>
```

---

### Q: Can I run multiple versions of the same module?

**A:** No, XOOPS doesn't support this natively. However, you can:

1. Create a copy with a different directory name: `mymodule` and `mymodule2`
2. Update the dirname in both modules' xoopsversion.php
3. Ensure unique database table names

This is not recommended as they share the same code.

---

## Module Configuration

### Q: Where do I configure module settings?

**A:**
1. Go to XOOPS Admin > Modules
2. Click the settings/gear icon next to the module
3. Configure preferences

Settings are stored in the `xoops_config` table.

**Access in code:**
```php
<?php
$module_handler = xoops_getHandler('module');
$module = $module_handler->getByDirname('modulename');
$config_handler = xoops_getHandler('config');
$settings = $config_handler->getConfigsByCat(0, $module->mid());

foreach ($settings as $setting) {
    echo $setting->getVar('conf_name') . ": " . $setting->getVar('conf_value');
}
?>
```

---

### Q: How do I define module configuration options?

**A:** In xoopsversion.php:

```php
<?php
$modversion['config'] = [
    [
        'name' => 'items_per_page',
        'title' => '_AM_MYMODULE_ITEMS_PER_PAGE',
        'description' => '_AM_MYMODULE_ITEMS_PER_PAGE_DESC',
        'formtype' => 'text',
        'valuetype' => 'int',
        'default' => 10
    ],
    [
        'name' => 'enable_feature',
        'title' => '_AM_MYMODULE_ENABLE_FEATURE',
        'description' => '_AM_MYMODULE_ENABLE_FEATURE_DESC',
        'formtype' => 'yesno',
        'valuetype' => 'bool',
        'default' => 1
    ]
];
?>
```

---

## Module Features

### Q: How do I add an admin page to my module?

**A:** Create the structure:

```
modules/mymodule/
├── admin/
│   ├── index.php
│   ├── menu.php
│   └── menu_en.php
```

In xoopsversion.php:
```php
<?php
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = 'admin/index.php';
?>
```

Create `admin/index.php`:
```php
<?php
require_once XOOPS_ROOT_PATH . '/kernel/admin.php';

xoops_cp_header();
echo "<h1>Module Administration</h1>";
xoops_cp_footer();
?>
```

---

### Q: How do I add search functionality to my module?

**A:**
1. Set in xoopsversion.php:
```php
<?php
$modversion['hasSearch'] = 1;
$modversion['search'] = 'search.php';
?>
```

2. Create `search.php`:
```php
<?php
function mymodule_search($queryArray, $andor, $limit, $offset) {
    // Search implementation
    $results = [];
    return $results;
}
?>
```

---

### Q: How do I add notifications to my module?

**A:**
1. Set in xoopsversion.php:
```php
<?php
$modversion['hasNotification'] = 1;
$modversion['notification_categories'] = [
    ['name' => 'item_published', 'title' => '_NOT_ITEM_PUBLISHED']
];
$modversion['notifications'] = [
    ['name' => 'item_published', 'title' => '_NOT_ITEM_PUBLISHED']
];
?>
```

2. Trigger notification in code:
```php
<?php
$notification_handler = xoops_getHandler('notification');
$notification_handler->triggerEvent(
    'item_published',
    $item_id,
    'Item published',
    'description'
);
?>
```

---

## Module Permissions

### Q: How do I set module permissions?

**A:**
1. Go to XOOPS Admin > Modules > Module Permissions
2. Select the module
3. Choose user/group and permission level
4. Save

**In code:**
```php
<?php
// Check if user can access module
if (!xoops_isUser()) {
    exit('Login required');
}

// Check specific permission
$mperm_handler = xoops_getHandler('member_permission');
$module_handler = xoops_getHandler('module');
$module = $module_handler->getByDirname('mymodule');

if (!$mperm_handler->userCanAccess($module->mid())) {
    exit('Access denied');
}
?>
```

---

## Module Database

### Q: Where are module database tables stored?

**A:** All in the main XOOPS database, prefixed with your table prefix (usually `xoops_`):

```bash
# List all module tables
mysql> SHOW TABLES LIKE 'xoops_mymodule_%';

# Or in PHP
<?php
$result = $GLOBALS['xoopsDB']->query(
    "SHOW TABLES LIKE '" . XOOPS_DB_PREFIX . "mymodule_%'"
);
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
```

---

### Q: How do I update module database tables?

**A:** Create an update script in your module:

```php
<?php
// modules/mymodule/update.php
require_once '../../mainfile.php';

if (!is_object($xoopsUser) || !$xoopsUser->isAdmin()) {
    exit('Admin only');
}

// Add new column
$sql = "ALTER TABLE `" . XOOPS_DB_PREFIX . "mymodule_items`
        ADD COLUMN `new_field` VARCHAR(255)";

if ($GLOBALS['xoopsDB']->query($sql)) {
    echo "✓ Updated successfully";
} else {
    echo "✗ Error: " . $GLOBALS['xoopsDB']->error;
}
?>
```

---

## Module Dependencies

### Q: How do I check if required modules are installed?

**A:**
```php
<?php
$module_handler = xoops_getHandler('module');

// Check if a module exists
$module = $module_handler->getByDirname('required_module');

if (!$module || !$module->getVar('isactive')) {
    die('Error: required_module is not installed or active');
}
?>
```

---

### Q: Can modules depend on other modules?

**A:** Yes, declare in xoopsversion.php:

```php
<?php
$modversion['dependencies'] = [
    [
        'dirname' => 'required_module',
        'version_min' => '1.0',
        'version_max' => 0,  // 0 = unlimited
        'order' => 1
    ]
];
?>
```

---

## Troubleshooting

### Q: Module appears in list but won't activate

**A:** Check:
1. xoopsversion.php syntax - Use PHP linter:
```bash
php -l modules/mymodule/xoopsversion.php
```

2. Database SQL file:
```bash
# Check SQL syntax
grep -n "CREATE TABLE" modules/mymodule/sql/mysql.sql
```

3. Language files:
```bash
ls -la modules/mymodule/language/english/
```

See [[../Common-Issues/Module-Installation-Failures|Module Installation Failures]] for detailed diagnostics.

---

### Q: Module activated but doesn't show in main site

**A:**
1. Set `hasMain = 1` in xoopsversion.php:
```php
<?php
$modversion['hasMain'] = 1;
$modversion['main_file'] = 'index.php';
?>
```

2. Create `modules/mymodule/index.php`:
```php
<?php
require_once '../../mainfile.php';
include_once XOOPS_ROOT_PATH . '/header.php';

echo "Welcome to my module";

include_once XOOPS_ROOT_PATH . '/footer.php';
?>
```

---

### Q: Module causes "white screen of death"

**A:** Enable debugging to find the error:

```php
<?php
// In mainfile.php
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('XOOPS_DEBUG_LEVEL', 2);
?>
```

Check the error log:
```bash
tail -100 /var/log/php/error.log
tail -100 /var/log/apache2/error.log
```

See [[../Common-Issues/White-Screen-of-Death|White Screen of Death]] for solutions.

---

## Performance

### Q: Module is slow, how do I optimize?

**A:**
1. **Check database queries** - Use query logging
2. **Cache data** - Use XOOPS cache:
```php
<?php
$cache = xoops_cache_handler::getInstance();
$data = $cache->read('mykey');
if ($data === false) {
    $data = expensive_operation();
    $cache->write('mykey', $data, 3600);  // 1 hour
}
?>
```

3. **Optimize templates** - Avoid loops in templates
4. **Enable PHP opcode cache** - APCu, XDebug, etc.

See [[../FAQ/Performance-FAQ|Performance FAQ]] for more details.

---

## Module Development

### Q: Where can I find module development documentation?

**A:** See:
- [[../../06-Publisher-Module/User-Guide/Getting-Started|Module Development Guide]]
- [[../../03-Module-Development/Module-Structure|Module Structure]]
- [[../../03-Module-Development/Tutorials/Hello-World-Module|Creating Your First Module]]

---

## Related Documentation

- [[../Common-Issues/Module-Installation-Failures|Module Installation Failures]]
- [[../../03-Module-Development/Module-Structure|Module Structure]]
- [[../FAQ/Performance-FAQ|Performance FAQ]]
- [[../Debugging/Enable-Debug-Mode|Enable Debug Mode]]

---

#xoops #modules #faq #troubleshooting
