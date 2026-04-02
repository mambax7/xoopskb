---
title: Publisher Module
description: Complete documentation for the Publisher news and blog module for XOOPS
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: modules
---

# 📰 Publisher Module

> The premier news and blog publishing module for XOOPS CMS.

---

## Overview

Publisher is the definitive content management module for XOOPS, evolved from SmartSection to become the most feature-rich blog and news solution. It provides comprehensive tools for creating, organizing, and publishing content with full editorial workflow support.

**Requirements:**
- XOOPS 2.5.10+
- PHP 8.2+

---

## 🌟 Key Features

### Content Management
- **Categories & Subcategories** - Hierarchical content organization
- **Rich Text Editing** - Multiple WYSIWYG editors supported
- **File Attachments** - Attach files to articles
- **Image Management** - Page and category images
- **File Wrapping** - Wrap files as articles

### Publishing Workflow
- **Scheduled Publishing** - Set future publish dates
- **Expiration Dates** - Auto-expire content
- **Moderation** - Editorial approval workflow
- **Draft Management** - Save work in progress

### Display & Templates
- **Four Base Templates** - Multiple display layouts
- **Custom Templates** - Create your own designs
- **SEO Optimization** - Search engine friendly URLs
- **Responsive Design** - Mobile-ready output

### User Interaction
- **Ratings** - Article rating system
- **Comments** - Reader discussions
- **Social Sharing** - Share to social networks

### Permissions
- **Submission Control** - Who can submit articles
- **Field-Level Permissions** - Control form fields by group
- **Category Permissions** - Access control per category
- **Moderation Rights** - Global moderation settings

---

## 🗂️ Section Contents

### User Guide
- [[User-Guide/Installation|Installation Guide]]
- [[User-Guide/Basic-Configuration|Basic Configuration]]
- [[User-Guide/Creating-Articles|Creating Articles]]
- [[User-Guide/Managing-Categories|Managing Categories]]
- [[User-Guide/Permissions-Setup|Setting Up Permissions]]

### Developer Guide
- [[Developer-Guide/Extending-Publisher|Extending Publisher]]
- [[Developer-Guide/Custom-Templates|Creating Custom Templates]]
- [[Developer-Guide/API-Reference|API Reference]]
- [[Developer-Guide/Hooks-and-Events|Hooks and Events]]

---

## 🚀 Quick Start

### 1. Installation

```bash
# Download from GitHub
git clone https://github.com/XoopsModules25x/publisher.git

# Copy to modules directory
cp -r publisher /path/to/xoops/htdocs/modules/
```

Then install via XOOPS Admin → Modules → Install.

### 2. Create Your First Category

1. Go to **Admin → Publisher → Categories**
2. Click **Add Category**
3. Fill in:
   - **Name**: News
   - **Description**: Latest news and updates
   - **Image**: Upload category image
4. Save

### 3. Create Your First Article

1. Go to **Admin → Publisher → Articles**
2. Click **Add Article**
3. Fill in:
   - **Title**: Welcome to Our Site
   - **Category**: News
   - **Content**: Your article content
4. Set **Status**: Published
5. Save

---

## ⚙️ Configuration Options

### General Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Editor | WYSIWYG editor to use | XOOPS Default |
| Items per page | Articles shown per page | 10 |
| Show breadcrumb | Display navigation trail | Yes |
| Allow ratings | Enable article ratings | Yes |
| Allow comments | Enable article comments | Yes |

### SEO Settings

| Setting | Description | Default |
|---------|-------------|---------|
| SEO URLs | Enable friendly URLs | No |
| URL rewriting | Apache mod_rewrite | None |
| Meta keywords | Auto-generate keywords | Yes |

### Permissions Matrix

| Permission | Anonymous | Registered | Editor | Admin |
|------------|-----------|------------|--------|-------|
| View articles | ✓ | ✓ | ✓ | ✓ |
| Submit articles | ✗ | ✓ | ✓ | ✓ |
| Edit own articles | ✗ | ✓ | ✓ | ✓ |
| Edit all articles | ✗ | ✗ | ✓ | ✓ |
| Approve articles | ✗ | ✗ | ✓ | ✓ |
| Manage categories | ✗ | ✗ | ✗ | ✓ |

---

## 📦 Module Structure

```
modules/publisher/
├── admin/                  # Admin interface
│   ├── index.php
│   ├── category.php
│   ├── item.php
│   └── menu.php
├── class/                  # PHP classes
│   ├── Category.php
│   ├── CategoryHandler.php
│   ├── Item.php
│   ├── ItemHandler.php
│   └── Helper.php
├── include/                # Include files
│   ├── common.php
│   └── functions.php
├── templates/              # Smarty templates
│   ├── publisher_index.tpl
│   ├── publisher_item.tpl
│   └── publisher_category.tpl
├── language/               # Translations
│   └── english/
├── sql/                    # Database schema
│   └── mysql.sql
├── xoops_version.php       # Module info
└── index.php               # Module entry
```

---

## 🔄 Migration

### From SmartSection

Publisher includes a built-in migration tool:

1. Go to **Admin → Publisher → Import**
2. Select **SmartSection** as source
3. Choose import options:
   - Categories
   - Articles
   - Comments
4. Click **Import**

### From News Module

1. Go to **Admin → Publisher → Import**
2. Select **News** as source
3. Map categories
4. Click **Import**

---

## 🔗 Related Documentation

- [[../03-Module-Development/Module-Development|Module Development Guide]]
- [[../02-Core-Concepts/Templates/Smarty-Basics|Smarty Templating]]
- [[../05-XMF-Framework/XMF-Framework|XMF Framework]]

---

## 📚 Resources

- [GitHub Repository](https://github.com/XoopsModules25x/publisher)
- [Issue Tracker](https://github.com/XoopsModules25x/publisher/issues)
- [Original Tutorial](https://xoops.gitbook.io/publisher-tutorial/)

---

#xoops #publisher #module #blog #news #cms #content-management
