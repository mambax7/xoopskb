---
title: MVC Pattern in XOOPS
description: Model-View-Controller architecture implementation in XOOPS modules
tags:
  - patterns
  - mvc
  - architecture
  - design-patterns
  - module-development
created: 2026-01-28
updated: 2026-01-31
---

# MVC Pattern in XOOPS

<span class="version-badge version-xmf">XMF Required</span> <span class="version-badge version-40x">4.0.x Native</span>

!!! abstract "Not sure if this is the right pattern?"
    See [Choosing a Data Access Pattern](../Choosing-Data-Access-Pattern.md) for guidance on when to use MVC vs simpler patterns.


!!! warning "Clarification: XOOPS Architecture"
    **Standard XOOPS 2.5.x** uses a **Page Controller** pattern (also called Transaction Script), not MVC. Legacy modules use `index.php` with direct includes, global objects (`$xoopsUser`, `$xoopsDB`), and handler-based data access.
    
    **To use MVC in XOOPS 2.5.x**, you need the **XMF Framework** which provides routing and controller support.
    
    **XOOPS 4.0** will natively support MVC with PSR-15 middleware and proper routing.
    
    See also: [Current XOOPS Architecture](../../02-Core-Concepts/Architecture/XOOPS-Architecture.md)


The Model-View-Controller (MVC) pattern is a fundamental architectural pattern for separating concerns in XOOPS modules. This pattern divides an application into three interconnected components.

## MVC Explanation

### Model
The **Model** represents the data and business logic of your application. It:
- Manages data persistence
- Implements business rules
- Validates data
- Communicates with the database
- Is independent of the UI

### View
The **View** is responsible for presenting data to the user. It:
- Renders HTML templates
- Displays model data
- Handles user interface presentation
- Sends user actions to the controller
- Should contain minimal logic

### Controller
The **Controller** handles user interactions and coordinates between Model and View. It:
- Receives user requests
- Processes input data
- Calls model methods
- Selects appropriate views
- Manages application flow

## XOOPS Implementation

In XOOPS, the MVC pattern is implemented using handlers and templates with the Smarty engine providing template support.

### Basic Model Structure
```php
<?php
class UserModel
{
    private $db;
    
    public function getUserById($id)
    {
        // Database query implementation
    }
    
    public function createUser($data)
    {
        // Create user implementation
    }
}
?>
```

### Controller Implementation
```php
<?php
class UserController
{
    private $model;
    
    public function listAction()
    {
        $users = $this->model->getAllUsers();
        return ['users' => $users];
    }
}
?>
```

### View Template
```smarty
{foreach from=$users item=user}
    <div>{$user.username|escape}</div>
{/foreach}
```

## Best Practices

- Keep business logic in Models
- Keep presentation in Views  
- Keep routing/coordination in Controllers
- Don't mix concerns between layers
- Validate all input at the Controller level

## Related Documentation

See also:
- [Repository-Pattern](../Patterns/Repository-Pattern.md) for advanced data access
- [Service-Layer](../Patterns/Service-Layer.md) for business logic abstraction
- [Code-Organization](../Best-Practices/Code-Organization.md) for project structure
- [Testing](../Best-Practices/Testing.md) for MVC testing strategies

---

Tags: #mvc #patterns #architecture #module-development #design-patterns
