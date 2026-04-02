---
title: XMF Request
description: Secure HTTP request handling and input validation with the Xmf\Request class
sidebar_position: 2
---

# XMF Request

The `Xmf\Request` class provides controlled access to HTTP request variables with built-in sanitization and type conversion. It protects against potentially harmful injections by default while conforming input to specified types.

## Overview

Request handling is one of the most security-critical aspects of web development. The XMF Request class:

- Automatically sanitizes input to prevent XSS attacks
- Provides type-safe accessors for common data types
- Supports multiple request sources (GET, POST, COOKIE, etc.)
- Offers consistent default value handling

## Basic Usage

```php
use Xmf\Request;

// Get string input
$name = Request::getString('name', '');

// Get integer input
$id = Request::getInt('id', 0);

// Get from specific source
$postData = Request::getString('data', '', 'POST');
```

## Request Methods

### getMethod()

Returns the HTTP request method for the current request.

```php
$method = Request::getMethod();
// Returns: 'GET', 'HEAD', 'POST', or 'PUT'
```

### getVar($name, $default, $hash, $type, $mask)

The core method that most other `get*()` methods invoke. Fetches and returns a named variable from request data.

**Parameters:**
- `$name` - Variable name to fetch
- `$default` - Default value if variable doesn't exist
- `$hash` - Source hash: GET, POST, FILES, COOKIE, ENV, SERVER, METHOD, or REQUEST (default)
- `$type` - Data type for cleaning (see FilterInput types below)
- `$mask` - Bitmask for cleaning options

**Mask Values:**

| Mask Constant | Effect |
|---------------|--------|
| `MASK_NO_TRIM` | Do not trim leading/trailing whitespace |
| `MASK_ALLOW_RAW` | Skip cleaning, allow raw input |
| `MASK_ALLOW_HTML` | Allow a limited "safe" set of HTML markup |

```php
// Get raw input without cleaning
$rawHtml = Request::getVar('content', '', 'POST', 'STRING', Request::MASK_ALLOW_RAW);

// Allow safe HTML
$content = Request::getVar('body', '', 'POST', 'STRING', Request::MASK_ALLOW_HTML);
```

## Type-Specific Methods

### getInt($name, $default, $hash)

Returns an integer value. Only digits are allowed.

```php
$id = Request::getInt('id', 0);
$page = Request::getInt('page', 1, 'GET');
```

### getFloat($name, $default, $hash)

Returns a float value. Only digits and periods allowed.

```php
$price = Request::getFloat('price', 0.0);
$rate = Request::getFloat('rate', 1.0, 'POST');
```

### getBool($name, $default, $hash)

Returns a boolean value.

```php
$enabled = Request::getBool('enabled', false);
$subscribe = Request::getBool('subscribe', false, 'POST');
```

### getWord($name, $default, $hash)

Returns a string with only letters and underscores `[A-Za-z_]`.

```php
$action = Request::getWord('action', 'view');
```

### getCmd($name, $default, $hash)

Returns a command string with only `[A-Za-z0-9.-_]`, forced to lowercase.

```php
$op = Request::getCmd('op', 'list');
// Input "View_Item" becomes "view_item"
```

### getString($name, $default, $hash, $mask)

Returns a cleaned string with bad HTML code removed (unless overridden by mask).

```php
$title = Request::getString('title', '');
$description = Request::getString('description', '', 'POST');

// Allow some HTML
$content = Request::getString('content', '', 'POST', Request::MASK_ALLOW_HTML);
```

### getArray($name, $default, $hash)

Returns an array, recursively processed to remove XSS and bad code.

```php
$items = Request::getArray('items', [], 'POST');
$selectedIds = Request::getArray('selected', []);
```

### getText($name, $default, $hash)

Returns raw text without cleaning. Use with caution.

```php
$rawContent = Request::getText('raw_content', '');
```

### getUrl($name, $default, $hash)

Returns a validated web URL (relative, http, or https schemes only).

```php
$website = Request::getUrl('website', '');
$returnUrl = Request::getUrl('return', 'index.php');
```

### getPath($name, $default, $hash)

Returns a validated filesystem or web path.

```php
$filePath = Request::getPath('file', '');
```

### getEmail($name, $default, $hash)

Returns a validated email address or the default.

```php
$email = Request::getEmail('email', '');
$contactEmail = Request::getEmail('contact', 'default@example.com');
```

### getIP($name, $default, $hash)

Returns a validated IPv4 or IPv6 address.

```php
$userIp = Request::getIP('client_ip', '');
```

### getHeader($headerName, $default)

Returns an HTTP request header value.

```php
$contentType = Request::getHeader('Content-Type', '');
$userAgent = Request::getHeader('User-Agent', '');
$authHeader = Request::getHeader('Authorization', '');
```

## Utility Methods

### hasVar($name, $hash)

Check if a variable exists in the specified hash.

```php
if (Request::hasVar('submit', 'POST')) {
    // Form was submitted
}

if (Request::hasVar('id', 'GET')) {
    // ID parameter exists
}
```

### setVar($name, $value, $hash, $overwrite)

Set a variable in the specified hash. Returns the previous value or null.

```php
// Set a value
$oldValue = Request::setVar('processed', true, 'POST');

// Only set if not already exists
Request::setVar('default_op', 'list', 'GET', false);
```

### get($hash, $mask)

Returns a cleaned copy of an entire hash array.

```php
// Get all POST data cleaned
$postData = Request::get('POST');

// Get all GET data
$getData = Request::get('GET');

// Get REQUEST data with no trimming
$requestData = Request::get('REQUEST', Request::MASK_NO_TRIM);
```

### set($array, $hash, $overwrite)

Sets multiple variables from an array.

```php
$defaults = [
    'page' => 1,
    'limit' => 10,
    'sort' => 'date'
];
Request::set($defaults, 'GET', false); // Don't overwrite existing
```

## FilterInput Integration

The Request class uses `Xmf\FilterInput` for cleaning. Available filter types:

| Type | Description |
|------|-------------|
| ALPHANUM / ALNUM | Alphanumeric only |
| ARRAY | Recursively clean each element |
| BASE64 | Base64 encoded string |
| BOOLEAN / BOOL | True or false |
| CMD | Command - A-Z, 0-9, underscore, dash, period (lowercase) |
| EMAIL | Valid email address |
| FLOAT / DOUBLE | Floating point number |
| INTEGER / INT | Integer value |
| IP | Valid IP address |
| PATH | Filesystem or web path |
| STRING | General string (default) |
| USERNAME | Username format |
| WEBURL | Web URL |
| WORD | Letters A-Z and underscore only |

## Practical Examples

### Form Processing

```php
use Xmf\Request;

if ('POST' === Request::getMethod()) {
    // Validate form submission
    $title = Request::getString('title', '');
    $content = Request::getString('content', '', 'POST', Request::MASK_ALLOW_HTML);
    $categoryId = Request::getInt('category_id', 0);
    $tags = Request::getArray('tags', []);
    $published = Request::getBool('published', false);

    if (empty($title)) {
        $errors[] = 'Title is required';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Please select a category';
    }
}
```

### AJAX Handler

```php
use Xmf\Request;

// Verify AJAX request
$isAjax = (Request::getHeader('X-Requested-With', '') === 'XMLHttpRequest');

if ($isAjax) {
    $action = Request::getCmd('action', '');
    $itemId = Request::getInt('item_id', 0);

    switch ($action) {
        case 'delete':
            // Handle delete
            break;
        case 'update':
            $data = Request::getArray('data', []);
            // Handle update
            break;
    }
}
```

### Pagination

```php
use Xmf\Request;

$page = Request::getInt('page', 1);
$limit = Request::getInt('limit', 20);
$sort = Request::getCmd('sort', 'date');
$order = Request::getWord('order', 'DESC');

// Validate ranges
$page = max(1, $page);
$limit = min(100, max(10, $limit));
$order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';

$offset = ($page - 1) * $limit;
```

### Search Form

```php
use Xmf\Request;

$query = Request::getString('q', '');
$category = Request::getInt('cat', 0);
$dateFrom = Request::getString('from', '');
$dateTo = Request::getString('to', '');

// Build search criteria
$criteria = new CriteriaCompo();

if (!empty($query)) {
    $criteria->add(new Criteria('title', '%' . $query . '%', 'LIKE'));
}

if ($category > 0) {
    $criteria->add(new Criteria('category_id', $category));
}
```

## Security Best Practices

1. **Always use type-specific methods** - Use `getInt()` for IDs, `getEmail()` for emails, etc.

2. **Provide sensible defaults** - Never assume input exists

3. **Validate after sanitization** - Sanitization removes bad data, validation ensures correct data

4. **Use appropriate hash** - Specify POST for form data, GET for query parameters

5. **Avoid raw input** - Only use `getText()` or `MASK_ALLOW_RAW` when absolutely necessary

```php
// Good - type-specific with default
$id = Request::getInt('id', 0);

// Bad - using getString for numeric data
$id = (int) Request::getString('id', '0');
```

## See Also

- [[Getting-Started-with-XMF]] - Basic XMF concepts
- [[XMF-Module-Helper]] - Module helper class
- [[../XMF-Framework]] - Framework overview

---

#xmf #request #security #input-validation #sanitization
