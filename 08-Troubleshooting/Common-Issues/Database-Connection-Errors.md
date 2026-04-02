---
title: Database Connection Errors
description: Troubleshooting guide for XOOPS database connection problems
tags:
  - troubleshooting
  - database
  - connection
  - mysql
  - errors
  - configuration
created: 2026-01-31
updated: 2026-01-31
version: 2026.01
---

# Database Connection Errors

Database connection errors are among the most common issues in XOOPS installations. This guide provides systematic troubleshooting steps to identify and resolve connection problems.

## Common Error Messages

### "Can't connect to MySQL server"

```
Error: Can't connect to MySQL server on 'localhost' (111)
```

This error typically indicates the MySQL server is not running or not accessible.

### "Access denied for user"

```
Error: Access denied for user 'xoops_user'@'localhost' (using password: YES)
```

This indicates incorrect database credentials in your configuration.

### "Unknown database"

```
Error: Unknown database 'xoops_db'
```

The specified database doesn't exist on the MySQL server.

## Configuration Files

### XOOPS Configuration Location

The main configuration file is located at:

```
/mainfile.php
```

Key database settings:

```php
// Database Configuration
define('XOOPS_DB_TYPE', 'mysqli');
define('XOOPS_DB_HOST', 'localhost');
define('XOOPS_DB_PORT', '3306');
define('XOOPS_DB_USER', 'xoops_user');
define('XOOPS_DB_PASS', 'your_password');
define('XOOPS_DB_NAME', 'xoops_db');
define('XOOPS_DB_PREFIX', 'xoops_');
```

## Troubleshooting Steps

### Step 1: Verify MySQL Service is Running

#### On Linux/Unix

```bash
# Check if MySQL is running
sudo systemctl status mysql

# Start MySQL if not running
sudo systemctl start mysql

# Restart MySQL
sudo systemctl restart mysql
```

### Step 2: Test MySQL Connectivity

#### Using Command Line

```bash
# Test connection with credentials
mysql -h localhost -u xoops_user -p xoops_db

# If prompted for password, enter it
# Success shows: mysql>

# Exit MySQL
mysql> EXIT;
```

### Step 3: Verify Database Credentials

#### Check XOOPS Configuration

```php
// In mainfile.php, verify these constants:
echo "Host: " . XOOPS_DB_HOST . "\n";
echo "User: " . XOOPS_DB_USER . "\n";
echo "Port: " . XOOPS_DB_PORT . "\n";
echo "Database: " . XOOPS_DB_NAME . "\n";
```

### Step 4: Verify Database Exists

```bash
# Connect to MySQL
mysql -u root -p

# List all databases
SHOW DATABASES;

# Check for your database
SHOW DATABASES LIKE 'xoops_db';

# If not found, create it
CREATE DATABASE xoops_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit
EXIT;
```

### Step 5: Check User Permissions

```bash
# Connect as root
mysql -u root -p

# Check user privileges
SHOW GRANTS FOR 'xoops_user'@'localhost';

# Grant all privileges if needed
GRANT ALL PRIVILEGES ON xoops_db.* TO 'xoops_user'@'localhost';

# Reload privileges
FLUSH PRIVILEGES;
```

## Common Issues and Solutions

### Issue 1: MySQL Not Running

**Symptoms:**
- Connection refused error
- Can't connect to localhost

**Solutions:**

```bash
# Linux: Check and start MySQL
sudo systemctl status mysql
sudo systemctl start mysql
```

### Issue 2: Incorrect Credentials

**Symptoms:**
- "Access denied" error
- "using password: YES" or "using password: NO"

**Solutions:**

```bash
# Reset password (as root)
mysql -u root -p

# Change user password
ALTER USER 'xoops_user'@'localhost' IDENTIFIED BY 'new_password';

# Update mainfile.php
define('XOOPS_DB_PASS', 'new_password');
```

### Issue 3: Database Not Created

**Symptoms:**
- "Unknown database" error
- Installation failed at database creation

**Solutions:**

```bash
# Check if database exists
mysql -u root -p -e "SHOW DATABASES;"

# Create database if missing
mysql -u root -p -e "CREATE DATABASE xoops_db CHARACTER SET utf8mb4;"
```

## Diagnostic Script

Create a comprehensive diagnostic script:

```php
<?php
// diagnose-db.php

echo "=== XOOPS Database Diagnostic ===\n\n";

// Check constants defined
echo "1. Configuration Check:\n";
echo "   Host: " . (defined('XOOPS_DB_HOST') ? XOOPS_DB_HOST : "NOT DEFINED") . "\n";
echo "   User: " . (defined('XOOPS_DB_USER') ? XOOPS_DB_USER : "NOT DEFINED") . "\n";
echo "   Database: " . (defined('XOOPS_DB_NAME') ? XOOPS_DB_NAME : "NOT DEFINED") . "\n\n";

// Check PHP MySQL extension
echo "2. Extension Check:\n";
echo "   MySQLi: " . (extension_loaded('mysqli') ? "YES" : "NO") . "\n\n";

// Test connection
echo "3. Connection Test:\n";
try {
    $conn = new mysqli(
        XOOPS_DB_HOST,
        XOOPS_DB_USER,
        XOOPS_DB_PASS,
        XOOPS_DB_NAME,
        XOOPS_DB_PORT
    );

    if ($conn->connect_error) {
        echo "   FAILED: " . $conn->connect_error . "\n";
    } else {
        echo "   SUCCESS: Connected to MySQL\n";
        echo "   Server Info: " . $conn->get_server_info() . "\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "   EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n=== End Diagnostic ===\n";
?>
```

## Related Documentation

- [[White-Screen-of-Death]] - Common WSOD troubleshooting
- [[../../01-Getting-Started/Configuration/Performance-Optimization]] - Database performance tuning
- [[../../06-Publisher-Module/User-Guide/Basic-Configuration]] - Initial XOOPS setup
- [[../../04-API-Reference/Database/XoopsDatabase]] - Database API reference

---

**Last Updated:** 2026-01-31
**Applies To:** XOOPS 2.5.7+
**PHP Versions:** 7.4+
