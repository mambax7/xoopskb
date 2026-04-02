---
title: XoopsObject Class
description: Base class for all data objects in the XOOPS system providing property management, validation, and serialization
created: 2024-01-28
updated: 2024-01-28
version: 2.5.11
tags:
  - api
  - core
  - xoopsobject
  - base-class
  - data-model
aliases:
  - XoopsObject
  - Base Object
---

# XoopsObject Class

The `XoopsObject` class is the fundamental base class for all data objects in the XOOPS system. It provides a standardized interface for managing object properties, validation, dirty tracking, and serialization.

## Class Overview

```php
namespace Xoops\Core;

class XoopsObject
{
    protected array $vars = [];
    protected array $cleanVars = [];
    protected bool $isNew = true;
    protected array $errors = [];
}
```

## Class Hierarchy

```
XoopsObject
├── XoopsUser
├── XoopsGroup
├── XoopsModule
├── XoopsBlock
├── XoopsComment
├── XoopsNotification
├── XoopsConfig
└── [Custom Module Objects]
```

## Properties

| Property | Type | Visibility | Description |
|----------|------|------------|-------------|
| `$vars` | array | protected | Stores variable definitions and values |
| `$cleanVars` | array | protected | Stores sanitized values for database operations |
| `$isNew` | bool | protected | Indicates if object is new (not yet in database) |
| `$errors` | array | protected | Stores validation and error messages |

## Constructor

```php
public function __construct()
```

Creates a new XoopsObject instance. The object is marked as new by default.

**Example:**
```php
$object = new XoopsObject();
// Object is new and has no defined variables
```

## Core Methods

### initVar

Initializes a variable definition for the object.

```php
public function initVar(
    string $key,
    int $dataType,
    mixed $value = null,
    bool $required = false,
    int $maxlength = null,
    string $options = ''
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | string | Variable name |
| `$dataType` | int | Data type constant (see Data Types) |
| `$value` | mixed | Default value |
| `$required` | bool | Whether field is required |
| `$maxlength` | int | Maximum length for string types |
| `$options` | string | Additional options |

**Data Types:**

| Constant | Value | Description |
|----------|-------|-------------|
| `XOBJ_DTYPE_TXTBOX` | 1 | Text box input |
| `XOBJ_DTYPE_TXTAREA` | 2 | Textarea content |
| `XOBJ_DTYPE_INT` | 3 | Integer value |
| `XOBJ_DTYPE_URL` | 4 | URL string |
| `XOBJ_DTYPE_EMAIL` | 5 | Email address |
| `XOBJ_DTYPE_ARRAY` | 6 | Serialized array |
| `XOBJ_DTYPE_OTHER` | 7 | Custom type |
| `XOBJ_DTYPE_SOURCE` | 8 | Source code |
| `XOBJ_DTYPE_STIME` | 9 | Short time format |
| `XOBJ_DTYPE_MTIME` | 10 | Medium time format |
| `XOBJ_DTYPE_LTIME` | 11 | Long time format |
| `XOBJ_DTYPE_FLOAT` | 12 | Floating point |
| `XOBJ_DTYPE_DECIMAL` | 13 | Decimal number |
| `XOBJ_DTYPE_ENUM` | 14 | Enumeration |

**Example:**
```php
class MyObject extends XoopsObject
{
    public function __construct()
    {
        parent::__construct();
        $this->initVar('id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('content', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('email', XOBJ_DTYPE_EMAIL, '', true, 100);
        $this->initVar('created', XOBJ_DTYPE_INT, time(), false);
        $this->initVar('status', XOBJ_DTYPE_INT, 1, true);
    }
}
```

---

### setVar

Sets the value of a variable.

```php
public function setVar(
    string $key,
    mixed $value,
    bool $notGpc = false
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | string | Variable name |
| `$value` | mixed | Value to set |
| `$notGpc` | bool | If true, value is not from GET/POST/COOKIE |

**Returns:** `bool` - True if successful, false otherwise

**Example:**
```php
$object = new MyObject();
$object->setVar('title', 'Hello World');
$object->setVar('content', '<p>Content here</p>', true); // Not from user input
$object->setVar('status', 1);
```

---

### getVar

Retrieves the value of a variable with optional formatting.

```php
public function getVar(
    string $key,
    string $format = 's'
): mixed
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | string | Variable name |
| `$format` | string | Output format |

**Format Options:**

| Format | Description |
|--------|-------------|
| `'s'` | Show - HTML entities escaped for display |
| `'e'` | Edit - For form input values |
| `'p'` | Preview - Similar to show |
| `'f'` | Form data - Raw for form processing |
| `'n'` | None - Raw value, no formatting |

**Returns:** `mixed` - The formatted value

**Example:**
```php
$object = new MyObject();
$object->setVar('title', 'Hello <World>');

echo $object->getVar('title', 's'); // "Hello &lt;World&gt;"
echo $object->getVar('title', 'e'); // "Hello &lt;World&gt;" (for input value)
echo $object->getVar('title', 'n'); // "Hello <World>" (raw)

// For array data types
$object->setVar('options', ['a', 'b', 'c']);
$options = $object->getVar('options', 'n'); // Returns array
```

---

### setVars

Sets multiple variables at once from an array.

```php
public function setVars(
    array $values,
    bool $notGpc = false
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$values` | array | Associative array of key => value pairs |
| `$notGpc` | bool | If true, values are not from GET/POST/COOKIE |

**Example:**
```php
$object = new MyObject();
$object->setVars([
    'title' => 'My Title',
    'content' => 'My content',
    'status' => 1
]);

// From database (not user input)
$object->setVars($row, true);
```

---

### getValues

Retrieves all variable values.

```php
public function getValues(
    array $keys = null,
    string $format = 's',
    int $maxDepth = 1
): array
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$keys` | array | Specific keys to retrieve (null for all) |
| `$format` | string | Output format |
| `$maxDepth` | int | Maximum depth for nested objects |

**Returns:** `array` - Associative array of values

**Example:**
```php
$object = new MyObject();

// Get all values
$allValues = $object->getValues();

// Get specific values
$subset = $object->getValues(['title', 'status']);

// Get raw values for database
$rawValues = $object->getValues(null, 'n');
```

---

### assignVar

Assigns a value directly without validation (use with caution).

```php
public function assignVar(
    string $key,
    mixed $value
): void
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | string | Variable name |
| `$value` | mixed | Value to assign |

**Example:**
```php
// Direct assignment from trusted source (e.g., database)
$object->assignVar('id', $row['id']);
$object->assignVar('created', $row['created']);
```

---

### cleanVars

Sanitizes all variables for database operations.

```php
public function cleanVars(): bool
```

**Returns:** `bool` - True if all variables are valid

**Example:**
```php
$object = new MyObject();
$object->setVar('title', 'Test');
$object->setVar('email', 'user@example.com');

if ($object->cleanVars()) {
    // Variables are sanitized and ready for database
    $cleanData = $object->cleanVars;
} else {
    // Validation errors occurred
    $errors = $object->getErrors();
}
```

---

### isNew

Checks or sets whether the object is new.

```php
public function isNew(): bool
public function setNew(): void
public function unsetNew(): void
```

**Example:**
```php
$object = new MyObject();
echo $object->isNew(); // true

$object->unsetNew();
echo $object->isNew(); // false

$object->setNew();
echo $object->isNew(); // true
```

---

## Error Handling Methods

### setErrors

Adds an error message.

```php
public function setErrors(string|array $error): void
```

**Example:**
```php
$object->setErrors('Title is required');
$object->setErrors(['Field 1 error', 'Field 2 error']);
```

---

### getErrors

Retrieves all error messages.

```php
public function getErrors(): array
```

**Example:**
```php
$errors = $object->getErrors();
foreach ($errors as $error) {
    echo $error . "\n";
}
```

---

### getHtmlErrors

Returns errors formatted as HTML.

```php
public function getHtmlErrors(): string
```

**Example:**
```php
if (!$object->cleanVars()) {
    echo '<div class="error">' . $object->getHtmlErrors() . '</div>';
}
```

---

## Utility Methods

### toArray

Converts the object to an array.

```php
public function toArray(): array
```

**Example:**
```php
$object = new MyObject();
$object->setVar('title', 'Test');
$data = $object->toArray();
// ['title' => 'Test', ...]
```

---

### getVars

Returns the variable definitions.

```php
public function getVars(): array
```

**Example:**
```php
$vars = $object->getVars();
foreach ($vars as $key => $definition) {
    echo "Field: $key, Type: {$definition['data_type']}\n";
}
```

---

## Complete Usage Example

```php
<?php
/**
 * Custom Article Object
 */
class Article extends XoopsObject
{
    /**
     * Constructor - Initialize all variables
     */
    public function __construct()
    {
        parent::__construct();

        // Primary key
        $this->initVar('article_id', XOBJ_DTYPE_INT, null, false);

        // Required fields
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('author_id', XOBJ_DTYPE_INT, 0, true);

        // Optional fields
        $this->initVar('summary', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('content', XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('category_id', XOBJ_DTYPE_INT, 0, false);

        // Timestamps
        $this->initVar('created', XOBJ_DTYPE_INT, time(), false);
        $this->initVar('updated', XOBJ_DTYPE_INT, time(), false);

        // Status flags
        $this->initVar('published', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('views', XOBJ_DTYPE_INT, 0, false);

        // Metadata as array
        $this->initVar('meta', XOBJ_DTYPE_ARRAY, [], false);
    }

    /**
     * Get formatted creation date
     */
    public function getCreatedDate(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, $this->getVar('created', 'n'));
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->getVar('published', 'n') == 1;
    }

    /**
     * Increment view counter
     */
    public function incrementViews(): void
    {
        $views = $this->getVar('views', 'n');
        $this->setVar('views', $views + 1);
    }

    /**
     * Custom validation
     */
    public function validate(): bool
    {
        $this->errors = [];

        // Title validation
        $title = trim($this->getVar('title', 'n'));
        if (empty($title)) {
            $this->setErrors('Title is required');
        } elseif (strlen($title) < 5) {
            $this->setErrors('Title must be at least 5 characters');
        }

        // Author validation
        if ($this->getVar('author_id', 'n') <= 0) {
            $this->setErrors('Author is required');
        }

        return empty($this->errors);
    }
}

// Usage
$article = new Article();
$article->setVar('title', 'My First Article');
$article->setVar('author_id', 1);
$article->setVar('content', '<p>Article content here...</p>', true);
$article->setVar('meta', [
    'keywords' => ['xoops', 'cms', 'php'],
    'description' => 'An example article'
]);

if ($article->validate() && $article->cleanVars()) {
    // Save to database via handler
    $handler = xoops_getModuleHandler('article', 'mymodule');
    $handler->insert($article);

    echo "Article saved with ID: " . $article->getVar('article_id');
} else {
    echo "Errors: " . $article->getHtmlErrors();
}
```

## Best Practices

1. **Always Initialize Variables**: Define all variables in the constructor using `initVar()`

2. **Use Appropriate Data Types**: Choose the correct `XOBJ_DTYPE_*` constant for validation

3. **Handle User Input Carefully**: Use `setVar()` with `$notGpc = false` for user input

4. **Validate Before Saving**: Always call `cleanVars()` before database operations

5. **Use Format Parameters**: Use the appropriate format in `getVar()` for the context

6. **Extend for Custom Logic**: Add domain-specific methods in subclasses

## Related Documentation

- [[XoopsObjectHandler]] - Handler pattern for object persistence
- [[../Database/Criteria]] - Query building with Criteria
- [[../Database/XoopsDatabase]] - Database operations

---

*See also: [XOOPS Source Code](https://github.com/XOOPS/XoopsCore25/blob/master/htdocs/class/xoopsobject.php)*
