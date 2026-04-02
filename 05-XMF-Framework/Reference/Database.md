---
title: Database Utilities
description: XMF database utilities for schema management, migrations, and data loading
sidebar_position: 2
---

# Database Utilities

The `Xmf\Database` namespace provides classes to simplify database maintenance tasks associated with installing and updating XOOPS modules. These utilities handle schema migrations, table modifications, and initial data loading.

## Overview

The database utilities include:

- **Tables** - Building and executing DDL statements for table modifications
- **Migrate** - Synchronizing database schema between module versions
- **TableLoad** - Loading initial data into tables

## Xmf\Database\Tables

The `Tables` class simplifies creating and modifying database tables. It builds a work queue of DDL (Data Definition Language) statements that are executed together.

### Key Features

- Loads current schema from existing tables
- Queues changes without immediate execution
- Considers current state when determining work to do
- Automatically handles XOOPS table prefix

### Getting Started

```php
use Xmf\Database\Tables;

// Create a new Tables instance
$tables = new Tables();

// Load an existing table or start new schema
$tables->addTable('mymodule_items');

// For existing tables only (fails if table doesn't exist)
$tables->useTable('mymodule_items');
```

### Table Operations

#### Rename a Table

```php
$tables = new Tables();
$tables->addTable('mymodule_old_name');
$tables->renameTable('mymodule_old_name', 'mymodule_new_name');
$tables->executeQueue();
```

#### Set Table Options

```php
$tables->addTable('mymodule_items');
$tables->setTableOptions('mymodule_items', 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$tables->executeQueue();
```

#### Drop a Table

```php
$tables->addTable('mymodule_temp');
$tables->dropTable('mymodule_temp');
$tables->executeQueue();
```

#### Copy a Table

```php
// Copy structure only
$tables->copyTable('mymodule_items', 'mymodule_items_backup', false);

// Copy structure and data
$tables->copyTable('mymodule_items', 'mymodule_items_backup', true);
$tables->executeQueue();
```

### Working with Columns

#### Add a Column

```php
$tables = new Tables();
$tables->addTable('mymodule_items');

$tables->addColumn(
    'mymodule_items',
    'status',
    "TINYINT(1) NOT NULL DEFAULT '1'"
);

$tables->executeQueue();
```

#### Alter a Column

```php
$tables->useTable('mymodule_items');

// Change column attributes
$tables->alterColumn(
    'mymodule_items',
    'title',
    "VARCHAR(255) NOT NULL DEFAULT ''"
);

// Rename and modify column
$tables->alterColumn(
    'mymodule_items',
    'old_column_name',
    "VARCHAR(100) NOT NULL",
    'new_column_name'
);

$tables->executeQueue();
```

#### Get Column Attributes

```php
$tables->useTable('mymodule_items');
$attributes = $tables->getColumnAttributes('mymodule_items', 'title');
// Returns: "VARCHAR(255) NOT NULL DEFAULT ''"
```

#### Drop a Column

```php
$tables->useTable('mymodule_items');
$tables->dropColumn('mymodule_items', 'obsolete_field');
$tables->executeQueue();
```

### Working with Indexes

#### Get Table Indexes

```php
$tables->useTable('mymodule_items');
$indexes = $tables->getTableIndexes('mymodule_items');

// Returns array like:
// [
//     'PRIMARY' => ['columns' => 'item_id', 'unique' => true],
//     'idx_category' => ['columns' => 'category_id', 'unique' => false]
// ]
```

#### Add Primary Key

```php
$tables->addTable('mymodule_items');
$tables->addPrimaryKey('mymodule_items', 'item_id');

// Composite primary key
$tables->addPrimaryKey('mymodule_item_tags', 'item_id, tag_id');
$tables->executeQueue();
```

#### Add Index

```php
$tables->useTable('mymodule_items');

// Simple index
$tables->addIndex('idx_category', 'mymodule_items', 'category_id');

// Unique index
$tables->addIndex('idx_slug', 'mymodule_items', 'slug', true);

// Composite index
$tables->addIndex('idx_cat_status', 'mymodule_items', 'category_id, status');

$tables->executeQueue();
```

#### Drop Index

```php
$tables->useTable('mymodule_items');
$tables->dropIndex('idx_old_index', 'mymodule_items');
$tables->executeQueue();
```

#### Drop All Non-Primary Indexes

```php
// Useful for cleaning up auto-generated index names
$tables->dropIndexes('mymodule_items');
$tables->executeQueue();
```

#### Drop Primary Key

```php
$tables->dropPrimaryKey('mymodule_items');
$tables->executeQueue();
```

### Data Operations

#### Insert Data

```php
$tables->useTable('mymodule_categories');

$tables->insert('mymodule_categories', [
    'category_id' => 1,
    'name' => 'General',
    'weight' => 0
]);

// Without automatic quoting (for expressions)
$tables->insert('mymodule_logs', [
    'created' => 'NOW()',
    'message' => "'Test message'"
], false);

$tables->executeQueue();
```

#### Update Data

```php
$tables->useTable('mymodule_items');

// Update with criteria object
$criteria = new Criteria('status', 0);
$tables->update('mymodule_items', ['status' => 1], $criteria);

// Update with string criteria
$tables->update('mymodule_items', ['hits' => 0], 'hits IS NULL');

$tables->executeQueue();
```

#### Delete Data

```php
$tables->useTable('mymodule_items');

// Delete with criteria
$criteria = new Criteria('status', -1);
$tables->delete('mymodule_items', $criteria);

// Delete with string criteria
$tables->delete('mymodule_items', 'created < DATE_SUB(NOW(), INTERVAL 1 YEAR)');

$tables->executeQueue();
```

#### Truncate Table

```php
$tables->useTable('mymodule_cache');
$tables->truncate('mymodule_cache');
$tables->executeQueue();
```

### Work Queue Management

#### Execute Queue

```php
// Normal execution (respects HTTP method safety)
$result = $tables->executeQueue();

// Force execution even on GET requests
$result = $tables->executeQueue(true);

if (!$result) {
    echo 'Error: ' . $tables->getLastError();
}
```

#### Reset Queue

```php
// Clear queue without executing
$tables->resetQueue();
```

#### Add Raw SQL

```php
// Add custom SQL to the queue
$tables->addToQueue('ALTER TABLE ' . $GLOBALS['xoopsDB']->prefix('mymodule_items') . ' CONVERT TO CHARACTER SET utf8mb4');
$tables->executeQueue();
```

### Error Handling

```php
$tables = new Tables();

if (!$tables->addTable('mymodule_items')) {
    $error = $tables->getLastError();
    $errno = $tables->getLastErrNo();
    // Handle error
}
```

## Xmf\Database\Migrate

The `Migrate` class simplifies synchronizing database changes between module versions. It extends `Tables` with schema comparison and automatic synchronization.

### Basic Usage

```php
use Xmf\Database\Migrate;

// Create migrate instance for a module
$migrate = new Migrate('mymodule');

// Synchronize database with target schema
$migrate->synchronizeSchema();
```

### In Module Update

Typically called in the module's `xoops_module_pre_update_*` function:

```php
function xoops_module_pre_update_mymodule($module, $previousVersion)
{
    $migrate = new \Xmf\Database\Migrate('mymodule');

    // Perform any pre-sync actions (renames, etc.)
    // ...

    // Synchronize schema
    return $migrate->synchronizeSchema();
}
```

### Getting DDL Statements

For large databases or command-line migrations:

```php
$migrate = new Migrate('mymodule');
$statements = $migrate->getSynchronizeDDL();

// Execute statements in batches or from CLI
foreach ($statements as $sql) {
    // Process each statement
}
```

### Pre-Sync Actions

Some changes require explicit handling before synchronization. Extend `Migrate` for complex migrations:

```php
class MyModuleMigrate extends \Xmf\Database\Migrate
{
    public function preSyncActions()
    {
        // Rename a table before sync
        $this->useTable('mymodule_old_name');
        $this->renameTable('mymodule_old_name', 'mymodule_new_name');
        $this->executeQueue();

        // Rename a column
        $this->useTable('mymodule_items');
        $this->alterColumn(
            'mymodule_items',
            'old_column',
            'VARCHAR(255) NOT NULL',
            'new_column'
        );
        $this->executeQueue();
    }
}

// Usage
$migrate = new MyModuleMigrate('mymodule');
$migrate->preSyncActions();
$migrate->synchronizeSchema();
```

### Schema Management

#### Get Current Schema

```php
$migrate = new Migrate('mymodule');
$currentSchema = $migrate->getCurrentSchema();
```

#### Get Target Schema

```php
$targetSchema = $migrate->getTargetDefinitions();
```

#### Save Current Schema

For module developers to capture schema after database changes:

```php
$migrate = new Migrate('mymodule');
$migrate->saveCurrentSchema();
// Saves schema to module's sql/migrate.yml
```

> **Developer Note:** Always make changes to the database first, then run `saveCurrentSchema()`. Do not manually edit the generated schema file.

## Xmf\Database\TableLoad

The `TableLoad` class simplifies loading initial data into tables. Useful for seeding tables with default data during module installation.

### Loading Data from Arrays

```php
use Xmf\Database\TableLoad;

$data = [
    ['category_id' => 1, 'name' => 'General', 'weight' => 0],
    ['category_id' => 2, 'name' => 'News', 'weight' => 10],
    ['category_id' => 3, 'name' => 'Events', 'weight' => 20]
];

$count = TableLoad::loadTableFromArray('mymodule_categories', $data);
echo "Inserted {$count} rows";
```

### Loading Data from YAML

```php
// Load from YAML file
$count = TableLoad::loadTableFromYamlFile(
    'mymodule_categories',
    XOOPS_ROOT_PATH . '/modules/mymodule/sql/categories.yml'
);
```

YAML format:

```yaml
-
  category_id: 1
  name: General
  weight: 0
-
  category_id: 2
  name: News
  weight: 10
```

### Extracting Data

#### Count Rows

```php
// Count all rows
$total = TableLoad::countRows('mymodule_items');

// Count with criteria
$criteria = new Criteria('status', 1);
$activeCount = TableLoad::countRows('mymodule_items', $criteria);
```

#### Extract Rows

```php
// Extract all rows
$rows = TableLoad::extractRows('mymodule_items');

// Extract with criteria
$criteria = new Criteria('category_id', 5);
$rows = TableLoad::extractRows('mymodule_items', $criteria);

// Skip certain columns
$rows = TableLoad::extractRows('mymodule_items', null, ['password', 'token']);
```

### Saving Data to YAML

```php
// Save all data
TableLoad::saveTableToYamlFile(
    'mymodule_categories',
    '/path/to/categories.yml'
);

// Save filtered data
$criteria = new Criteria('is_default', 1);
TableLoad::saveTableToYamlFile(
    'mymodule_settings',
    '/path/to/default_settings.yml',
    $criteria
);

// Save without certain columns
TableLoad::saveTableToYamlFile(
    'mymodule_items',
    '/path/to/items.yml',
    null,
    ['created', 'modified']
);
```

### Truncate Table

```php
// Empty a table
$affectedRows = TableLoad::truncateTable('mymodule_cache');
```

## Complete Migration Example

### xoops_version.php

```php
$modversion['sqlfile']['mysql'] = 'sql/mysql.sql';
$modversion['tables'] = [
    'mymodule_items',
    'mymodule_categories',
    'mymodule_settings'
];
```

### include/onupdate.php

```php
<?php
use Xmf\Database\Migrate;
use Xmf\Database\Tables;
use Xmf\Database\TableLoad;

function xoops_module_pre_update_mymodule($module, $previousVersion)
{
    // Create custom migrate class
    $migrate = new MyModuleMigrate('mymodule');

    // Handle version-specific migrations
    if ($previousVersion < 120) {
        // Version 1.2.0 renamed a table
        $migrate->renameOldTable();
    }

    if ($previousVersion < 130) {
        // Version 1.3.0 renamed a column
        $migrate->renameOldColumn();
    }

    // Synchronize schema
    return $migrate->synchronizeSchema();
}

function xoops_module_update_mymodule($module, $previousVersion)
{
    // Post-update data migrations
    if ($previousVersion < 130) {
        // Load new default settings
        TableLoad::loadTableFromYamlFile(
            'mymodule_settings',
            XOOPS_ROOT_PATH . '/modules/mymodule/sql/new_settings.yml'
        );
    }

    return true;
}

class MyModuleMigrate extends Migrate
{
    public function renameOldTable()
    {
        if ($this->useTable('mymodule_posts')) {
            $this->renameTable('mymodule_posts', 'mymodule_items');
            $this->executeQueue();
        }
    }

    public function renameOldColumn()
    {
        if ($this->useTable('mymodule_items')) {
            $this->alterColumn(
                'mymodule_items',
                'post_title',
                "VARCHAR(255) NOT NULL DEFAULT ''",
                'title'
            );
            $this->executeQueue();
        }
    }
}
```

## API Reference

### Xmf\Database\Tables

| Method | Description |
|--------|-------------|
| `addTable($table)` | Load or create table schema |
| `useTable($table)` | Load existing table only |
| `renameTable($table, $newName)` | Queue table rename |
| `setTableOptions($table, $options)` | Queue table options change |
| `dropTable($table)` | Queue table drop |
| `copyTable($table, $newTable, $withData)` | Queue table copy |
| `addColumn($table, $column, $attributes)` | Queue column addition |
| `alterColumn($table, $column, $attributes, $newName)` | Queue column change |
| `getColumnAttributes($table, $column)` | Get column definition |
| `dropColumn($table, $column)` | Queue column drop |
| `getTableIndexes($table)` | Get index definitions |
| `addPrimaryKey($table, $column)` | Queue primary key |
| `addIndex($name, $table, $column, $unique)` | Queue index |
| `dropIndex($name, $table)` | Queue index drop |
| `dropIndexes($table)` | Queue all index drops |
| `dropPrimaryKey($table)` | Queue primary key drop |
| `insert($table, $columns, $quote)` | Queue insert |
| `update($table, $columns, $criteria, $quote)` | Queue update |
| `delete($table, $criteria)` | Queue delete |
| `truncate($table)` | Queue truncate |
| `executeQueue($force)` | Execute queued operations |
| `resetQueue()` | Clear queue |
| `addToQueue($sql)` | Add raw SQL |
| `getLastError()` | Get last error message |
| `getLastErrNo()` | Get last error code |

### Xmf\Database\Migrate

| Method | Description |
|--------|-------------|
| `__construct($dirname)` | Create for module |
| `synchronizeSchema()` | Sync database to target |
| `getSynchronizeDDL()` | Get DDL statements |
| `preSyncActions()` | Override for custom actions |
| `getCurrentSchema()` | Get current database schema |
| `getTargetDefinitions()` | Get target schema |
| `saveCurrentSchema()` | Save schema for developers |

### Xmf\Database\TableLoad

| Method | Description |
|--------|-------------|
| `loadTableFromArray($table, $data)` | Load from array |
| `loadTableFromYamlFile($table, $file)` | Load from YAML |
| `truncateTable($table)` | Empty table |
| `countRows($table, $criteria)` | Count rows |
| `extractRows($table, $criteria, $skip)` | Extract rows |
| `saveTableToYamlFile($table, $file, $criteria, $skip)` | Save to YAML |

## See Also

- [[../XMF-Framework]] - Framework overview
- [[../Basics/XMF-Module-Helper]] - Module helper class
- [[Metagen]] - Metadata utilities

---

#xmf #database #migration #schema #tables #ddl
