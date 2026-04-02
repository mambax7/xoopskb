---
title: Basic Configuration
description: Initial XOOPS setup including mainfile.php settings, site name, email, and timezone configuration
created: 2025-01-28
updated: 2025-01-28
version: 2.5.8
category: Configuration
---

# Basic XOOPS Configuration

This guide covers essential configuration settings to get your XOOPS site running properly after installation.

## mainfile.php Configuration

The `mainfile.php` file contains critical configuration for your XOOPS installation. It's created during installation but you may need to edit it manually.

### Location

```
/var/www/html/xoops/mainfile.php
```

### File Structure

```php
<?php
// Database Configuration
define('XOOPS_DB_TYPE', 'mysqli');  // Database type
define('XOOPS_DB_HOST', 'localhost');  // Database host
define('XOOPS_DB_USER', 'xoops_user');  // Database user
define('XOOPS_DB_PASS', 'password');  // Database password
define('XOOPS_DB_NAME', 'xoops_db');  // Database name
define('XOOPS_DB_PREFIX', 'xoops_');  // Table prefix

// Site Configuration
define('XOOPS_ROOT_PATH', '/var/www/html/xoops');  // File system path
define('XOOPS_URL', 'http://your-domain.com/xoops');  // Web URL
define('XOOPS_TRUST_PATH', '/var/www/html/xoops/var');  // Trusted path

// Character Set
define('XOOPS_DB_CHARSET', 'utf8mb4');  // Database charset
define('_CHARSET', 'UTF-8');  // Page charset

// Debug Mode (set to 0 in production)
define('XOOPS_DEBUG', 0);  // Set to 1 for debugging
?>
```

### Critical Settings Explained

| Setting | Purpose | Example |
|---|---|---|
| `XOOPS_DB_TYPE` | Database system | `mysqli`, `mysql`, `pdo` |
| `XOOPS_DB_HOST` | Database server location | `localhost`, `192.168.1.1` |
| `XOOPS_DB_USER` | Database username | `xoops_user` |
| `XOOPS_DB_PASS` | Database password | [secure_password] |
| `XOOPS_DB_NAME` | Database name | `xoops_db` |
| `XOOPS_DB_PREFIX` | Table name prefix | `xoops_` (allows multiple XOOPS on one DB) |
| `XOOPS_ROOT_PATH` | Physical file system path | `/var/www/html/xoops` |
| `XOOPS_URL` | Web accessible URL | `http://your-domain.com` |
| `XOOPS_TRUST_PATH` | Trusted path (outside web root) | `/var/www/xoops_var` |

### Editing mainfile.php

Open mainfile.php in a text editor:

```bash
# Using nano
nano /var/www/html/xoops/mainfile.php

# Using vi
vi /var/www/html/xoops/mainfile.php

# Using sed (find and replace)
sed -i "s|define('XOOPS_URL'.*|define('XOOPS_URL', 'http://new-domain.com');|" /var/www/html/xoops/mainfile.php
```

### Common mainfile.php Changes

**Change site URL:**
```php
define('XOOPS_URL', 'https://yourdomain.com');
```

**Enable debug mode (development only):**
```php
define('XOOPS_DEBUG', 1);
```

**Change table prefix (if needed):**
```php
define('XOOPS_DB_PREFIX', 'myxoops_');
```

**Move trust path outside web root (advanced):**
```php
define('XOOPS_TRUST_PATH', '/var/www/xoops_var');
```

## Admin Panel Configuration

Configure basic settings through the XOOPS admin panel.

### Accessing System Settings

1. Log in to admin panel: `http://your-domain.com/xoops/admin/`
2. Navigate to: **System > Preferences > General Settings**
3. Modify settings (see below)
4. Click "Save" at bottom

### Site Name and Description

Configure how your site appears:

```
Site Name: My XOOPS Site
Site Description: A dynamic content management system
Site Slogan: Built with XOOPS
```

### Contact Information

Set site contact details:

```
Site Admin Email: admin@your-domain.com
Site Admin Name: Site Administrator
Contact Form Email: support@your-domain.com
Support Email: help@your-domain.com
```

### Language and Region

Set default language and region:

```
Default Language: English
Default Timezone: America/New_York  (or your timezone)
Date Format: %Y-%m-%d
Time Format: %H:%M:%S
```

## Email Configuration

Configure email settings for notifications and user communications.

### Email Settings Location

**Admin Panel:** System > Preferences > Email Settings

### SMTP Configuration

For reliable email delivery, use SMTP instead of PHP mail():

```
Use SMTP: Yes
SMTP Host: smtp.gmail.com  (or your SMTP provider)
SMTP Port: 587  (TLS) or 465 (SSL)
SMTP Username: your-email@gmail.com
SMTP Password: [app_password]
SMTP Security: TLS or SSL
```

### Gmail Configuration Example

Set up XOOPS to send email via Gmail:

```
SMTP Host: smtp.gmail.com
SMTP Port: 587
SMTP Security: TLS
SMTP Username: your-email@gmail.com
SMTP Password: [Google App Password - NOT regular password]
From Address: your-email@gmail.com
From Name: Your Site Name
```

**Note:** Gmail requires an App Password, not your Gmail password:
1. Go to https://myaccount.google.com/apppasswords
2. Generate app password for "Mail" and "Windows Computer"
3. Use the generated password in XOOPS

### PHP mail() Configuration (Simpler but Less Reliable)

If SMTP unavailable, use PHP mail():

```
Use SMTP: No
From Address: noreply@your-domain.com
From Name: Your Site Name
```

Ensure your server has sendmail or postfix configured:

```bash
# Check if sendmail is available
which sendmail

# Or check postfix
systemctl status postfix
```

### Email Function Settings

Configure what triggers emails:

```
Send Notifications: Yes
Notify Admin on User Registration: Yes
Send Welcome Email to New Users: Yes
Send Password Reset Link: Yes
Enable User Email: Yes
Enable Private Messages: Yes
Notify on Admin Actions: Yes
```

## Timezone Configuration

Set proper timezone for correct timestamps and scheduling.

### Setting Timezone in Admin Panel

**Path:** System > Preferences > General Settings

```
Default Timezone: [Select your timezone]
```

**Common Timezones:**
- America/New_York (EST/EDT)
- America/Chicago (CST/CDT)
- America/Denver (MST/MDT)
- America/Los_Angeles (PST/PDT)
- Europe/London (GMT/BST)
- Europe/Paris (CET/CEST)
- Asia/Tokyo (JST)
- Asia/Shanghai (CST)
- Australia/Sydney (AEDT/AEST)

### Verify Timezone

Check current server timezone:

```bash
# Show current timezone
timedatectl

# Or check date
date +%Z

# List available timezones
timedatectl list-timezones
```

### Set System Timezone (Linux)

```bash
# Set timezone
timedatectl set-timezone America/New_York

# Or use symlink method
ln -sf /usr/share/zoneinfo/America/New_York /etc/localtime

# Verify
date
```

## URL Configuration

### Enable Clean URLs (Friendly URLs)

For URLs like `/page/about` instead of `/index.php?page=about`

**Requirements:**
- Apache with mod_rewrite enabled
- `.htaccess` file in XOOPS root

**Enable in Admin Panel:**

1. Go to: **System > Preferences > URL Settings**
2. Check: "Enable Friendly URLs"
3. Select: "URL Type" (Path Info or Query)
4. Save

**Verify .htaccess Exists:**

```bash
cat /var/www/html/xoops/.htaccess
```

Sample .htaccess content:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /xoops/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?$1 [L,QSA]
</IfModule>
```

**Troubleshooting Clean URLs:**

```bash
# Verify mod_rewrite enabled
apache2ctl -M | grep rewrite

# Enable if needed
a2enmod rewrite

# Restart Apache
systemctl restart apache2

# Test rewrite rule
curl -I http://your-domain.com/xoops/index.php
```

### Configure Site URL

**Admin Panel:** System > Preferences > General Settings

Set correct URL for your domain:

```
Site URL: http://your-domain.com/xoops/
```

Or if XOOPS is in root:

```
Site URL: http://your-domain.com/
```

## Search Engine Optimization (SEO)

Configure SEO settings for better search engine visibility.

### Meta Tags

Set global meta tags:

**Admin Panel:** System > Preferences > SEO Settings

```
Meta Keywords: xoops, cms, content management
Meta Description: A dynamic content management system
```

These appear in page `<head>`:

```html
<meta name="keywords" content="xoops, cms, content management">
<meta name="description" content="A dynamic content management system">
```

### Sitemap

Enable XML sitemap for search engines:

1. Go to: **System > Modules**
2. Find "Sitemap" module
3. Click to install and enable
4. Access sitemap at: `/xoops/sitemap.xml`

### Robots.txt

Control search engine crawling:

Create `/var/www/html/xoops/robots.txt`:

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /templates_c/
Disallow: /install/
Disallow: /upgrade/

Sitemap: https://your-domain.com/xoops/sitemap.xml
```

## User Settings

Configure default user-related settings.

### User Registration

**Admin Panel:** System > Preferences > User Settings

```
Allow User Registration: Yes/No
User Registration Type:
  - Instant (Automatic approval)
  - Approval Required (Admin approval needed)
  - Email Verification (Email confirmation required)

Email Confirmation Required: Yes/No
Account Activation Method: Automatic/Manual
```

### User Profile

```
Enable User Profiles: Yes
Show User Avatar: Yes
Maximum Avatar Size: 100KB
Avatar Dimensions: 100x100 pixels
```

### User Email Display

```
Show User Email: No (for privacy)
Users Can Hide Email: Yes
Users Can Change Avatar: Yes
Users Can Upload Files: Yes
```

## Cache Configuration

Improve performance with proper caching.

### Cache Settings

**Admin Panel:** System > Preferences > Cache Settings

```
Enable Caching: Yes
Cache Method: File (or APCu/Memcache if available)
Cache Lifetime: 3600 seconds (1 hour)
```

### Clear Cache

Clear old cache files:

```bash
# Manual cache clear
rm -rf /var/www/html/xoops/cache/*
rm -rf /var/www/html/xoops/templates_c/*

# From admin panel:
# System > Dashboard > Tools > Clear Cache
```

## Initial Settings Checklist

After installation, configure:

- [ ] Site name and description set correctly
- [ ] Admin email configured
- [ ] SMTP email settings configured and tested
- [ ] Timezone set to your region
- [ ] URL configured correctly
- [ ] Clean URLs (friendly URLs) enabled if desired
- [ ] User registration settings configured
- [ ] Meta tags for SEO configured
- [ ] Default language selected
- [ ] Cache settings enabled
- [ ] Admin user password is strong (16+ characters)
- [ ] Test user registration
- [ ] Test email functionality
- [ ] Test file upload
- [ ] Visit homepage and verify appearance

## Testing Configuration

### Test Email

Send a test email:

**Admin Panel:** System > Email Test

Or manually:

```php
<?php
// Create test file: /var/www/html/xoops/test-email.php
require_once __DIR__ . '/mainfile.php';
require_once XOOPS_ROOT_PATH . '/class/mail/phpmailer/class.phpmailer.php';

$mailer = xoops_getMailer();
$mailer->addRecipient('admin@your-domain.com');
$mailer->setSubject('XOOPS Email Test');
$mailer->setBody('This is a test email from XOOPS');

if ($mailer->send()) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email: " . $mailer->getError();
}
?>
```

### Test Database Connection

```php
<?php
// Create test file: /var/www/html/xoops/test-db.php
require_once __DIR__ . '/mainfile.php';

$connection = XoopsDatabaseFactory::getDatabaseConnection();
if ($connection) {
    echo "Database connected successfully!";
    $result = $connection->query("SELECT COUNT(*) FROM " . $connection->prefix("users"));
    if ($result) {
        echo "Query successful!";
    }
} else {
    echo "Database connection failed!";
}
?>
```

**Important:** Delete test files after testing!

```bash
rm /var/www/html/xoops/test-*.php
```

## Configuration Files Summary

| File | Purpose | Edit Method |
|---|---|---|
| mainfile.php | Database and core settings | Text editor |
| Admin Panel | Most settings | Web interface |
| .htaccess | URL rewriting | Text editor |
| robots.txt | Search engine crawling | Text editor |

## Next Steps

After basic configuration:

1. [[System-Settings|Configure system settings]] in detail
2. [[Security-Configuration|Harden security]]
3. [[../First-Steps/Admin-Panel-Overview|Explore admin panel]]
4. [[../First-Steps/Creating-Your-First-Page|Create your first content]]
5. [[../First-Steps/Managing-Users|Set up user accounts]]

---

**Tags:** #configuration #setup #email #timezone #seo

**Related Articles:**
- [[../Installation/Installation]]
- [[System-Settings]]
- [[Security-Configuration]]
- [[Performance-Optimization]]
