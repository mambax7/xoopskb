---
title: Template Variables
description: Available Smarty variables in XOOPS templates
tags:
  - smarty
  - templates
  - variables
  - xoops
created: 2025-01-28
updated: 2025-01-28
---

# Template Variables

XOOPS automatically provides many variables to Smarty templates. This reference documents the available variables for theme and module template development.

## Related Documentation

- [[Smarty-Basics]] - Fundamentals of Smarty in XOOPS
- [[Theme-Development]] - Creating XOOPS themes
- [[Smarty-4-Migration]] - Upgrading from Smarty 3 to 4

## Global Theme Variables

These variables are available in theme templates (`theme.tpl`):

### Site Information

| Variable | Description | Example |
|----------|-------------|---------|
| `$xoops_sitename` | Site name from preferences | `"My XOOPS Site"` |
| `$xoops_pagetitle` | Current page title | `"Welcome"` |
| `$xoops_slogan` | Site slogan | `"Just Use It!"` |
| `$xoops_url` | Full XOOPS URL | `"https://example.com"` |
| `$xoops_langcode` | Language code | `"en"` |
| `$xoops_charset` | Character set | `"UTF-8"` |

### Meta Tags

| Variable | Description |
|----------|-------------|
| `$xoops_meta_keywords` | Meta keywords |
| `$xoops_meta_description` | Meta description |
| `$xoops_meta_robots` | Robots meta tag |
| `$xoops_meta_rating` | Content rating |
| `$xoops_meta_author` | Author meta tag |
| `$xoops_meta_copyright` | Copyright notice |

### Theme Information

| Variable | Description |
|----------|-------------|
| `$xoops_theme` | Current theme name |
| `$xoops_imageurl` | Theme images directory URL |
| `$xoops_themecss` | Main theme CSS file URL |
| `$xoops_icons32_url` | 32x32 icons URL |
| `$xoops_icons16_url` | 16x16 icons URL |

### Page Content

| Variable | Description |
|----------|-------------|
| `$xoops_contents` | Main page content |
| `$xoops_module_header` | Module-specific head content |
| `$xoops_footer` | Footer content |
| `$xoops_js` | JavaScript to include |

### Navigation and Menus

| Variable | Description |
|----------|-------------|
| `$xoops_mainmenu` | Main navigation menu |
| `$xoops_usermenu` | User menu |

### Block Variables

| Variable | Description |
|----------|-------------|
| `$xoops_lblocks` | Array of left blocks |
| `$xoops_rblocks` | Array of right blocks |
| `$xoops_cblocks` | Array of center blocks |
| `$xoops_showlblock` | Show left blocks (boolean) |
| `$xoops_showrblock` | Show right blocks (boolean) |
| `$xoops_showcblock` | Show center blocks (boolean) |

## User Variables

When a user is logged in:

| Variable | Description |
|----------|-------------|
| `$xoops_isuser` | User is logged in (boolean) |
| `$xoops_isadmin` | User is admin (boolean) |
| `$xoops_userid` | User ID |
| `$xoops_uname` | Username |
| `$xoops_isowner` | User owns current content (boolean) |

### Access User Object Properties

```smarty
<{if $xoops_isuser}>
    <p>Welcome, <{$xoops_uname}>!</p>
    <p>Your email: <{$xoopsUser->getVar('email')}>}</p>
    <p>Joined: <{$xoopsUser->getVar('user_regdate')|date_format:"%Y-%m-%d"}>}</p>
<{else}>
    <p>Welcome, Guest!</p>
<{/if}>
```

## Module Variables

In module templates:

| Variable | Description |
|----------|-------------|
| `$xoops_dirname` | Module directory name |
| `$xoops_modulename` | Module display name |
| `$mod_url` | Module URL (when assigned) |

### Common Module Template Pattern

```php
// In PHP
$helper = \XoopsModules\MyModule\Helper::getInstance();
$GLOBALS['xoopsTpl']->assign('mod_url', $helper->url());
$GLOBALS['xoopsTpl']->assign('mod_name', $helper->getModule()->getVar('name'));
```

```smarty
{* In template *}
<a href="<{$mod_url}>">Back to <{$mod_name}></a>
```

## Block Variables

Each block in `$xoops_lblocks`, `$xoops_rblocks`, and `$xoops_cblocks` has:

| Property | Description |
|----------|-------------|
| `$block.id` | Block ID |
| `$block.title` | Block title |
| `$block.content` | Block HTML content |
| `$block.template` | Block template name |
| `$block.module` | Module name |
| `$block.weight` | Block weight/order |

### Block Display Example

```smarty
<{foreach item=block from=$xoops_lblocks}>
<div class="block block-<{$block.module}>">
    <{if $block.title}>
    <h3 class="block-title"><{$block.title}></h3>
    <{/if}>
    <div class="block-content">
        <{$block.content}>
    </div>
</div>
<{/foreach}>
```

## Form Variables

When using XoopsForm classes:

```php
// PHP
$form = new XoopsThemeForm('Edit Item', 'edit_form', 'save.php');
$form->addElement(new XoopsFormText('Title', 'title', 50, 255, $title));
$GLOBALS['xoopsTpl']->assign('form', $form->render());
```

```smarty
{* Template *}
<div class="form-container">
    <{$form}>
</div>
```

## Pagination Variables

```php
// PHP
include_once XOOPS_ROOT_PATH . '/class/pagenav.php';
$pagenav = new XoopsPageNav($total, $limit, $start, 'start');
$GLOBALS['xoopsTpl']->assign('page_nav', $pagenav->renderNav());
```

```smarty
{* Template *}
<{if $page_nav}>
<div class="pagination">
    <{$page_nav}>
</div>
<{/if}>
```

## Assigning Custom Variables

### Simple Values

```php
$GLOBALS['xoopsTpl']->assign('my_title', 'Custom Title');
$GLOBALS['xoopsTpl']->assign('item_count', 42);
$GLOBALS['xoopsTpl']->assign('is_featured', true);
```

```smarty
<h1><{$my_title}></h1>
<p><{$item_count}> items found</p>
<{if $is_featured}>Featured!<{/if}>
```

### Arrays

```php
$items = [
    ['id' => 1, 'name' => 'Item One', 'price' => 10.99],
    ['id' => 2, 'name' => 'Item Two', 'price' => 20.50],
];
$GLOBALS['xoopsTpl']->assign('items', $items);
```

```smarty
<ul>
<{foreach $items as $item}>
    <li>
        <{$item.name}> - $<{$item.price|string_format:"%.2f"}>
    </li>
<{/foreach}>
</ul>
```

### Objects

```php
$item = $itemHandler->get($itemId);
$GLOBALS['xoopsTpl']->assign('item', $item->toArray());

// Or for XoopsObject
$GLOBALS['xoopsTpl']->assign('item_obj', $item);
```

```smarty
{* Array access *}
<h2><{$item.title}></h2>
<p><{$item.content}></p>

{* Object method access *}
<h2><{$item_obj->getVar('title')}></h2>
```

### Nested Arrays

```php
$category = [
    'id' => 1,
    'name' => 'Technology',
    'items' => [
        ['id' => 1, 'title' => 'Article 1'],
        ['id' => 2, 'title' => 'Article 2'],
    ]
];
$GLOBALS['xoopsTpl']->assign('category', $category);
```

```smarty
<h2><{$category.name}></h2>
<ul>
<{foreach $category.items as $item}>
    <li><{$item.title}></li>
<{/foreach}>
</ul>
```

## Smarty Built-in Variables

### $smarty.now

Current timestamp:

```smarty
<p>Current year: <{$smarty.now|date_format:"%Y"}></p>
<p>Current date: <{$smarty.now|date_format:"%Y-%m-%d"}></p>
<p>Current time: <{$smarty.now|date_format:"%H:%M:%S"}></p>
```

### $smarty.const

Access PHP constants:

```smarty
<p>XOOPS URL: <{$smarty.const.XOOPS_URL}></p>
<p>Root Path: <{$smarty.const.XOOPS_ROOT_PATH}></p>
<p>Upload Path: <{$smarty.const.XOOPS_UPLOAD_PATH}></p>
```

### $smarty.get, $smarty.post, $smarty.request

Access request variables (use with caution):

```smarty
{* Only for reading, always escape output! *}
<{if $smarty.get.page}>
    Page: <{$smarty.get.page|escape}>
<{/if}>
```

### $smarty.server

Server variables:

```smarty
<p>Server: <{$smarty.server.SERVER_NAME}></p>
<p>Request URI: <{$smarty.server.REQUEST_URI|escape}></p>
```

### $smarty.foreach

Loop information:

```smarty
<{foreach $items as $item name=itemloop}>
    <{* Index (0-based) *}>
    Index: <{$smarty.foreach.itemloop.index}>

    <{* Iteration (1-based) *}>
    Number: <{$smarty.foreach.itemloop.iteration}>

    <{* First item *}>
    <{if $smarty.foreach.itemloop.first}>First Item!<{/if}>

    <{* Last item *}>
    <{if $smarty.foreach.itemloop.last}>Last Item!<{/if}>

    <{* Total count *}>
    Total: <{$smarty.foreach.itemloop.total}>
<{/foreach}>
```

## XMF Helper Variables

When using XMF, additional helpers are available:

```php
// In PHP
use Xmf\Module\Helper;

$helper = Helper::getInstance();
$GLOBALS['xoopsTpl']->assign('mod_config', $helper->getConfig());
$GLOBALS['xoopsTpl']->assign('mod_url', $helper->url());
$GLOBALS['xoopsTpl']->assign('mod_path', $helper->path());
```

```smarty
{* In template *}
<a href="<{$mod_url}>">Module Home</a>
<{if $mod_config.show_breadcrumb}>
    {* Breadcrumb HTML *}
<{/if}>
```

## Image and Asset URLs

```smarty
{* Theme images *}
<img src="<{$xoops_imageurl}>images/logo.png" alt="Logo">

{* Module images *}
<img src="<{$xoops_url}>/modules/<{$xoops_dirname}>/assets/images/icon.png">

{* Upload directory *}
<img src="<{$xoops_url}>/uploads/mymodule/<{$item.image}>">

{* Using icons *}
<img src="<{$xoops_icons32_url}>edit.png" alt="Edit">
<img src="<{$xoops_icons16_url}>delete.png" alt="Delete">
```

## Conditional Display Based on User

```smarty
{* Show only to logged-in users *}
<{if $xoops_isuser}>
    <a href="<{$xoops_url}>/modules/profile/">My Profile</a>
    <a href="<{$xoops_url}>/user.php?op=logout">Logout</a>
<{else}>
    <a href="<{$xoops_url}>/user.php">Login</a>
    <a href="<{$xoops_url}>/register.php">Register</a>
<{/if}>

{* Show only to admins *}
<{if $xoops_isadmin}>
    <a href="<{$xoops_url}>/admin.php">Admin Panel</a>
<{/if}>

{* Show only to content owner *}
<{if $xoops_isowner || $xoops_isadmin}>
    <a href="edit.php?id=<{$item.id}>">Edit</a>
    <a href="delete.php?id=<{$item.id}>">Delete</a>
<{/if}>
```

## Language Variables

```php
// In PHP - load language file
xoops_loadLanguage('main', 'mymodule');

// Assign language constants
$GLOBALS['xoopsTpl']->assign('lang_title', _MD_MYMODULE_TITLE);
$GLOBALS['xoopsTpl']->assign('lang_submit', _SUBMIT);
```

```smarty
{* In template *}
<h1><{$lang_title}></h1>
<button type="submit"><{$lang_submit}></button>
```

Or use constants directly:

```smarty
<h1><{$smarty.const._MD_MYMODULE_TITLE}></h1>
```

## Debugging Variables

To see all available variables:

```smarty
{* Display debug console *}
<{debug}>

{* Print specific variable *}
<pre><{$myvar|@print_r}></pre>

{* Export variable *}
<pre><{$myvar|@var_export}></pre>
```

---

#smarty #templates #variables #xoops #reference
