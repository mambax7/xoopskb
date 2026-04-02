---
title: Code Organization Best Practices
description: Module structure, naming conventions, and PSR-4 autoloading
tags:
  - best-practices
  - code-organization
  - psr-4
  - project-structure
  - module-development
created: 2026-01-28
updated: 2026-01-28
---

# Code Organization Best Practices in XOOPS

Proper code organization is essential for maintainability, scalability, and team collaboration.

## Module Directory Structure

A well-organized XOOPS module should follow this structure:

```
mymodule/
├── xoops_version.php           # Module metadata
├── index.php                    # Frontend entry point
├── admin.php                    # Admin entry point
├── class/
│   ├── Controller/             # Request handlers
│   ├── Handler/                # Data handlers
│   ├── Repository/             # Data access
│   ├── Entity/                 # Domain objects
│   ├── Service/                # Business logic
│   ├── DTO/                    # Data transfer objects
│   └── Exception/              # Custom exceptions
├── templates/                  # Smarty templates
│   ├── admin/                  # Admin templates
│   └── blocks/                 # Block templates
├── assets/
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript
│   └── images/                 # Images
├── sql/                        # Database schemas
├── tests/                      # Unit and integration tests
├── docs/                       # Documentation
└── composer.json              # Composer configuration
```

## Naming Conventions

### PHP Naming Standards (PSR-12)

```
Classes:      PascalCase         (UserController, PostRepository)
Methods:      camelCase          (getUserById, createUser)
Properties:   camelCase          ($userId, $username)
Constants:    UPPER_SNAKE_CASE   (DEFAULT_LIMIT, MAX_USERS)
Functions:    snake_case         (get_user_data, validate_email)
Files:        PascalCase.php     (UserController.php)
```

### File and Directory Organization

- One class per file
- Filename matches class name
- Directory structure matches namespace hierarchy
- Keep related classes together
- Use consistent naming across module

## PSR-4 Autoloading

### Composer Configuration

```json
{
  "autoload": {
    "psr-4": {
      "Xoops\\Module\\Mymodule\\": "class/"
    }
  }
}
```

### Manual Autoloader

```php
<?php
class Autoloader
{
    public static function register()
    {
        spl_autoload_register([self::class, 'autoload']);
    }
    
    public static function autoload($class)
    {
        $prefix = 'Xoops\\Module\\Mymodule\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }
        
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . 
                str_replace('\\', '/', $relative) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
}
?>
```

## Best Practices

### 1. Single Responsibility
- Each class should have one reason to change
- Separate concerns into different classes
- Keep classes focused and cohesive

### 2. Consistent Naming
- Use meaningful, descriptive names
- Follow PSR-12 coding standards
- Avoid abbreviations unless obvious
- Use consistent patterns

### 3. Directory Organization
- Group related classes together
- Separate concerns into subdirectories
- Keep templates and assets organized
- Use consistent file naming

### 4. Namespace Usage
- Use proper namespaces for all classes
- Follow PSR-4 autoloading
- Namespace matches directory structure

### 5. Configuration Management
- Centralize configuration in config directory
- Use environment-based configuration
- Don't hardcode settings

## Module Bootstrap

```php
<?php
class Bootstrap
{
    private static $serviceContainer;
    private static $initialized = false;
    
    public static function initialize()
    {
        if (self::$initialized) {
            return;
        }
        
        global $xoopsDB;
        self::$serviceContainer = new ServiceContainer($xoopsDB);
        self::$initialized = true;
    }
    
    public static function getServiceContainer()
    {
        if (!self::$initialized) {
            self::initialize();
        }
        return self::$serviceContainer;
    }
}
?>
```

## Related Documentation

See also:
- [[Error-Handling]] for exception management
- [[Testing]] for test organization
- [[../Patterns/MVC-Pattern]] for controller structure

---

Tags: #best-practices #code-organization #psr-4 #module-development
