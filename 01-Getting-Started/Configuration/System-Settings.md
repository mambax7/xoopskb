---
title: System Settings
description: Comprehensive guide to XOOPS admin system settings, configuration options, and preferences hierarchy
created: 2025-01-28
updated: 2025-01-28
version: 2.5.8
category: Configuration
---

# XOOPS System Settings

This guide covers the complete system settings available in the XOOPS admin panel, organized by category.

## System Settings Architecture

```mermaid
graph TD
    A[System Settings] --> B[General Settings]
    A --> C[User Settings]
    A --> D[Module Settings]
    A --> E[Meta Tags & Footer]
    A --> F[Email Settings]
    A --> G[Cache Settings]
    A --> H[URL Settings]
    A --> I[Security Settings]
    B --> B1[Site Name]
    B --> B2[Timezone]
    B --> B3[Language]
    C --> C1[Registration]
    C --> C2[Profiles]
    C --> C3[Permissions]
    F --> F1[SMTP Config]
    F --> F2[Notification Rules]
```

## Accessing System Settings

### Location

**Admin Panel > System > Preferences**

Or navigate directly:

```
http://your-domain.com/xoops/admin/index.php?fct=preferences
```

### Permission Requirements

- Only administrators (webmasters) can access system settings
- Changes affect the entire site
- Most changes take effect immediately

## General Settings

The foundational configuration for your XOOPS installation.

### Basic Information

```
Site Name: [Your Site Name]
Default Description: [Brief description of your site]
Site Slogan: [Catchy slogan]
Admin Email: admin@your-domain.com
Webmaster Name: Administrator Name
Webmaster Email: admin@your-domain.com
```

### Appearance Settings

```
Default Theme: [Select theme]
Default Language: English (or preferred language)
Items Per Page: 15 (typically 10-25)
Words in Snippet: 25 (for search results)
Theme Upload Permission: Disabled (security)
```

### Regional Settings

```
Default Timezone: [Your timezone]
Date Format: %Y-%m-%d (YYYY-MM-DD format)
Time Format: %H:%M:%S (HH:MM:SS format)
Daylight Saving Time: [Auto/Manual/None]
```

**Timezone Format Table:**

| Region | Timezone | UTC Offset |
|---|---|---|
| US Eastern | America/New_York | -5 / -4 |
| US Central | America/Chicago | -6 / -5 |
| US Mountain | America/Denver | -7 / -6 |
| US Pacific | America/Los_Angeles | -8 / -7 |
| UK/London | Europe/London | 0 / +1 |
| France/Germany | Europe/Paris | +1 / +2 |
| Japan | Asia/Tokyo | +9 |
| China | Asia/Shanghai | +8 |
| Australia/Sydney | Australia/Sydney | +10 / +11 |

### Search Configuration

```
Enable Search: Yes
Search Admin Pages: Yes/No
Search Archives: Yes
Default Search Type: All / Pages only
Words Excluded from Search: [Comma-separated list]
```

**Common excluded words:** the, a, an, and, or, but, in, on, at, by, to, from

## User Settings

Control user account behavior and registration process.

### User Registration

```
Allow User Registration: Yes/No
Registration Type:
  ☐ Auto-activate (Instant access)
  ☐ Admin approval (Admin must approve)
  ☐ Email verification (User must verify email)

Notification to Users: Yes/No
User Email Verification: Required/Optional
```

### New User Configuration

```
Auto-login New Users: Yes/No
Assign Default User Group: Yes
Default User Group: [Select group]
Create User Avatar: Yes/No
Initial User Avatar: [Select default]
```

### User Profile Settings

```
Allow User Profiles: Yes
Show Member List: Yes
Show User Statistics: Yes
Show Last Online Time: Yes
Allow User Avatar: Yes
Avatar Max File Size: 100KB
Avatar Dimensions: 100x100 pixels
```

### User Email Settings

```
Allow Users to Hide Email: Yes
Show Email on Profile: Yes
Notification Email Interval: Immediately/Daily/Weekly/Never
```

### User Activity Tracking

```
Track User Activity: Yes
Log User Logins: Yes
Log Failed Logins: Yes
Track IP Address: Yes
Clear Activity Logs Older Than: 90 days
```

### Account Limits

```
Allow Duplicate Email: No
Minimum Username Length: 3 characters
Maximum Username Length: 15 characters
Minimum Password Length: 6 characters
Require Special Characters: Yes
Require Numbers: Yes
Password Expiration: 90 days (or Never)
Accounts Inactive Days to Delete: 365 days
```

## Module Settings

Configure individual module behavior.

### Common Module Options

For each installed module, you can set:

```
Module Status: Active/Inactive
Display in Menu: Yes/No
Module Weight: [1-999] (higher = lower in display)
Homepage Default: This module shows when visiting /
Admin Access: [Allowed user groups]
User Access: [Allowed user groups]
```

### System Module Settings

```
Show Homepage as: Portal / Module / Static Page
Default Homepage Module: [Select module]
Show Footer Menu: Yes
Footer Color: [Color selector]
Show System Stats: Yes
Show Memory Usage: Yes
```

### Configuration per Module

Each module can have module-specific settings:

**Example - Page Module:**
```
Enable Comments: Yes/No
Moderate Comments: Yes/No
Comments Per Page: 10
Enable Ratings: Yes
Allow Anonymous Ratings: Yes
```

**Example - User Module:**
```
Avatar Upload Folder: ./uploads/
Maximum Upload Size: 100KB
Allow File Upload: Yes
Allowed File Types: jpg, gif, png
```

Access module-specific settings:
- **Admin > Modules > [Module Name] > Preferences**

## Meta Tags & SEO Settings

Configure meta tags for search engine optimization.

### Global Meta Tags

```
Meta Keywords: xoops, cms, content management system
Meta Description: A powerful content management system for building dynamic websites
Meta Author: Your Name
Meta Copyright: Copyright 2025, Your Company
Meta Robots: index, follow
Meta Revisit: 30 days
```

### Meta Tag Best Practices

| Tag | Purpose | Recommendation |
|---|---|---|
| Keywords | Search terms | 5-10 relevant keywords, comma-separated |
| Description | Search listing | 150-160 characters |
| Author | Page creator | Your name or company |
| Copyright | Legal | Your copyright notice |
| Robots | Crawler instructions | index, follow (allow indexing) |

### Footer Settings

```
Show Footer: Yes
Footer Color: Dark/Light
Footer Background: [Color code]
Footer Text: [HTML allowed]
Additional Footer Links: [URL and text pairs]
```

**Sample Footer HTML:**
```html
<p>Copyright &copy; 2025 Your Company. All rights reserved.</p>
<p><a href="/privacy">Privacy Policy</a> | <a href="/terms">Terms of Use</a></p>
```

### Social Meta Tags (Open Graph)

```
Enable Open Graph: Yes
Facebook App ID: [App ID]
Twitter Card Type: summary / summary_large_image / player
Default Share Image: [Image URL]
```

## Email Settings

Configure email delivery and notification system.

### Email Delivery Method

```
Use SMTP: Yes/No

If SMTP:
  SMTP Host: smtp.gmail.com
  SMTP Port: 587 (TLS) or 465 (SSL)
  SMTP Security: TLS / SSL / None
  SMTP Username: [email@example.com]
  SMTP Password: [password]
  SMTP Authentication: Yes/No
  SMTP Timeout: 10 seconds

If PHP mail():
  Sendmail Path: /usr/sbin/sendmail -t -i
```

### Email Configuration

```
From Address: noreply@your-domain.com
From Name: Your Site Name
Reply-To Address: support@your-domain.com
BCC Admin Emails: Yes/No
```

### Notification Settings

```
Send Welcome Email: Yes/No
Welcome Email Subject: Welcome to [Site Name]
Welcome Email Body: [Custom message]

Send Password Reset Email: Yes/No
Include Random Password: Yes/No
Token Expiration: 24 hours
```

### Admin Notifications

```
Notify Admin on Registration: Yes
Notify Admin on Comments: Yes
Notify Admin on Submissions: Yes
Notify Admin on Errors: Yes
```

### User Notifications

```
Notify User on Registration: Yes
Notify User on Comments: Yes
Notify User on Private Messages: Yes
Allow Users to Disable Notifications: Yes
Default Notification Frequency: Immediately
```

### Email Templates

Customize notification emails in admin panel:

**Path:** System > Email Templates

Available templates:
- User Registration
- Password Reset
- Comment Notification
- Private Message
- System Alerts
- Module-specific emails

## Cache Settings

Optimize performance through caching.

### Cache Configuration

```
Enable Caching: Yes/No
Cache Type:
  ☐ File Cache
  ☐ APCu (Alternative PHP Cache)
  ☐ Memcache (Distributed caching)
  ☐ Redis (Advanced caching)

Cache Lifetime: 3600 seconds (1 hour)
```

### Cache Options by Type

**File Cache:**
```
Cache Directory: /var/www/html/xoops/cache/
Clear Interval: Daily
Maximum Cache Files: 1000
```

**APCu Cache:**
```
Memory Allocation: 128MB
Fragmentation Level: Low
```

**Memcache/Redis:**
```
Server Host: localhost
Server Port: 11211 (Memcache) / 6379 (Redis)
Persistent Connection: Yes
```

### What Gets Cached

```
Cache Module Lists: Yes
Cache Configuration Data: Yes
Cache Template Data: Yes
Cache User Session Data: Yes
Cache Search Results: Yes
Cache Database Queries: Yes
Cache RSS Feeds: Yes
Cache Images: Yes
```

## URL Settings

Configure URL rewriting and formatting.

### Friendly URL Settings

```
Enable Friendly URLs: Yes/No
Friendly URL Type:
  ☐ Path Info: /page/about
  ☐ Query String: /index.php?p=about

Trailing Slash: Include / Omit
URL Case: Lower case / Case sensitive
```

### URL Rewrite Rules

```
.htaccess Rules: [Display current]
Nginx Rules: [Display current if Nginx]
IIS Rules: [Display current if IIS]
```

## Security Settings

Control security-related configuration.

### Password Security

```
Password Policy:
  ☐ Require uppercase letters
  ☐ Require lowercase letters
  ☐ Require numbers
  ☐ Require special characters

Minimum Password Length: 8 characters
Password Expiration: 90 days
Password History: Remember last 5 passwords
Force Password Change: On next login
```

### Login Security

```
Lock Account After Failed Attempts: 5 attempts
Lock Duration: 15 minutes
Log All Login Attempts: Yes
Log Failed Logins: Yes
Admin Login Alert: Send email on admin login
Two-Factor Authentication: Disabled/Enabled
```

### File Upload Security

```
Allow File Uploads: Yes/No
Maximum File Size: 128MB
Allowed File Types: jpg, gif, png, pdf, zip, doc, docx
Scan Uploads for Malware: Yes (if available)
Quarantine Suspicious Files: Yes
```

### Session Security

```
Session Management: Database/Files
Session Timeout: 1800 seconds (30 min)
Session Cookie Lifetime: 0 (until browser closes)
Secure Cookie: Yes (HTTPS only)
HTTP Only Cookie: Yes (prevent JavaScript access)
```

### CORS Settings

```
Allow Cross-Origin Requests: No
Allowed Origins: [List domains]
Allow Credentials: No
Allowed Methods: GET, POST
```

## Advanced Settings

Additional configuration options for advanced users.

### Debug Mode

```
Debug Mode: Disabled/Enabled
Log Level: Error / Warning / Info / Debug
Debug Log File: /var/log/xoops_debug.log
Display Errors: Disabled (production)
```

### Performance Tuning

```
Optimize Database Queries: Yes
Use Query Cache: Yes
Compress Output: Yes
Minify CSS/JavaScript: Yes
Lazy Load Images: Yes
```

### Content Settings

```
Allow HTML in Posts: Yes/No
Allowed HTML Tags: [Configure]
Strip Harmful Code: Yes
Allow Embed: Yes/No
Content Moderation: Automatic/Manual
Spam Detection: Yes
```

## Settings Export/Import

### Backup Settings

Export current settings:

**Admin Panel > System > Tools > Export Settings**

```bash
# Settings exported as JSON file
# Download and store securely
```

### Restore Settings

Import previously exported settings:

**Admin Panel > System > Tools > Import Settings**

```bash
# Upload JSON file
# Verify changes before confirming
```

## Configuration Hierarchy

XOOPS settings hierarchy (top to bottom - first match wins):

```
1. mainfile.php (Constants)
2. Module-specific config
3. Admin System Settings
4. Theme configuration
5. User preferences (for user-specific settings)
```

## Settings Backup Script

Create a backup of current settings:

```php
<?php
// Backup script: /var/www/html/xoops/backup-settings.php
require_once __DIR__ . '/mainfile.php';

$config_handler = xoops_getHandler('config');
$configs = $config_handler->getConfigs();

$backup = [
    'exported_date' => date('Y-m-d H:i:s'),
    'xoops_version' => XOOPS_VERSION,
    'php_version' => PHP_VERSION,
    'settings' => []
];

foreach ($configs as $config) {
    $backup['settings'][$config->getVar('conf_name')] = [
        'value' => $config->getVar('conf_value'),
        'description' => $config->getVar('conf_desc'),
        'type' => $config->getVar('conf_type'),
    ];
}

// Save to JSON file
file_put_contents(
    '/backups/xoops_settings_' . date('YmdHis') . '.json',
    json_encode($backup, JSON_PRETTY_PRINT)
);

echo "Settings backed up successfully!";
?>
```

## Common Settings Changes

### Change Site Name

1. Admin > System > Preferences > General Settings
2. Modify "Site Name"
3. Click "Save"

### Enable/Disable Registration

1. Admin > System > Preferences > User Settings
2. Toggle "Allow User Registration"
3. Choose registration type
4. Click "Save"

### Change Default Theme

1. Admin > System > Preferences > General Settings
2. Select "Default Theme"
3. Click "Save"
4. Clear cache for changes to take effect

### Update Contact Email

1. Admin > System > Preferences > General Settings
2. Modify "Admin Email"
3. Modify "Webmaster Email"
4. Click "Save"

## Verification Checklist

After configuring system settings, verify:

- [ ] Site name displays correctly
- [ ] Timezone shows correct time
- [ ] Email notifications send properly
- [ ] User registration works as configured
- [ ] Homepage displays selected default
- [ ] Search functionality works
- [ ] Cache improves page load time
- [ ] Friendly URLs work (if enabled)
- [ ] Meta tags appear in page source
- [ ] Admin notifications received
- [ ] Security settings enforced

## Troubleshooting Settings

### Settings Not Saving

**Solution:**
```bash
# Check file permissions on config directory
chmod 755 /var/www/html/xoops/var/

# Verify database writable
# Try saving again in admin panel
```

### Changes Not Taking Effect

**Solution:**
```bash
# Clear cache
rm -rf /var/www/html/xoops/cache/*
rm -rf /var/www/html/xoops/templates_c/*

# If still not working, restart web server
systemctl restart apache2
```

### Email Not Sending

**Solution:**
1. Verify SMTP credentials in email settings
2. Test with "Send Test Email" button
3. Check error logs
4. Try using PHP mail() instead of SMTP

## Next Steps

After system settings configuration:

1. [[Security-Configuration|Configure security settings]]
2. [[Performance-Optimization|Optimize performance]]
3. [[../First-Steps/Admin-Panel-Overview|Explore admin panel features]]
4. [[../First-Steps/Managing-Users|Set up user management]]

---

**Tags:** #system-settings #configuration #preferences #admin-panel

**Related Articles:**
- [[../../06-Publisher-Module/User-Guide/Basic-Configuration]]
- [[Security-Configuration]]
- [[Performance-Optimization]]
- [[../First-Steps/Admin-Panel-Overview]]
