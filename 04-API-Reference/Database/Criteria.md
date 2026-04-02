---
title: Criteria and CriteriaCompo Classes
description: Query building and advanced filtering using Criteria classes
tags:
  - api
  - database
  - criteria
  - query-builder
  - filtering
  - where-clause
created: 2026-01-31
updated: 2026-01-31
version: 2026.01
---

# Criteria and CriteriaCompo Classes

The `Criteria` and `CriteriaCompo` classes provide a fluent, object-oriented interface for building complex database queries. These classes abstract SQL WHERE clauses, allowing developers to construct dynamic queries safely and readably.

## Class Overview

### Criteria Class

The `Criteria` class represents a single condition in a WHERE clause:

```php
namespace Xoops\Database;

class Criteria
{
    protected $column;
    protected $operator;
    protected $value;
    protected $function;

    public function __construct(
        string $column,
        mixed $value = null,
        string $operator = '=',
        string $function = ''
    ) {}

    public function render(string $prefix = ''): string {}
}
```

## Basic Usage

### Simple Criteria

```php
use Xoops\Database\Criteria;
use Xoops\Database\CriteriaCompo;

// Single condition
$criteria = new Criteria('status', 'active');
// Renders: `status` = 'active'
```

### Different Operators

```php
// Equality (default)
$criteria = new Criteria('status', 'active', '=');

// Not equal
$criteria = new Criteria('status', 'active', '<>');

// Greater than
$criteria = new Criteria('age', 18, '>');

// Less than or equal
$criteria = new Criteria('age', 65, '<=');

// LIKE (for pattern matching)
$criteria = new Criteria('email', '%@example.com', 'LIKE');

// IN (for multiple values)
$criteria = new Criteria('status', ['active', 'pending', 'review'], 'IN');
```

## Building Complex Queries

### AND Logic (Default)

```php
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('status', 'active'));
$criteria->add(new Criteria('age', 18, '>='));
$criteria->add(new Criteria('verified', 1));
// Renders: `status` = 'active' AND `age` >= 18 AND `verified` = 1
```

### OR Logic

```php
$criteria = new CriteriaCompo('OR');
$criteria->add(new Criteria('role', 'admin'));
$criteria->add(new Criteria('role', 'moderator'));
$criteria->add(new Criteria('role', 'editor'));
```

## Integration with Repository Pattern

### Repository Example

```php
namespace MyModule\Repository;

use Xoops\Database\XoopsDatabase;
use Xoops\Database\Criteria;
use Xoops\Database\CriteriaCompo;

class UserRepository
{
    private $db;
    private $table = 'users';

    public function __construct(XoopsDatabase $db)
    {
        $this->db = $db;
    }

    public function findByCriteria(CriteriaCompo $criteria): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($criteria->count() > 0) {
            $sql .= " WHERE " . $criteria->render();
        }

        $result = $this->db->query($sql);
        $users = [];

        while ($row = $this->db->fetchArray($result)) {
            $users[] = new User($row);
        }

        return $users;
    }
}
```

## Safety and Security

### Automatic Escaping

The `Criteria` class automatically escapes values to prevent SQL injection:

```php
// Safe - value is automatically escaped
$userInput = "'; DROP TABLE users; --";
$criteria = new Criteria('username', $userInput);
// Safely renders: `username` = '\''; DROP TABLE users; --'
```

## API Reference

### Criteria Methods

| Method | Description | Return |
|--------|-------------|--------|
| `__construct()` | Initialize a criteria condition | void |
| `render($prefix = '')` | Render to SQL WHERE clause segment | string |
| `getColumn()` | Get the column name | string |
| `getValue()` | Get the comparison value | mixed |
| `getOperator()` | Get the comparison operator | string |

### CriteriaCompo Methods

| Method | Description | Return |
|--------|-------------|--------|
| `__construct($logic = 'AND')` | Initialize composite criteria | void |
| `add($criteria, $logic = null)` | Add criteria or nested composite | void |
| `render($prefix = '')` | Render to complete WHERE clause | string |
| `count()` | Get number of criteria | int |
| `clear()` | Remove all criteria | void |

## Related Documentation

- [[XoopsDatabase]] - Database class reference
- [[../../03-Module-Development/Patterns/Repository-Pattern]] - Repository pattern in XOOPS
- [[../../03-Module-Development/Patterns/Service-Layer-Pattern]] - Service layer pattern

## Version Information

- **Introduced:** XOOPS 2.5.0
- **Last Updated:** XOOPS 4.0
- **Compatibility:** PHP 8.2+
