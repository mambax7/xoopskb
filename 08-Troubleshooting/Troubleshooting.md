---
title: Troubleshooting
description: Solutions for common XOOPS issues, debugging techniques, and FAQ
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: troubleshooting
---

# 🔍 Troubleshooting

> Solutions to common problems and debugging techniques for XOOPS CMS.

---

## 📋 Quick Diagnosis

Before diving into specific issues, check these common causes:

1. **File Permissions** - Directories need 755, files need 644
2. **PHP Version** - Ensure PHP 8.2+
3. **Error Logs** - Check `xoops_data/logs/` and PHP error logs
4. **Cache** - Clear cache in Admin → System → Maintenance

---

## 🗂️ Section Contents

### Common Issues
- [[../08-Troubleshooting/Common-Issues/White-Screen-of-Death|White Screen of Death (WSOD)]]
- [[Common-Issues/Database-Connection-Errors|Database Connection Errors]]
- [[Common-Issues/Permission-Denied|Permission Denied Errors]]
- [[Common-Issues/Module-Installation-Failures|Module Installation Failures]]
- [[Common-Issues/Template-Errors|Template Compilation Errors]]

### FAQ
- [[../08-Troubleshooting/FAQ/Installation-FAQ|Installation FAQ]]
- [[FAQ/Module-FAQ|Module FAQ]]
- [[FAQ/Theme-FAQ|Theme FAQ]]
- [[FAQ/Performance-FAQ|Performance FAQ]]

### Debugging
- [[../08-Troubleshooting/Debugging/Enable-Debug-Mode|Enabling Debug Mode]]
- [[Debugging/Using-Ray-Debugger|Using Ray Debugger]]
- [[Debugging/Database-Debugging|Database Query Debugging]]
- [[Debugging/Smarty-Debugging|Smarty Template Debugging]]

---

## 🚨 Common Issues & Solutions

### White Screen of Death (WSOD)

**Symptoms:** Blank white page, no error message

**Solutions:**

1. **Enable PHP error display temporarily:**
   ```php
   // Add to mainfile.php temporarily
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. **Check PHP error log:**
   ```bash
   tail -f /var/log/php/error.log
   ```

3. **Common causes:**
   - Memory limit exceeded
   - Fatal PHP syntax error
   - Missing required extension

4. **Fix memory issues:**
   ```php
   // In mainfile.php or php.ini
   ini_set('memory_limit', '256M');
   ```

---

### Database Connection Errors

**Symptoms:** "Unable to connect to database" or similar

**Solutions:**

1. **Verify credentials in mainfile.php:**
   ```php
   define('XOOPS_DB_HOST', 'localhost');
   define('XOOPS_DB_USER', 'your_username');
   define('XOOPS_DB_PASS', 'your_password');
   define('XOOPS_DB_NAME', 'your_database');
   ```

2. **Test connection manually:**
   ```php
   <?php
   $conn = new mysqli('localhost', 'user', 'pass', 'database');
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }
   echo "Connected successfully";
   ```

3. **Check MySQL service:**
   ```bash
   sudo systemctl status mysql
   sudo systemctl restart mysql
   ```

4. **Verify user permissions:**
   ```sql
   GRANT ALL PRIVILEGES ON xoops.* TO 'user'@'localhost';
   FLUSH PRIVILEGES;
   ```

---

### Permission Denied Errors

**Symptoms:** Cannot upload files, cannot save settings

**Solutions:**

1. **Set correct permissions:**
   ```bash
   # Directories
   find /path/to/xoops -type d -exec chmod 755 {} \;

   # Files
   find /path/to/xoops -type f -exec chmod 644 {} \;

   # Writable directories
   chmod -R 777 xoops_data/
   chmod -R 777 uploads/
   ```

2. **Set correct ownership:**
   ```bash
   chown -R www-data:www-data /path/to/xoops
   ```

3. **Check SELinux (CentOS/RHEL):**
   ```bash
   # Check status
   sestatus

   # Allow httpd to write
   setsebool -P httpd_unified 1
   ```

---

### Module Installation Failures

**Symptoms:** Module won't install, SQL errors

**Solutions:**

1. **Check module requirements:**
   - PHP version compatibility
   - Required PHP extensions
   - XOOPS version compatibility

2. **Manual SQL installation:**
   ```bash
   mysql -u user -p database < modules/mymodule/sql/mysql.sql
   ```

3. **Clear module cache:**
   ```php
   // In xoops_data/caches/
   rm -rf xoops_cache/*
   rm -rf smarty_cache/*
   rm -rf smarty_compile/*
   ```

4. **Check xoops_version.php syntax:**
   ```bash
   php -l modules/mymodule/xoops_version.php
   ```

---

### Template Compilation Errors

**Symptoms:** Smarty errors, template not found

**Solutions:**

1. **Clear Smarty cache:**
   ```bash
   rm -rf xoops_data/caches/smarty_cache/*
   rm -rf xoops_data/caches/smarty_compile/*
   ```

2. **Check template syntax:**
   ```smarty
   {* Correct *}
   {$variable}

   {* Incorrect - missing $ *}
   {variable}
   ```

3. **Verify template exists:**
   ```bash
   ls modules/mymodule/templates/
   ```

4. **Regenerate templates:**
   - Admin → System → Maintenance → Templates → Regenerate

---

## 🐛 Debugging Techniques

### Enable XOOPS Debug Mode

```php
// In mainfile.php
define('XOOPS_DEBUG_LEVEL', 2);

// Levels:
// 0 = Off
// 1 = PHP debug
// 2 = PHP + SQL debug
// 3 = PHP + SQL + Smarty templates
```

### Using Ray Debugger

Ray is an excellent debugging tool for PHP:

```php
// Install via Composer
composer require spatie/ray --dev

// Usage in your code
ray($variable);
ray($object)->expand();
ray()->measure();

// Database queries
ray($sql)->label('Query');
```

### Smarty Debug Console

```smarty
{* Enable in template *}
{debug}

{* Or in PHP *}
$xoopsTpl->debugging = true;
```

### Database Query Logging

```php
// Enable query logging
$GLOBALS['xoopsDB']->setLogger(new XoopsLogger());

// Get all queries
$queries = $GLOBALS['xoopsLogger']->queries;
foreach ($queries as $query) {
    echo $query['sql'] . " - " . $query['time'] . "s\n";
}
```

---

## ❓ Frequently Asked Questions

### Installation

**Q: Installation wizard shows blank page**
A: Check PHP error logs, ensure PHP has enough memory, verify file permissions.

**Q: Cannot write to mainfile.php during installation**
A: Set permissions: `chmod 666 mainfile.php` during installation, then `chmod 444` after.

**Q: Database tables not created**
A: Check MySQL user has CREATE TABLE privileges, verify database exists.

### Modules

**Q: Module admin page is blank**
A: Clear cache, check module's admin/menu.php for syntax errors.

**Q: Module blocks not showing**
A: Check block permissions in Admin → Blocks, verify block is assigned to pages.

**Q: Module update fails**
A: Backup database, try manual SQL updates, check version requirements.

### Themes

**Q: Theme not applying correctly**
A: Clear Smarty cache, check theme.html exists, verify theme permissions.

**Q: Custom CSS not loading**
A: Check file path, clear browser cache, verify CSS syntax.

**Q: Images not displaying**
A: Check image paths, verify uploads folder permissions.

### Performance

**Q: Site is very slow**
A: Enable caching, optimize database, check for slow queries, enable OpCache.

**Q: High memory usage**
A: Increase memory_limit, optimize large queries, implement pagination.

---

## 🔧 Maintenance Commands

### Clear All Caches

```bash
#!/bin/bash
# clear_cache.sh
rm -rf xoops_data/caches/xoops_cache/*
rm -rf xoops_data/caches/smarty_cache/*
rm -rf xoops_data/caches/smarty_compile/*
echo "Cache cleared!"
```

### Database Optimization

```sql
-- Optimize all tables
OPTIMIZE TABLE xoops_config;
OPTIMIZE TABLE xoops_users;
OPTIMIZE TABLE xoops_session;
-- Repeat for other tables

-- Or optimize all at once
mysqlcheck -o -u user -p database
```

### Check File Integrity

```bash
# Compare against fresh install
diff -r /path/to/xoops /path/to/fresh-xoops
```

---

## 🔗 Related Documentation

- [[../01-Getting-Started/Getting-Started|Getting Started]]
- [[../02-Core-Concepts/Security/Security-Best-Practices|Security Best Practices]]
- [[../07-XOOPS-4.0/XOOPS-4.0-Roadmap|XOOPS 4.0 Roadmap]]

---

## 📚 External Resources

- [XOOPS Forums](https://xoops.org/modules/newbb/)
- [GitHub Issues](https://github.com/XOOPS/XoopsCore25/issues)
- [PHP Error Reference](https://www.php.net/manual/en/errorfunc.constants.php)

---

#xoops #troubleshooting #debugging #faq #errors #solutions
