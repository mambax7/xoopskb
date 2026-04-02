---
title: XoopsDatabase Class
description: Database abstraction layer providing connection management, query execution, and result handling
created: 2024-01-28
updated: 2024-01-28
version: 2.5.11
tags:
  - api
  - database
  - mysql
  - mysqli
  - abstraction
  - query
aliases:
  - XoopsDatabase
  - Database Layer
  - DB Connection
---

# XoopsDatabase Class

The `XoopsDatabase` class provides a database abstraction layer for XOOPS, handling connection management, query execution, result processing, and error handling. It supports multiple database drivers through a driver architecture.

## Class Overview

```php
namespace Xoops\Database;

abstract class XoopsDatabase
{
    protected $conn;
    protected $prefix;
    protected $logger;

    abstract public function connect(bool $selectdb = true): bool;
    abstract public function query(string $sql, int $limit = 0, int $start = 0);
    abstract public function fetchArray($result): ?array;
    abstract public function fetchObject($result): ?object;
    abstract public function getRowsNum($result): int;
    abstract public function getAffectedRows(): int;
    abstract public function getInsertId(): int;
    abstract public function escape(string $string): string;
}
```

## Class Hierarchy

```
XoopsDatabase (Abstract Base)
├── XoopsMySQLDatabase (MySQL Extension)
│   └── XoopsMySQLDatabaseProxy (Security Proxy)
└── XoopsMySQLiDatabase (MySQLi Extension)
    └── XoopsMySQLiDatabaseProxy (Security Proxy)

XoopsDatabaseFactory
└── Creates appropriate driver instances
```

## Getting a Database Instance

### Using the Factory

```php
// Recommended: Use the factory
$db = XoopsDatabaseFactory::getDatabaseConnection();
```

### Using getInstance

```php
// Alternative: Direct singleton access
$db = XoopsDatabase::getInstance();
```

### Global Variable

```php
// Legacy: Use global variable
global $xoopsDB;
```

## Core Methods

### connect

Establishes a database connection.

```php
abstract public function connect(bool $selectdb = true): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$selectdb` | bool | Whether to select the database |

**Returns:** `bool` - True on successful connection

**Example:**
```php
$db = XoopsDatabaseFactory::getDatabaseConnection();
if ($db->connect()) {
    echo "Connected successfully";
}
```

---

### query

Executes an SQL query.

```php
abstract public function query(
    string $sql,
    int $limit = 0,
    int $start = 0
): mixed
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sql` | string | SQL query string |
| `$limit` | int | Maximum rows to return (0 = no limit) |
| `$start` | int | Starting offset |

**Returns:** `resource|bool` - Result resource or false on failure

**Example:**
```php
$db = XoopsDatabaseFactory::getDatabaseConnection();

// Simple query
$sql = "SELECT * FROM " . $db->prefix('users') . " WHERE uid > 0";
$result = $db->query($sql);

// Query with limit
$sql = "SELECT * FROM " . $db->prefix('users');
$result = $db->query($sql, 10, 0); // First 10 rows

// Query with offset
$result = $db->query($sql, 10, 20); // 10 rows starting at row 20
```

---

### queryF

Executes a query forcing the operation (bypasses security checks).

```php
public function queryF(string $sql, int $limit = 0, int $start = 0): mixed
```

**Use Cases:**
- INSERT, UPDATE, DELETE operations
- When you need to bypass read-only restrictions

**Example:**
```php
$sql = sprintf(
    "UPDATE %s SET views = views + 1 WHERE article_id = %d",
    $db->prefix('articles'),
    $articleId
);
$db->queryF($sql);
```

---

### prefix

Prepends the database table prefix.

```php
public function prefix(string $table = ''): string
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | string | Table name without prefix |

**Returns:** `string` - Table name with prefix

**Example:**
```php
$db = XoopsDatabaseFactory::getDatabaseConnection();

echo $db->prefix('users');       // "xoops_users" (if prefix is "xoops_")
echo $db->prefix('modules');     // "xoops_modules"
echo $db->prefix();              // "xoops_" (just the prefix)
```

---

### fetchArray

Fetches a result row as an associative array.

```php
abstract public function fetchArray($result): ?array
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | resource | Query result resource |

**Returns:** `array|null` - Associative array or null if no more rows

**Example:**
```php
$sql = "SELECT * FROM " . $db->prefix('users') . " WHERE level > 0";
$result = $db->query($sql);

while ($row = $db->fetchArray($result)) {
    echo "User: " . $row['uname'] . "\n";
    echo "Email: " . $row['email'] . "\n";
}
```

---

### fetchObject

Fetches a result row as an object.

```php
abstract public function fetchObject($result): ?object
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | resource | Query result resource |

**Returns:** `object|null` - Object with properties for each column

**Example:**
```php
$sql = "SELECT * FROM " . $db->prefix('users') . " WHERE uid = 1";
$result = $db->query($sql);

if ($user = $db->fetchObject($result)) {
    echo "Username: " . $user->uname;
    echo "Email: " . $user->email;
}
```

---

### fetchRow

Fetches a result row as a numeric array.

```php
abstract public function fetchRow($result): ?array
```

**Example:**
```php
$sql = "SELECT uname, email FROM " . $db->prefix('users');
$result = $db->query($sql);

while ($row = $db->fetchRow($result)) {
    echo "Username: " . $row[0] . ", Email: " . $row[1];
}
```

---

### fetchBoth

Fetches a result row as both associative and numeric array.

```php
abstract public function fetchBoth($result): ?array
```

**Example:**
```php
$result = $db->query($sql);
$row = $db->fetchBoth($result);
echo $row['uname'];  // By name
echo $row[0];        // By index
```

---

### getRowsNum

Gets the number of rows in a result set.

```php
abstract public function getRowsNum($result): int
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | resource | Query result resource |

**Returns:** `int` - Number of rows

**Example:**
```php
$sql = "SELECT * FROM " . $db->prefix('users') . " WHERE level > 0";
$result = $db->query($sql);
$count = $db->getRowsNum($result);
echo "Found $count active users";
```

---

### getAffectedRows

Gets the number of affected rows from last query.

```php
abstract public function getAffectedRows(): int
```

**Returns:** `int` - Number of affected rows

**Example:**
```php
$sql = "UPDATE " . $db->prefix('users') . " SET last_login = " . time() . " WHERE uid = 1";
$db->queryF($sql);
$affected = $db->getAffectedRows();
echo "Updated $affected rows";
```

---

### getInsertId

Gets the auto-generated ID from the last INSERT.

```php
abstract public function getInsertId(): int
```

**Returns:** `int` - Last insert ID

**Example:**
```php
$sql = sprintf(
    "INSERT INTO %s (title, content) VALUES (%s, %s)",
    $db->prefix('articles'),
    $db->quoteString($title),
    $db->quoteString($content)
);
$db->queryF($sql);
$newId = $db->getInsertId();
echo "Created article with ID: $newId";
```

---

### escape

Escapes a string for safe use in SQL queries.

```php
abstract public function escape(string $string): string
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$string` | string | String to escape |

**Returns:** `string` - Escaped string (without quotes)

**Example:**
```php
$unsafeInput = "O'Reilly";
$safe = $db->escape($unsafeInput);  // "O\'Reilly"

$sql = "SELECT * FROM " . $db->prefix('users') . " WHERE uname = '" . $safe . "'";
```

---

### quoteString

Escapes and quotes a string for SQL.

```php
public function quoteString(string $string): string
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$string` | string | String to quote |

**Returns:** `string` - Escaped and quoted string

**Example:**
```php
$name = "John O'Connor";
$quoted = $db->quoteString($name);  // "'John O\'Connor'"

$sql = "INSERT INTO users (name) VALUES (" . $quoted . ")";
```

---

### freeRecordSet

Frees memory associated with a result.

```php
abstract public function freeRecordSet($result): void
```

**Example:**
```php
$result = $db->query($sql);
// Process results...
$db->freeRecordSet($result);  // Free memory
```

---

## Error Handling

### error

Gets the last error message.

```php
abstract public function error(): string
```

**Example:**
```php
$result = $db->query($sql);
if (!$result) {
    echo "Database error: " . $db->error();
}
```

---

### errno

Gets the last error number.

```php
abstract public function errno(): int
```

**Example:**
```php
$result = $db->query($sql);
if (!$result) {
    echo "Error #" . $db->errno() . ": " . $db->error();
}
```

---

## Prepared Statements (MySQLi)

The MySQLi driver supports prepared statements for enhanced security.

### prepare

Creates a prepared statement.

```php
public function prepare(string $sql): mysqli_stmt|false
```

**Example:**
```php
$db = XoopsDatabaseFactory::getDatabaseConnection();

$sql = "SELECT * FROM " . $db->prefix('users') . " WHERE uid = ?";
$stmt = $db->prepare($sql);

$stmt->bind_param('i', $userId);
$userId = 5;
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo $row['uname'];
}
$stmt->close();
```

### Prepared Statement with Multiple Parameters

```php
$sql = "INSERT INTO " . $db->prefix('articles') . " (title, content, author_id) VALUES (?, ?, ?)";
$stmt = $db->prepare($sql);

$stmt->bind_param('ssi', $title, $content, $authorId);

$title = "My Article";
$content = "Article content here";
$authorId = 1;

if ($stmt->execute()) {
    echo "Article created with ID: " . $stmt->insert_id;
}

$stmt->close();
```

---

## Transaction Support

### beginTransaction

Starts a transaction.

```php
public function beginTransaction(): bool
```

### commit

Commits the current transaction.

```php
public function commit(): bool
```

### rollback

Rolls back the current transaction.

```php
public function rollback(): bool
```

**Example:**
```php
$db = XoopsDatabaseFactory::getDatabaseConnection();

try {
    $db->beginTransaction();

    // Multiple operations
    $sql1 = "UPDATE " . $db->prefix('accounts') . " SET balance = balance - 100 WHERE id = 1";
    $db->queryF($sql1);

    $sql2 = "UPDATE " . $db->prefix('accounts') . " SET balance = balance + 100 WHERE id = 2";
    $db->queryF($sql2);

    // Check for errors
    if ($db->errno()) {
        throw new Exception($db->error());
    }

    $db->commit();
    echo "Transaction completed";

} catch (Exception $e) {
    $db->rollback();
    echo "Transaction failed: " . $e->getMessage();
}
```

---

## Complete Usage Examples

### Basic CRUD Operations

```php
$db = XoopsDatabaseFactory::getDatabaseConnection();

// CREATE
$sql = sprintf(
    "INSERT INTO %s (title, content, created) VALUES (%s, %s, %d)",
    $db->prefix('articles'),
    $db->quoteString('New Article'),
    $db->quoteString('Article content'),
    time()
);
$db->queryF($sql);
$articleId = $db->getInsertId();

// READ
$sql = "SELECT * FROM " . $db->prefix('articles') . " WHERE id = " . (int)$articleId;
$result = $db->query($sql);
$article = $db->fetchArray($result);

// UPDATE
$sql = sprintf(
    "UPDATE %s SET title = %s, updated = %d WHERE id = %d",
    $db->prefix('articles'),
    $db->quoteString('Updated Title'),
    time(),
    $articleId
);
$db->queryF($sql);

// DELETE
$sql = "DELETE FROM " . $db->prefix('articles') . " WHERE id = " . (int)$articleId;
$db->queryF($sql);
```

### Pagination Query

```php
function getArticles(int $page = 1, int $perPage = 10): array
{
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    $start = ($page - 1) * $perPage;

    // Get total count
    $sql = "SELECT COUNT(*) as total FROM " . $db->prefix('articles') . " WHERE published = 1";
    $result = $db->query($sql);
    $row = $db->fetchArray($result);
    $total = $row['total'];

    // Get page of results
    $sql = "SELECT * FROM " . $db->prefix('articles') .
           " WHERE published = 1 ORDER BY created DESC";
    $result = $db->query($sql, $perPage, $start);

    $articles = [];
    while ($row = $db->fetchArray($result)) {
        $articles[] = $row;
    }

    return [
        'articles' => $articles,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current' => $page
    ];
}
```

### Search Query with LIKE

```php
function searchArticles(string $keyword): array
{
    $db = XoopsDatabaseFactory::getDatabaseConnection();

    $keyword = $db->escape($keyword);
    $sql = "SELECT * FROM " . $db->prefix('articles') .
           " WHERE title LIKE '%" . $keyword . "%'" .
           " OR content LIKE '%" . $keyword . "%'" .
           " ORDER BY created DESC";

    $result = $db->query($sql, 50);  // Limit to 50 results

    $articles = [];
    while ($row = $db->fetchArray($result)) {
        $articles[] = $row;
    }

    return $articles;
}
```

### Join Query

```php
function getArticlesWithAuthors(): array
{
    $db = XoopsDatabaseFactory::getDatabaseConnection();

    $sql = "SELECT a.*, u.uname as author_name, u.email as author_email
            FROM " . $db->prefix('articles') . " a
            LEFT JOIN " . $db->prefix('users') . " u ON a.author_id = u.uid
            WHERE a.published = 1
            ORDER BY a.created DESC";

    $result = $db->query($sql, 20);

    $articles = [];
    while ($row = $db->fetchArray($result)) {
        $articles[] = $row;
    }

    return $articles;
}
```

---

## SqlUtility Class

Helper class for SQL file operations.

### splitMySqlFile

Splits a SQL file into individual queries.

```php
public static function splitMySqlFile(string $content): array
```

**Example:**
```php
$sqlContent = file_get_contents('install.sql');
$queries = SqlUtility::splitMySqlFile($sqlContent);

foreach ($queries as $query) {
    $db->queryF($query);
    if ($db->errno()) {
        echo "Error executing: " . $query . "\n";
        echo "Error: " . $db->error() . "\n";
    }
}
```

### prefixQuery

Replaces table placeholders with prefixed table names.

```php
public static function prefixQuery(string $sql, string $prefix): string
```

**Example:**
```php
$sql = "CREATE TABLE {PREFIX}_articles (id INT PRIMARY KEY)";
$prefixedSql = SqlUtility::prefixQuery($sql, $db->prefix());
// "CREATE TABLE xoops_articles (id INT PRIMARY KEY)"
```

---

## Best Practices

### Security

1. **Always escape user input**:
```php
$safe = $db->escape($_POST['input']);
```

2. **Use prepared statements when available**:
```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
```

3. **Use quoteString for values**:
```php
$sql = "INSERT INTO table (name) VALUES (" . $db->quoteString($name) . ")";
```

### Performance

1. **Always use LIMIT for large tables**:
```php
$result = $db->query($sql, 100);  // Limit results
```

2. **Free result sets when done**:
```php
$db->freeRecordSet($result);
```

3. **Use appropriate indexes** in your table definitions

4. **Prefer handlers over raw SQL** when possible

### Error Handling

1. **Always check for errors**:
```php
$result = $db->query($sql);
if (!$result) {
    trigger_error($db->error(), E_USER_WARNING);
}
```

2. **Use transactions for multiple related operations**:
```php
$db->beginTransaction();
// ... operations ...
$db->commit();  // or $db->rollback();
```

## Related Documentation

- [[Criteria]] - Query criteria system
- [[../Kernel/Criteria|QueryBuilder]] - Fluent query building
- [[../Core/XoopsObjectHandler]] - Object persistence

---

*See also: [XOOPS Source Code](https://github.com/XOOPS/XoopsCore25/tree/master/htdocs/class/database)*
