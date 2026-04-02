---
title: Server Requirements
description: Detailed server requirements including PHP version, MySQL, web server configuration, and file permissions
created: 2025-01-28
updated: 2026-01-31
version: 2.5.11
category: Installation
---

# XOOPS Server Requirements

This document specifies all server requirements needed to run XOOPS successfully.

## Version-Specific Requirements

| XOOPS Version | Status | PHP Minimum | PHP Recommended | MySQL/MariaDB |
|---------------|--------|-------------|-----------------|---------------|
| **2.5.11** | Stable | 7.4.0 | 8.2 | 5.7+ / 10.3+ |
| **2.5.12** | Beta | 7.4.4 | 8.2 | 5.7+ / 10.4+ |
| **2026** | Target | 8.2 | 8.3 | 8.0+ / 10.6+ |

> **Note:** XOOPS 2.5.12 requires PHP 8.2 as the minimum version.

## PHP Requirements

### Minimum Version

### PHP Extensions (Required)

The following PHP extensions must be installed and enabled:

| Extension | Purpose | Enabled By Default |
|---|---|---|
| **PDO** | Database abstraction | Yes |
| **MySQL/MySQLi** | MySQL connectivity | Yes |
| **GD** | Image manipulation | Usually Yes |
| **JSON** | JSON encoding/decoding | Yes |
| **Session** | Session management | Yes |
| **PCRE** | Regular expressions | Yes |
| **Reflection** | Class/method inspection | Yes |
| **SPL** | Standard PHP library | Yes |

### PHP Extensions (Recommended)

These extensions enhance XOOPS functionality:

| Extension | Purpose | Impact |
|---|---|---|
| **cURL** | Remote HTTP requests | Highly recommended |
| **OpenSSL** | SSL/TLS support | Required for HTTPS |
| **Zip** | ZIP file handling | For module installation |
| **XML** | XML parsing | Some modules use XML |
| **mbstring** | Multi-byte string support | Better Unicode handling |
| **Opcache** | PHP code caching | Significant performance boost |
| **APCu** | User data caching | Performance optimization |

### PHP Configuration Settings

Recommended php.ini settings for XOOPS:

```ini
; Memory and Execution
memory_limit = 128M              ; Minimum 128MB, 256MB+ recommended
max_execution_time = 300         ; 5 minutes for installations/upgrades
max_input_time = 300             ; 5 minutes

; File Upload
upload_max_filesize = 128M       ; Maximum upload file size
post_max_size = 128M             ; Maximum POST size
max_file_uploads = 20            ; Maximum files per request

; Sessions
session.save_path = "/tmp"       ; Ensure directory exists and is writable
session.gc_maxlifetime = 3600    ; Session timeout in seconds

; Security
display_errors = Off             ; Don't display errors in production
log_errors = On                  ; Log errors to file
error_log = "/var/log/php_errors.log"  ; Error log location

; Performance
default_charset = "UTF-8"        ; UTF-8 encoding
short_open_tag = Off             ; Avoid short PHP tags

; Extensions
extension = php_mysql.so         ; Or php_mysqli.so
extension = php_gd2.so           ; Or php_gd.so
extension = php_curl.so
extension = php_openssl.so
```

#### Verify PHP Configuration

Check current PHP settings:

```bash
# View PHP version
php -v

# View installed extensions
php -m

# View specific php.ini setting
php -r "echo ini_get('memory_limit');"

# Create phpinfo() script
echo '<?php phpinfo(); ?>' > /var/www/html/info.php
# Visit http://your-domain.com/info.php in browser
```

## Database Requirements

### MySQL/MariaDB Version

| Database | Minimum Version | Recommended |
|---|---|---|
| **MySQL** | 5.0.0 | 5.7+ |
| **MariaDB** | 5.5.0 | 10.2+ |
| **Percona** | 5.5.0 | 5.7+ |

### Database Configuration

```sql
-- Required settings
SET GLOBAL character_set_server = utf8mb4;
SET GLOBAL collation_server = utf8mb4_unicode_ci;

-- For better performance (optional)
SET GLOBAL max_connections = 200;
SET GLOBAL max_allowed_packet = 256M;
SET GLOBAL query_cache_size = 64M;
SET GLOBAL query_cache_type = 1;
```

### Database User Privileges

Required privileges for XOOPS database user:

```sql
-- Create database user
CREATE USER 'xoops_user'@'localhost' IDENTIFIED BY 'password';

-- Grant required privileges
GRANT CREATE, SELECT, INSERT, UPDATE, DELETE,
      DROP, CREATE TEMPORARY TABLES,
      LOCK TABLES, ALTER
ON xoops_db.* TO 'xoops_user'@'localhost';

FLUSH PRIVILEGES;
```

### Connection Methods

| Method | Recommended | Notes |
|---|---|---|
| **Local (localhost)** | Yes | Fastest, most secure |
| **TCP/IP (127.0.0.1)** | Yes | Alternative to localhost |
| **Remote Host** | Conditional | Only with SSL encryption |
| **Socket (/tmp/mysql.sock)** | Yes | Slightly faster than TCP |

### Database Tables

XOOPS creates approximately **40-50 tables** depending on installed modules.

Storage recommendations:
- **Minimum:** 50MB
- **Recommended:** 500MB
- **Large sites:** 1GB+

Check database size:

```sql
-- View total database size
SELECT
    table_schema,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'xoops_db'
GROUP BY table_schema;
```

## Web Server Requirements

### Apache (Recommended)

| Module | Required | Recommended |
|---|---|---|
| **mod_rewrite** | Yes | For clean URLs |
| **mod_dir** | Yes | Directory handling |
| **mod_mime** | Yes | MIME type mapping |
| **mod_autoindex** | No | Directory listings |
| **mod_ssl** | Yes | HTTPS support |
| **mod_expires** | No | Cache control |
| **mod_deflate** | No | Gzip compression |

#### Apache Configuration

Create virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/html/xoops

    <Directory /var/www/html/xoops>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable mod_rewrite for clean URLs
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php?$1 [L,QSA]
        </IfModule>
    </Directory>

    # Restrict access to sensitive directories
    <Directory /var/www/html/xoops/install>
        Deny from all
    </Directory>

    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/xoops_error.log
    CustomLog ${APACHE_LOG_DIR}/xoops_access.log combined
</VirtualHost>
```

Enable modules:

```bash
# Enable required modules
a2enmod rewrite
a2enmod ssl
a2enmod headers
a2enmod expires
a2enmod deflate

# Enable site
a2ensite xoops

# Restart Apache
systemctl restart apache2
```

### Nginx

| Module | Recommended | Purpose |
|---|---|---|
| **gzip** | Yes | Compression |
| **ssl** | Yes | HTTPS support |
| **rewrite** | Yes | URL rewriting |
| **headers** | Yes | Custom headers |
| **cache** | Optional | Caching |

#### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name your-domain.com www.your-domain.com;
    root /var/www/html/xoops;
    index index.php index.html;

    # Logging
    access_log /var/log/nginx/xoops_access.log;
    error_log /var/log/nginx/xoops_error.log;

    # Gzip compression
    gzip on;
    gzip_types text/html text/plain text/css text/javascript application/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Deny access to sensitive directories
    location ~ ^/(install|docs|samples)/ {
        deny all;
    }

    # PHP-FPM backend
    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "upload_max_filesize=128M post_max_size=128M";
    }

    # Static files - enable browser caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # URL rewriting for clean URLs
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to dotfiles
    location ~ /\. {
        deny all;
    }
}
```

### Other Web Servers

| Server | Supported | Notes |
|---|---|---|
| **Apache** | Yes | Most common, fully supported |
| **Nginx** | Yes | High performance, growing use |
| **LiteSpeed** | Yes | Requires compatible hosting |
| **IIS (Windows)** | Limited | Community support only |

## File System Requirements

### Directory Structure

```
xoops/
├── admin/              (Web accessible)
├── bin/                (PHP CLI scripts)
├── cache/              (Writable - 777)
├── class/              (Writable for module installations)
├── configs/            (Contains mainfile.php)
├── docs/               (Readable only)
├── extra/              (Optional utilities)
├── formats/            (Optional)
├── htdocs/             (Web accessible)
├── include/            (PHP includes)
├── install/            (Remove after installation)
├── kernel/             (Core classes)
├── modules/            (Extensible modules)
├── templates/          (Core templates)
├── templates_c/        (Writable - 777)
├── uploads/            (Writable - 777)
├── var/                (Writable - 777, variable data)
├── index.php           (Entry point)
├── mainfile.php        (Critical config)
└── .htaccess           (Rewrite rules)
```

### Permission Requirements Table

| Path | Owner | Permissions | Reason |
|---|---|---|---|
| `mainfile.php` | www-data | 644 | Configuration (readable, not writable) |
| `cache/` | www-data | 777 | Cache files writable by web server |
| `templates_c/` | www-data | 777 | Compiled templates writable |
| `uploads/` | www-data | 777 | User uploads writable |
| `var/` | www-data | 777 | Variable data writable |
| `modules/` | root | 755 | Modules readable by all |
| `modules/*/` | www-data | 755 | Module dirs (some may need write) |
| `*.php` files | root | 644 | PHP files readable, not writable |
| `*.php` dirs | root | 755 | PHP dirs readable and traversable |

Set permissions script:

```bash
#!/bin/bash
XOOPS_PATH="/var/www/html/xoops"
WEB_USER="www-data"
WEB_GROUP="www-data"

# Set ownership
chown -R $WEB_USER:$WEB_GROUP $XOOPS_PATH

# Set directory permissions
find $XOOPS_PATH -type d -exec chmod 755 {} \;

# Set file permissions
find $XOOPS_PATH -type f -exec chmod 644 {} \;

# Make specific directories writable
chmod 777 $XOOPS_PATH/cache
chmod 777 $XOOPS_PATH/templates_c
chmod 777 $XOOPS_PATH/uploads
chmod 777 $XOOPS_PATH/var

# Make mainfile.php less accessible
chmod 644 $XOOPS_PATH/mainfile.php

echo "Permissions set successfully"
```

### Disk Space Requirements

| Component | Space |
|---|---|
| **XOOPS Core** | ~20MB |
| **Default Modules** | ~30MB |
| **Database** | 50MB minimum |
| **User Uploads** | Variable (100MB-1GB+) |
| **Backups** | 2-3x database size |
| **Logs** | 10-50MB (monitor) |
| **Recommended Total** | 500MB minimum |

## Server Environment

### Hosting Type

| Type | Suitable | Notes |
|---|---|---|
| **Shared Hosting** | Yes | Verify PHP and MySQL versions |
| **VPS** | Yes (Recommended) | Better control, scalability |
| **Dedicated Server** | Yes | Full control, best performance |
| **Cloud** (AWS, Azure, DigitalOcean) | Yes | Flexible, pay-as-you-go |

### Memory Requirements

| Type | Minimum | Recommended |
|---|---|---|
| **System RAM** | 512MB | 2GB+ |
| **PHP memory_limit** | 128MB | 256MB+ |
| **MySQL buffer_pool_size** | 128MB | 1GB+ |

### Network Requirements

| Requirement | Details |
|---|---|
| **Bandwidth** | 100 Mbps+ recommended |
| **Ports Open** | 80 (HTTP), 443 (HTTPS) |
| **Email Support** | SMTP for sending notifications |
| **DNS** | Standard DNS setup |

## System Requirements Checker

Use this PHP script to check server compatibility:

```php
<?php
$required = [
    'PHP Version' => version_compare(PHP_VERSION, '8.2.0') >= 0,
    'PHP-MySQL' => extension_loaded('mysqli') || extension_loaded('mysql'),
    'PHP-GD' => extension_loaded('gd'),
    'PHP-cURL' => extension_loaded('curl'),
    'PHP-OpenSSL' => extension_loaded('openssl'),
    'PHP-JSON' => extension_loaded('json'),
];

echo "<h2>XOOPS Server Requirements Check</h2>";
echo "<table border='1'>";
echo "<tr><th>Requirement</th><th>Status</th></tr>";

foreach ($required as $name => $status) {
    $status_text = $status ? '<span style="color:green;">OK</span>' : '<span style="color:red;">MISSING</span>';
    echo "<tr><td>$name</td><td>$status_text</td></tr>";
}

echo "</table>";

// Check php.ini settings
echo "<h3>PHP Configuration</h3>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . " seconds</p>";
?>
```

## Compliance Checklist

Before installing XOOPS, ensure:

- [ ] PHP version 8.2.0 or higher installed
- [ ] MySQL/MariaDB 5.7 or higher installed
- [ ] PDO and MySQL extensions enabled
- [ ] GD library extension enabled
- [ ] cURL extension installed (recommended)
- [ ] OpenSSL extension enabled
- [ ] Web server (Apache/Nginx) configured
- [ ] Mod_rewrite or URL rewriting enabled
- [ ] SSL/HTTPS certificate installed
- [ ] Write permissions available for cache, templates_c, uploads, var
- [ ] Minimum 500MB disk space available
- [ ] 128MB+ PHP memory_limit set
- [ ] Sendmail or SMTP configured for email
- [ ] DNS properly configured
- [ ] Domain name registered and pointing to server

## Next Steps

1. Verify all requirements are met
2. Proceed with [[../../06-Publisher-Module/User-Guide/Installation|XOOPS Installation]]
3. Configure [[../Configuration/Basic-Configuration|Basic Settings]] after installation
4. Review [[../Configuration/Security-Configuration|Security Settings]]

---

**Tags:** #server-requirements #installation #hosting #configuration

**Related Articles:**
- [[../../06-Publisher-Module/User-Guide/Installation]]
- [[../Configuration/Security-Configuration]]
- [[../Configuration/Performance-Optimization]]
