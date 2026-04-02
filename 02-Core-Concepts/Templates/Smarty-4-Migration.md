---
title: Smarty 4 Migration
description: Guide to upgrading XOOPS templates from Smarty 3 to Smarty 4
tags:
  - smarty
  - migration
  - upgrade
  - xoops
  - smarty4
created: 2025-01-28
updated: 2025-01-28
---

# Smarty 4 Migration

This guide covers the changes and migration steps needed when upgrading from Smarty 3 to Smarty 4 in XOOPS. Understanding these differences is essential for maintaining compatibility with modern XOOPS installations.

## Related Documentation

- [[Smarty-Basics]] - Fundamentals of Smarty in XOOPS
- [[Theme-Development]] - Creating XOOPS themes
- [[Template-Variables]] - Available variables in templates

## Overview of Changes

Smarty 4 introduced several breaking changes from Smarty 3:

1. Variable assignment behavior changed
2. `{php}` tags completely removed
3. Caching API changes
4. Modifier handling updates
5. Security policy changes
6. Deprecated features removed

## Variable Access Changes

### The Problem

In Smarty 2/3, assigned values were directly accessible:

```php
// PHP
$GLOBALS['xoopsTpl']->assign('mod_url', $helper->url());
```

```smarty
{* Smarty 2/3 - worked fine *}
<img src="<{$mod_url}>/assets/images/icon.png">
```

In Smarty 4, variables are wrapped in `Smarty_Variable` objects:

```
Smarty_Variable Object
(
    [value] => http://example.com/modules/mymodule/
    [nocache] =>
)
```

### Solution 1: Access the Value Property

```smarty
{* Smarty 4 - access the value property *}
<img src="<{$mod_url->value}>/assets/images/icon.png">
```

### Solution 2: Compatibility Mode

Enable compatibility mode in PHP:

```php
$smarty = new Smarty();
$smarty->setCompatibilityMode(true);
```

This allows direct variable access like Smarty 3.

### Solution 3: Conditional Version Check

Write templates that work in both versions:

```smarty
<{if $smarty.version|regex_replace:'[^0-9]':'' >= 4}>
    <{$mod_url->value}>
<{else}>
    <{$mod_url}>
<{/if}>
```

### Solution 4: Wrapper Function

Create a helper function for assignments:

```php
function smartyAssign($smarty, $name, $value)
{
    if (version_compare($smarty->version, '4.0.0', '>=')) {
        // Smarty 4+ - assign normally, access via ->value in templates
        $smarty->assign($name, $value);
    } else {
        // Smarty 3 - standard assignment
        $smarty->assign($name, $value);
    }
}
```

## Removing {php} Tags

### The Problem

Smarty 3+ does not support `{php}` tags for security reasons:

```smarty
{* This NO LONGER works in Smarty 3+ *}
<{assign var="cid" value=$downloads.cid}>
<{php}>
    $catid = $this->get_template_vars('cid');
<{/php}>
```

### Solution: Use Smarty Variables

```smarty
{* Use Smarty's built-in variable access *}
<{assign var="cid" value=$downloads.cid}>
<{assign var="catid" value=$smarty.template_vars.cid}>
```

### Solution: Move Logic to PHP

Complex logic should be in PHP, not templates:

```php
// In PHP - do the processing
$catid = $downloads['cid'];
$categoryInfo = getCategoryInfo($catid);

// Assign processed data to template
$GLOBALS['xoopsTpl']->assign('category', $categoryInfo);
```

```smarty
{* In template - just display *}
<h2><{$category.name}></h2>
```

### Solution: Custom Plugins

For reusable functionality, create Smarty plugins:

```php
// /class/smarty/plugins/function.getcategory.php
function smarty_function_getcategory($params, $smarty)
{
    $catId = $params['id'] ?? 0;
    $categoryHandler = xoops_getModuleHandler('category', 'mymodule');
    $category = $categoryHandler->get($catId);

    if ($category) {
        $smarty->assign($params['assign'], $category->toArray());
    }
}
```

```smarty
{* In template *}
<{getcategory id=$cid assign="category"}>
<h2><{$category.name}></h2>
```

## Caching Changes

### Smarty 3 Caching

```php
// Smarty 3 style
$smarty->caching = true;
$smarty->cache_lifetime = 3600;
$smarty->cache_dir = '/path/to/cache';

// Per-variable nocache
$xoopsTpl->tpl_vars["mod_url"]->nocache = false;
```

### Smarty 4 Caching

```php
// Smarty 4 style
$smarty->setCaching(Smarty::CACHING_LIFETIME_CURRENT);
$smarty->setCacheLifetime(3600);
$smarty->setCacheDir('/path/to/cache');

// Or using properties (still works)
$smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
$smarty->cache_lifetime = 3600;
```

### Caching Constants

```php
// Caching modes
Smarty::CACHING_OFF                  // No caching
Smarty::CACHING_LIFETIME_CURRENT     // Use cache_lifetime
Smarty::CACHING_LIFETIME_SAVED       // Use cached lifetime
```

### Nocache in Templates

```smarty
{* Mark content as never cached *}
<{nocache}>
    <p>Current time: <{$smarty.now|date_format:"%H:%M:%S"}></p>
<{/nocache}>
```

## Modifier Changes

### String Modifiers

Some modifiers were renamed or deprecated:

```smarty
{* Smarty 3 *}
<{$text|escape:'htmlall'}>

{* Smarty 4 - use 'html' instead *}
<{$text|escape:'html'}>
```

### Array Modifiers

Array modifiers require `@` prefix:

```smarty
{* Count array elements *}
<{$items|@count}> items

{* Join array *}
<{$tags|@implode:', '}>

{* JSON encode *}
<{$data|@json_encode}>
```

### Custom Modifiers

Custom modifiers must be registered:

```php
// Register a custom modifier
$smarty->registerPlugin('modifier', 'my_modifier', 'my_modifier_function');

function my_modifier_function($string, $param1 = 'default')
{
    // Process and return
    return processed_string($string, $param1);
}
```

## Security Policy Changes

### Smarty 4 Security

Smarty 4 has stricter default security:

```php
// Configure security policy
$smarty->enableSecurity('Smarty_Security');

// Or create custom policy
class MySecurityPolicy extends Smarty_Security
{
    public $php_functions = ['isset', 'empty', 'count'];
    public $php_modifiers = ['escape', 'count'];
    public $allow_super_globals = false;
}

$smarty->enableSecurity(new MySecurityPolicy($smarty));
```

### Allowed Functions

By default, Smarty 4 restricts which PHP functions can be used:

```smarty
{* These may be restricted *}
<{if isset($variable)}>
<{if empty($array)}>
<{$array|@count}>
```

Configure allowed functions if needed:

```php
$smarty->security_policy->php_functions = [
    'isset', 'empty', 'count', 'sizeof',
    'in_array', 'is_array', 'date', 'time'
];
```

## Template Inheritance Updates

### Block Syntax

Block syntax remains similar but with some changes:

```smarty
{* Parent template *}
<html>
<head>
    {block name=head}
    <title>Default Title</title>
    {/block}
</head>
<body>
    {block name=content}{/block}
</body>
</html>
```

```smarty
{* Child template *}
{extends file="parent.tpl"}

{block name=head}
    {$smarty.block.parent}  {* Include parent block content *}
    <meta name="custom" content="value">
{/block}

{block name=content}
    <h1>My Content</h1>
{/block}
```

### Append and Prepend

```smarty
{block name=head append}
    {* This is added after parent content *}
    <link rel="stylesheet" href="extra.css">
{/block}

{block name=scripts prepend}
    {* This is added before parent content *}
    <script src="early.js"></script>
{/block}
```

## Deprecated Features

### Removed in Smarty 4

| Feature | Alternative |
|---------|-------------|
| `{php}` tags | Move logic to PHP or use plugins |
| `{include_php}` | Use registered plugins |
| `$smarty.capture` | Still works but deprecated |
| `{strip}` with spaces | Use minification tools |

### Use Alternatives

```smarty
{* Instead of {php} *}
{* Move to PHP and assign result *}

{* Instead of include_php *}
<{include file="db:mytemplate.tpl"}>

{* Instead of capture (still works but consider) *}
<{capture name="sidebar"}>
    <h3>Sidebar</h3>
<{/capture}>
<div><{$smarty.capture.sidebar}></div>
```

## Migration Checklist

### Before Migration

1. [ ] Backup all templates
2. [ ] List all `{php}` tag usage
3. [ ] Document custom plugins
4. [ ] Test current functionality

### During Migration

1. [ ] Remove all `{php}` tags
2. [ ] Update variable access syntax
3. [ ] Check modifier usage
4. [ ] Update caching configuration
5. [ ] Review security settings

### After Migration

1. [ ] Test all templates
2. [ ] Check all forms work
3. [ ] Verify caching works
4. [ ] Test with different user roles

## Testing for Compatibility

### Version Detection

```php
// Check Smarty version in PHP
$version = Smarty::SMARTY_VERSION;

if (version_compare($version, '4.0.0', '>=')) {
    // Smarty 4+ specific code
} else {
    // Smarty 3 code
}
```

### Template Version Check

```smarty
{* Check version in template *}
<{assign var="smarty_major" value=$smarty.version|regex_replace:'/\\..*$/':''}>

<{if $smarty_major >= 4}>
    {* Smarty 4+ template code *}
<{else}>
    {* Smarty 3 template code *}
<{/if}>
```

## Writing Cross-Compatible Templates

### Best Practices

1. **Avoid `{php}` tags entirely** - They do not work in Smarty 3+

2. **Keep templates simple** - Complex logic belongs in PHP

3. **Use standard modifiers** - Avoid deprecated ones

4. **Test in both versions** - If you need to support both

5. **Use plugins for complex operations** - More maintainable

### Example: Cross-Compatible Template

```smarty
{* Works in both Smarty 3 and 4 *}
<!DOCTYPE html>
<html>
<head>
    <title><{$page_title|default:'Default Title'|escape}></title>
</head>
<body>
    <{if isset($items) && $items|@count > 0}>
        <ul>
        <{foreach $items as $item}>
            <li><{$item.name|escape}></li>
        <{/foreach}>
        </ul>
    <{else}>
        <p>No items found.</p>
    <{/if}>
</body>
</html>
```

## Common Migration Issues

### Issue: Variables Return Empty

**Problem**: `<{$mod_url}>` returns nothing in Smarty 4

**Solution**: Use `<{$mod_url->value}>` or enable compatibility mode

### Issue: PHP Tag Errors

**Problem**: Template throws error on `{php}` tags

**Solution**: Remove all PHP tags and move logic to PHP files

### Issue: Modifier Not Found

**Problem**: Custom modifier throws "unknown modifier" error

**Solution**: Register the modifier with `registerPlugin()`

### Issue: Security Restriction

**Problem**: Function not allowed in template

**Solution**: Add function to security policy's allowed list

---

#smarty #migration #upgrade #xoops #smarty4 #compatibility
