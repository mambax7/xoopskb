---
title: XoopsObjectHandler Class
description: Base handler class for CRUD operations on XoopsObject instances with database persistence
created: 2024-01-28
updated: 2024-01-28
version: 2.5.11
tags:
  - api
  - core
  - handler
  - crud
  - database
  - persistence
aliases:
  - XoopsObjectHandler
  - Object Handler
  - XoopsPersistableObjectHandler
---

# XoopsObjectHandler Class

The `XoopsObjectHandler` class and its extension `XoopsPersistableObjectHandler` provide a standardized interface for performing CRUD (Create, Read, Update, Delete) operations on `XoopsObject` instances. This implements the Data Mapper pattern, separating domain logic from database access.

## Class Overview

```php
namespace Xoops\Core;

abstract class XoopsObjectHandler
{
    protected XoopsDatabase $db;

    public function __construct(XoopsDatabase $db);
    abstract public function create(bool $isNew = true);
    abstract public function get(int $id);
    abstract public function insert(XoopsObject $obj, bool $force = false): bool;
    abstract public function delete(XoopsObject $obj, bool $force = false): bool;
}
```

## Class Hierarchy

```
XoopsObjectHandler (Abstract Base)
└── XoopsPersistableObjectHandler (Extended Implementation)
    ├── XoopsUserHandler
    ├── XoopsGroupHandler
    ├── XoopsModuleHandler
    ├── XoopsBlockHandler
    ├── XoopsConfigHandler
    └── [Custom Module Handlers]
```

## XoopsObjectHandler

### Constructor

```php
public function __construct(XoopsDatabase $db)
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db` | XoopsDatabase | Database connection instance |

**Example:**
```php
$db = XoopsDatabaseFactory::getDatabaseConnection();
$handler = new MyObjectHandler($db);
```

---

### create

Creates a new object instance.

```php
abstract public function create(bool $isNew = true): ?XoopsObject
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$isNew` | bool | Whether object is new (default: true) |

**Returns:** `XoopsObject|null` - New object instance

**Example:**
```php
$handler = xoops_getHandler('user');
$user = $handler->create();
$user->setVar('uname', 'newuser');
```

---

### get

Retrieves an object by its primary key.

```php
abstract public function get(int $id): ?XoopsObject
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Primary key value |

**Returns:** `XoopsObject|null` - Object instance or null if not found

**Example:**
```php
$handler = xoops_getHandler('user');
$user = $handler->get(1);
if ($user) {
    echo $user->getVar('uname');
}
```

---

### insert

Saves an object to the database (insert or update).

```php
abstract public function insert(
    XoopsObject $obj,
    bool $force = false
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$obj` | XoopsObject | Object to save |
| `$force` | bool | Force operation even if object unchanged |

**Returns:** `bool` - True on success

**Example:**
```php
$handler = xoops_getHandler('user');
$user = $handler->create();
$user->setVar('uname', 'testuser');
$user->setVar('email', 'test@example.com');

if ($handler->insert($user)) {
    echo "User saved with ID: " . $user->getVar('uid');
} else {
    echo "Save failed: " . implode(', ', $user->getErrors());
}
```

---

### delete

Deletes an object from the database.

```php
abstract public function delete(
    XoopsObject $obj,
    bool $force = false
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$obj` | XoopsObject | Object to delete |
| `$force` | bool | Force deletion |

**Returns:** `bool` - True on success

**Example:**
```php
$handler = xoops_getHandler('user');
$user = $handler->get(5);

if ($user && $handler->delete($user)) {
    echo "User deleted";
}
```

---

## XoopsPersistableObjectHandler

The `XoopsPersistableObjectHandler` extends `XoopsObjectHandler` with additional methods for querying and bulk operations.

### Constructor

```php
public function __construct(
    XoopsDatabase $db,
    string $table,
    string $className,
    string $keyName,
    string $identifierName = ''
)
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db` | XoopsDatabase | Database connection |
| `$table` | string | Table name (without prefix) |
| `$className` | string | Full class name of the object |
| `$keyName` | string | Primary key field name |
| `$identifierName` | string | Human-readable identifier field |

**Example:**
```php
class ArticleHandler extends XoopsPersistableObjectHandler
{
    public function __construct(XoopsDatabase $db)
    {
        parent::__construct(
            $db,
            'mymodule_articles',    // Table name
            'Article',               // Class name
            'article_id',            // Primary key
            'title'                  // Identifier field
        );
    }
}
```

---

### getObjects

Retrieves multiple objects matching criteria.

```php
public function getObjects(
    CriteriaElement $criteria = null,
    bool $idAsKey = false,
    bool $asObject = true
): array
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$criteria` | CriteriaElement | Query criteria (optional) |
| `$idAsKey` | bool | Use primary key as array key |
| `$asObject` | bool | Return objects (true) or arrays (false) |

**Returns:** `array` - Array of objects or associative arrays

**Example:**
```php
$handler = xoops_getHandler('user');

// Get all active users
$criteria = new Criteria('level', 0, '>');
$users = $handler->getObjects($criteria);

// Get users with ID as key
$users = $handler->getObjects($criteria, true);
echo $users[1]->getVar('uname'); // Access by ID

// Get as arrays instead of objects
$usersArray = $handler->getObjects($criteria, false, false);
foreach ($usersArray as $userData) {
    echo $userData['uname'];
}
```

---

### getCount

Counts objects matching criteria.

```php
public function getCount(CriteriaElement $criteria = null): int
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$criteria` | CriteriaElement | Query criteria (optional) |

**Returns:** `int` - Count of matching objects

**Example:**
```php
$handler = xoops_getHandler('user');

// Count all users
$totalUsers = $handler->getCount();

// Count active users
$criteria = new Criteria('level', 0, '>');
$activeUsers = $handler->getCount($criteria);

echo "Total: $totalUsers, Active: $activeUsers";
```

---

### getAll

Retrieves all objects (alias for getObjects with no criteria).

```php
public function getAll(
    CriteriaElement $criteria = null,
    array $fields = null,
    bool $asObject = true,
    bool $idAsKey = true
): array
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$criteria` | CriteriaElement | Query criteria |
| `$fields` | array | Specific fields to retrieve |
| `$asObject` | bool | Return as objects |
| `$idAsKey` | bool | Use ID as array key |

**Example:**
```php
$handler = xoops_getHandler('module');

// Get all modules
$modules = $handler->getAll();

// Get only specific fields
$modules = $handler->getAll(null, ['mid', 'name', 'dirname'], false);
```

---

### getIds

Retrieves only the primary keys of matching objects.

```php
public function getIds(CriteriaElement $criteria = null): array
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$criteria` | CriteriaElement | Query criteria |

**Returns:** `array` - Array of primary key values

**Example:**
```php
$handler = xoops_getHandler('user');
$criteria = new Criteria('level', 1);
$adminIds = $handler->getIds($criteria);
// [1, 5, 12, ...] - Array of admin user IDs
```

---

### getList

Retrieves a key-value list for dropdowns.

```php
public function getList(CriteriaElement $criteria = null): array
```

**Returns:** `array` - Associative array [id => identifier]

**Example:**
```php
$handler = xoops_getHandler('group');
$groups = $handler->getList();
// [1 => 'Administrators', 2 => 'Registered Users', ...]

// For a select dropdown
$form->addElement(new XoopsFormSelect('Group', 'group_id', $default, 1, false));
$form->getElement('group_id')->addOptionArray($groups);
```

---

### deleteAll

Deletes all objects matching criteria.

```php
public function deleteAll(
    CriteriaElement $criteria = null,
    bool $force = true,
    bool $asObject = false
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$criteria` | CriteriaElement | Criteria for objects to delete |
| `$force` | bool | Force deletion |
| `$asObject` | bool | Load objects before deleting (triggers events) |

**Returns:** `bool` - True on success

**Example:**
```php
$handler = xoops_getModuleHandler('comment', 'mymodule');

// Delete all comments for a specific article
$criteria = new Criteria('article_id', $articleId);
$handler->deleteAll($criteria);

// Delete with object loading (triggers delete events)
$handler->deleteAll($criteria, true, true);
```

---

### updateAll

Updates a field value for all matching objects.

```php
public function updateAll(
    string $fieldname,
    mixed $fieldvalue,
    CriteriaElement $criteria = null,
    bool $force = false
): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fieldname` | string | Field to update |
| `$fieldvalue` | mixed | New value |
| `$criteria` | CriteriaElement | Criteria for objects to update |
| `$force` | bool | Force update |

**Returns:** `bool` - True on success

**Example:**
```php
$handler = xoops_getModuleHandler('article', 'mymodule');

// Mark all articles by an author as draft
$criteria = new Criteria('author_id', $authorId);
$handler->updateAll('published', 0, $criteria);

// Update view count
$criteria = new Criteria('article_id', $id);
$handler->updateAll('views', $views + 1, $criteria);
```

---

### insert (Extended)

The extended insert method with additional functionality.

```php
public function insert(
    XoopsObject $obj,
    bool $force = false
): bool
```

**Behavior:**
- If object is new (`isNew() === true`): INSERT
- If object exists (`isNew() === false`): UPDATE
- Calls `cleanVars()` automatically
- Sets auto-increment ID on new objects

**Example:**
```php
$handler = xoops_getModuleHandler('article', 'mymodule');

// Create new article
$article = $handler->create();
$article->setVar('title', 'New Article');
$article->setVar('content', 'Content here');
$handler->insert($article);
echo "Created with ID: " . $article->getVar('article_id');

// Update existing article
$article = $handler->get(5);
$article->setVar('title', 'Updated Title');
$handler->insert($article);
```

---

## Helper Functions

### xoops_getHandler

Global function to retrieve a core handler.

```php
function xoops_getHandler(string $name, bool $optional = false): ?XoopsObjectHandler
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | string | Handler name (user, module, group, etc.) |
| `$optional` | bool | Return null instead of triggering error |

**Example:**
```php
$userHandler = xoops_getHandler('user');
$moduleHandler = xoops_getHandler('module');
$groupHandler = xoops_getHandler('group');
$blockHandler = xoops_getHandler('block');
$configHandler = xoops_getHandler('config');
```

---

### xoops_getModuleHandler

Retrieves a module-specific handler.

```php
function xoops_getModuleHandler(
    string $name,
    string $dirname = null,
    bool $optional = false
): ?XoopsObjectHandler
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | string | Handler name |
| `$dirname` | string | Module directory name |
| `$optional` | bool | Return null on failure |

**Example:**
```php
// Get handler from current module
$articleHandler = xoops_getModuleHandler('article');

// Get handler from specific module
$articleHandler = xoops_getModuleHandler('article', 'news');
$storyHandler = xoops_getModuleHandler('story', 'news');
```

---

## Creating Custom Handlers

### Basic Handler Implementation

```php
<?php
namespace XoopsModules\MyModule;

use XoopsPersistableObjectHandler;
use XoopsDatabase;
use CriteriaElement;
use Criteria;
use CriteriaCompo;

/**
 * Handler for Article objects
 */
class ArticleHandler extends XoopsPersistableObjectHandler
{
    /**
     * Constructor
     */
    public function __construct(XoopsDatabase $db = null)
    {
        parent::__construct(
            $db,
            'mymodule_articles',
            Article::class,
            'article_id',
            'title'
        );
    }

    /**
     * Get published articles
     */
    public function getPublished(int $limit = 10, int $start = 0): array
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('published', 1));
        $criteria->add(new Criteria('publish_date', time(), '<='));
        $criteria->setSort('publish_date');
        $criteria->setOrder('DESC');
        $criteria->setLimit($limit);
        $criteria->setStart($start);

        return $this->getObjects($criteria);
    }

    /**
     * Get articles by author
     */
    public function getByAuthor(int $authorId, bool $publishedOnly = true): array
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('author_id', $authorId));

        if ($publishedOnly) {
            $criteria->add(new Criteria('published', 1));
        }

        $criteria->setSort('created');
        $criteria->setOrder('DESC');

        return $this->getObjects($criteria);
    }

    /**
     * Get articles by category
     */
    public function getByCategory(int $categoryId, int $limit = 0): array
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('category_id', $categoryId));
        $criteria->add(new Criteria('published', 1));
        $criteria->setSort('publish_date');
        $criteria->setOrder('DESC');

        if ($limit > 0) {
            $criteria->setLimit($limit);
        }

        return $this->getObjects($criteria);
    }

    /**
     * Search articles
     */
    public function search(string $query, array $fields = ['title', 'content']): array
    {
        $criteria = new CriteriaCompo();
        $searchCriteria = new CriteriaCompo();

        foreach ($fields as $field) {
            $searchCriteria->add(
                new Criteria($field, '%' . $query . '%', 'LIKE'),
                'OR'
            );
        }

        $criteria->add($searchCriteria);
        $criteria->add(new Criteria('published', 1));
        $criteria->setSort('publish_date');
        $criteria->setOrder('DESC');

        return $this->getObjects($criteria);
    }

    /**
     * Get popular articles by view count
     */
    public function getPopular(int $limit = 5): array
    {
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('published', 1));
        $criteria->setSort('views');
        $criteria->setOrder('DESC');
        $criteria->setLimit($limit);

        return $this->getObjects($criteria);
    }

    /**
     * Increment view count
     */
    public function incrementViews(int $articleId): bool
    {
        $sql = sprintf(
            "UPDATE %s SET views = views + 1 WHERE article_id = %d",
            $this->db->prefix($this->table),
            $articleId
        );

        return $this->db->queryF($sql) !== false;
    }

    /**
     * Override insert for custom behavior
     */
    public function insert(\XoopsObject $obj, bool $force = false): bool
    {
        // Set updated timestamp
        $obj->setVar('updated', time());

        // If new, set created timestamp
        if ($obj->isNew()) {
            $obj->setVar('created', time());
        }

        return parent::insert($obj, $force);
    }

    /**
     * Override delete for cascade operations
     */
    public function delete(\XoopsObject $obj, bool $force = false): bool
    {
        // Delete associated comments
        $commentHandler = xoops_getModuleHandler('comment', 'mymodule');
        $criteria = new Criteria('article_id', $obj->getVar('article_id'));
        $commentHandler->deleteAll($criteria);

        return parent::delete($obj, $force);
    }
}
```

### Using the Custom Handler

```php
// Get the handler
$articleHandler = xoops_getModuleHandler('article', 'mymodule');

// Create a new article
$article = $articleHandler->create();
$article->setVars([
    'title' => 'My New Article',
    'content' => 'Article content here...',
    'author_id' => $xoopsUser->getVar('uid'),
    'category_id' => 1,
    'published' => 1,
    'publish_date' => time()
]);

if ($articleHandler->insert($article)) {
    redirect_header('article.php?id=' . $article->getVar('article_id'), 2, 'Article created');
}

// Get published articles
$articles = $articleHandler->getPublished(10);

// Search articles
$results = $articleHandler->search('xoops');

// Get popular articles
$popular = $articleHandler->getPopular(5);

// Update view count
$articleHandler->incrementViews($articleId);
```

## Best Practices

1. **Use Criteria for Queries**: Always use Criteria objects for type-safe queries

2. **Extend for Custom Methods**: Add domain-specific query methods to handlers

3. **Override insert/delete**: Add cascade operations and timestamps in overrides

4. **Use Transaction Where Needed**: Wrap complex operations in transactions

5. **Leverage getList**: Use `getList()` for select dropdowns to reduce queries

6. **Index Keys**: Ensure database fields used in criteria are indexed

7. **Limit Results**: Always use `setLimit()` for potentially large result sets

## Related Documentation

- [[XoopsObject]] - Base object class
- [[../Database/Criteria]] - Building query criteria
- [[../Database/XoopsDatabase]] - Database operations

---

*See also: [XOOPS Source Code](https://github.com/XOOPS/XoopsCore25/blob/master/htdocs/class/xoopsobject.php)*
