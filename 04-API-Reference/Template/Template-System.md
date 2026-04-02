---
title: XOOPS Template System
description: Smarty integration, XoopsTpl class, template variables, theme management, and template rendering
created: 2024-01-31
updated: 2024-01-31
version: 2.5.11
tags:
  - api
  - template
  - smarty
  - xoopstpl
  - themes
  - rendering
aliases:
  - Template System
  - XoopsTpl
  - Smarty Integration
  - Theme System
---

# XOOPS Template System

The XOOPS Template System is built on the powerful Smarty template engine, providing a flexible and extensible way to separate presentation logic from business logic. It manages themes, template rendering, variable assignment, and dynamic content generation.

## Template Architecture

```mermaid
graph TD
    A[XoopsTpl] -->|extends| B[Smarty]
    A -->|manages| C[Themes]
    A -->|manages| D[Template Variables]
    A -->|handles| E[Block Rendering]

    C -->|contains| F[Templates]
    C -->|contains| G[CSS/JS]
    C -->|contains| H[Images]

    I[Theme Manager] -->|loads| C
    I -->|applies| J[Active Theme]
    I -->|configures| K[Template Paths]

    L[Block System] -->|uses| A
    M[Module Templates] -->|uses| A
    N[Admin Templates] -->|uses| A
```

## XoopsTpl Class

The main template engine class that extends Smarty.

### Class Overview

```php
namespace Xoops\Core;

class XoopsTpl extends Smarty
{
    protected array $vars = [];
    protected string $currentTheme = '';
    protected array $blocks = [];
    protected bool $isAdmin = false;
}
```

### Extending Smarty

```php
use Xoops\Core\XoopsTpl;

class XoopsTpl extends Smarty
{
    private static ?XoopsTpl $instance = null;

    private function __construct()
    {
        parent::__construct();
        $this->configureDirectories();
        $this->registerPlugins();
    }

    public static function getInstance(): XoopsTpl
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Core Methods

#### getInstance

Gets the singleton template instance.

```php
public static function getInstance(): XoopsTpl
```

**Returns:** `XoopsTpl` - Singleton instance

**Example:**
```php
$xoopsTpl = XoopsTpl::getInstance();
```

#### assign

Assigns a variable to the template.

```php
public function assign(
    string|array $tplVar,
    mixed $value = null
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tplVar` | string\|array | Variable name or associative array |
| `$value` | mixed | Variable value |

**Example:**
```php
$xoopsTpl->assign('page_title', 'Welcome');
$xoopsTpl->assign('user_name', 'John Doe');

// Multiple assignments
$xoopsTpl->assign([
    'items' => $items,
    'total_count' => count($items),
    'show_pagination' => true
]);
```

#### appendAssign

Appends values to template array variables.

```php
public function appendAssign(
    string $tplVar,
    mixed $value
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tplVar` | string | Variable name |
| `$value` | mixed | Value to append |

**Example:**
```php
$xoopsTpl->assign('breadcrumbs', ['Home']);
$xoopsTpl->appendAssign('breadcrumbs', 'Blog');
$xoopsTpl->appendAssign('breadcrumbs', 'Posts');
// breadcrumbs = ['Home', 'Blog', 'Posts']
```

#### getAssignedVars

Gets all assigned template variables.

```php
public function getAssignedVars(): array
```

**Returns:** `array` - Assigned variables

**Example:**
```php
$vars = $xoopsTpl->getAssignedVars();
foreach ($vars as $name => $value) {
    echo "$name = " . var_export($value, true) . "\n";
}
```

#### display

Renders a template and outputs to browser.

```php
public function display(
    string $resource,
    string|array $cache_id = null,
    string $compile_id = null,
    object $parent = null
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$resource` | string | Template file path |
| `$cache_id` | string\|array | Cache identifier |
| `$compile_id` | string | Compile identifier |
| `$parent` | object | Parent template object |

**Example:**
```php
$xoopsTpl->assign('page_title', 'Home');
$xoopsTpl->display('user:index.tpl');

// With absolute path
$xoopsTpl->display(XOOPS_ROOT_PATH . '/templates/user/index.tpl');
```

#### fetch

Renders a template and returns as string.

```php
public function fetch(
    string $resource,
    string|array $cache_id = null,
    string $compile_id = null,
    object $parent = null
): string
```

**Returns:** `string` - Rendered template content

**Example:**
```php
$xoopsTpl->assign('message', 'Hello World');
$html = $xoopsTpl->fetch('user:message.tpl');
echo $html;

// Use for email templates
$emailContent = $xoopsTpl->fetch('mail:notification.tpl');
mail($to, $subject, $emailContent);
```

#### loadTheme

Loads a specific theme.

```php
public function loadTheme(string $themeName): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$themeName` | string | Theme directory name |

**Returns:** `bool` - True on success

**Example:**
```php
if ($xoopsTpl->loadTheme('bluemoon')) {
    echo "Theme loaded successfully";
}
```

#### getCurrentTheme

Gets the name of the currently active theme.

```php
public function getCurrentTheme(): string
```

**Returns:** `string` - Theme name

**Example:**
```php
$currentTheme = $xoopsTpl->getCurrentTheme();
echo "Active theme: $currentTheme";
```

#### setOutputFilter

Adds an output filter to process template output.

```php
public function setOutputFilter(string $function): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$function` | string | Filter function name |

**Example:**
```php
// Remove whitespace from output
$xoopsTpl->setOutputFilter('trim');

// Custom filter
function my_output_filter($output) {
    // Minify HTML
    $output = preg_replace('/\s+/', ' ', $output);
    return trim($output);
}
$xoopsTpl->setOutputFilter('my_output_filter');
```

#### registerPlugin

Registers a custom Smarty plugin.

```php
public function registerPlugin(
    string $type,
    string $name,
    callable $callback
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | string | Plugin type (modifier, block, function) |
| `$name` | string | Plugin name |
| `$callback` | callable | Callback function |

**Example:**
```php
// Register custom modifier
$xoopsTpl->registerPlugin('modifier', 'markdown', function($text) {
    return markdown_parse($text);
});

// Use in template: {$content|markdown}

// Register custom block tag
$xoopsTpl->registerPlugin('block', 'permission', function($params, $content, $smarty, &$repeat) {
    if ($repeat) return;

    // Check permission
    if (has_permission($params['name'])) {
        return $content;
    }
    return '';
});

// Use in template: {permission name="admin"}...{/permission}
```

## Theme System

### Theme Structure

Standard XOOPS theme directory structure:

```
bluemoon/
├── style.css              # Main stylesheet
├── admin.css              # Admin stylesheet
├── theme.html             # Main page template
├── admin.html             # Admin page template
├── blocks/                # Block templates
│   ├── block_left.tpl
│   └── block_right.tpl
├── modules/               # Module templates
│   ├── publisher/
│   │   ├── index.tpl
│   │   └── item.tpl
│   └── news/
│       └── index.tpl
├── images/                # Theme images
│   ├── logo.png
│   └── banner.png
├── js/                    # Theme JavaScript
│   └── script.js
└── readme.txt             # Theme documentation
```

### Theme Manager Class

```php
namespace Xoops\Core\Theme;

class ThemeManager
{
    protected array $themes = [];
    protected string $activeTheme = '';
    protected string $themeDirectory = '';

    public function getActiveTheme(): string {}
    public function setActiveTheme(string $theme): bool {}
    public function getThemeList(): array {}
    public function themeExists(string $name): bool {}
}
```

## Template Variables

### Standard Global Variables

XOOPS automatically assigns several global template variables:

| Variable | Type | Description |
|----------|------|-------------|
| `$xoops_url` | string | XOOPS installation URL |
| `$xoops_user` | XoopsUser\|null | Current user object |
| `$xoops_uname` | string | Current username |
| `$xoops_isadmin` | bool | User is admin |
| `$xoops_banner` | string | Banner HTML |
| `$xoops_notification` | string | Notification markup |
| `$xoops_version` | string | XOOPS version |

### Block-Specific Variables

When rendering blocks:

| Variable | Type | Description |
|----------|------|-------------|
| `$block` | array | Block information |
| `$block.title` | string | Block title |
| `$block.content` | string | Block content |
| `$block.id` | int | Block ID |
| `$block.module` | string | Module name |

### Module Template Variables

Modules typically assign:

| Variable | Type | Description |
|----------|------|-------------|
| `$module_name` | string | Module display name |
| `$module_dir` | string | Module directory |
| `$xoops_module_header` | string | Module CSS/JS |

## Smarty Configuration

### Common Smarty Modifiers

| Modifier | Description | Example |
|----------|-------------|---------|
| `capitalize` | Capitalize first letter | `{$title\|capitalize}` |
| `count_characters` | Character count | `{$text\|count_characters}` |
| `date_format` | Format timestamp | `{$timestamp\|date_format:'%Y-%m-%d'}` |
| `escape` | Escape special chars | `{$html\|escape:'html'}` |
| `nl2br` | Convert newlines to `<br>` | `{$text\|nl2br}` |
| `strip_tags` | Remove HTML tags | `{$content\|strip_tags}` |
| `truncate` | Limit string length | `{$text\|truncate:100}` |
| `upper` | Convert to uppercase | `{$name\|upper}` |
| `lower` | Convert to lowercase | `{$name\|lower}` |

### Control Structures

```smarty
{* If statement *}
{if $user->isAdmin()}
    <p>Admin content</p>
{else}
    <p>User content</p>
{/if}

{* For loop *}
{foreach $items as $item}
    <div class="item">{$item.title}</div>
{/foreach}

{* For loop with counter *}
{foreach $items as $item name=item_loop}
    {$smarty.foreach.item_loop.iteration}: {$item.title}
{/foreach}

{* While loop *}
{while $condition}
    <!-- content -->
{/while}

{* Switch statement *}
{switch $status}
    {case 'draft'}<span class="draft">Draft</span>{break}
    {case 'published'}<span class="published">Published</span>{break}
    {default}<span class="unknown">Unknown</span>
{/switch}
```

## Complete Template Example

### PHP Code

```php
<?php
/**
 * Module Article List Page
 */

include __DIR__ . '/include/common.inc.php';

$xoopsTpl = XoopsTpl::getInstance();

// Check if module is active
$module = xoops_getModuleByDirname('articles');
if (!$module) {
    redirect_header(XOOPS_URL, 3, 'Module not found');
}

// Get item handler
$itemHandler = xoops_getModuleHandler('item', 'articles');

// Get pagination parameters
$page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = $module->getConfig('items_per_page') ?: 10;
$offset = ($page - 1) * $perPage;

// Build criteria
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('status', 1));
$criteria->setSort('published', 'DESC');
$criteria->setLimit($perPage);
$criteria->setStart($offset);

// Fetch items
$items = $itemHandler->getObjects($criteria);
$total = $itemHandler->getCount(new Criteria('status', 1));

// Calculate pagination
$pages = ceil($total / $perPage);

// Assign template variables
$xoopsTpl->assign([
    'module_name' => $module->getName(),
    'items' => $items,
    'total_items' => $total,
    'current_page' => $page,
    'total_pages' => $pages,
    'items_per_page' => $perPage,
    'show_pagination' => $pages > 1
]);

// Add breadcrumbs
$xoopsTpl->assign('xoops_breadcrumbs', [
    ['url' => XOOPS_URL, 'title' => 'Home'],
    ['url' => $module->getUrl(), 'title' => $module->getName()],
    ['title' => 'Articles']
]);

// Display template
$xoopsTpl->display($module->getPath() . '/templates/user/list.tpl');
```

### Template File (list.tpl)

```smarty
<div id="articles-list">
    <h1>{$module_name|escape}</h1>

    {if $items}
        <div class="articles-container">
            {foreach $items as $item}
                <article class="article-item">
                    <header>
                        <h2>
                            <a href="{$item.url|escape}">
                                {$item.title|escape}
                            </a>
                        </h2>
                        <div class="meta">
                            <span class="author">By {$item.author|escape}</span>
                            <span class="date">
                                {$item.published|date_format:'%B %d, %Y'}
                            </span>
                        </div>
                    </header>

                    <div class="content">
                        <p>{$item.summary|truncate:150}</p>
                    </div>

                    <footer>
                        <a href="{$item.url|escape}" class="read-more">
                            Read More »
                        </a>
                    </footer>
                </article>
            {/foreach}
        </div>

        {* Pagination *}
        {if $show_pagination}
            <nav class="pagination">
                {if $current_page > 1}
                    <a href="?page=1" class="first">« First</a>
                    <a href="?page={$current_page - 1}" class="prev">‹ Previous</a>
                {/if}

                {for $i=1 to $total_pages}
                    {if $i == $current_page}
                        <span class="current">{$i}</span>
                    {else}
                        <a href="?page={$i}">{$i}</a>
                    {/if}
                {/for}

                {if $current_page < $total_pages}
                    <a href="?page={$current_page + 1}" class="next">Next ›</a>
                    <a href="?page={$total_pages}" class="last">Last »</a>
                {/if}
            </nav>
        {/if}
    {else}
        <p class="no-items">No articles found.</p>
    {/if}
</div>
```

## Custom Smarty Functions

### Creating a Custom Block Function

```php
<?php
/**
 * Custom Smarty block function for permission checking
 */

function smarty_block_permission($params, $content, $smarty, &$repeat)
{
    if ($repeat) return;

    if (!isset($params['name'])) {
        return 'Permission name required';
    }

    $permName = $params['name'];
    $user = $GLOBALS['xoopsUser'];

    // Check if user has permission
    if ($user && $user->isAdmin()) {
        return $content;
    }

    if ($user && check_user_permission($user->uid(), $permName)) {
        return $content;
    }

    return '';
}
```

Register and use:

```php
$xoopsTpl->registerPlugin('block', 'permission', 'smarty_block_permission');
```

Template:

```smarty
{permission name="edit_articles"}
    <button>Edit Article</button>
{/permission}
```

## Best Practices

1. **Escape User Content** - Always use `|escape` for user-generated content
2. **Use Template Paths** - Reference templates relative to theme
3. **Separate Logic from Presentation** - Keep complex logic in PHP
4. **Cache Templates** - Enable template caching in production
5. **Use Modifiers Correctly** - Apply appropriate filters for context
6. **Organize Blocks** - Place block templates in dedicated directory
7. **Document Variables** - Document all template variables in PHP

## Related Documentation

- [[../Module/Module-System]] - Module system and hooks
- [[../Kernel/Kernel-Classes]] - Kernel and configuration
- [[../Core/XoopsObject]] - Base object class

---

*See also: [Smarty Documentation](https://www.smarty.net/docs) | [XOOPS Template API](https://github.com/XOOPS/XoopsCore25/tree/master/htdocs/class)*
