---
title: Service Layer Pattern in XOOPS
description: Business logic abstraction and dependency injection
tags:
  - patterns
  - service-layer
  - business-logic
  - dependency-injection
  - design-patterns
created: 2026-01-28
updated: 2026-01-28
---

# Service Layer Pattern in XOOPS

<span class="version-badge version-25x">2.5.x ✅</span> <span class="version-badge version-40x">4.0.x ✅</span>

!!! abstract "Not sure if this is the right pattern?"
    See [Choosing a Data Access Pattern](../Choosing-Data-Access-Pattern.md) for a decision tree comparing handlers, repositories, services, and CQRS.


!!! tip "Works Today & Tomorrow"
    The Service Layer pattern **works in both XOOPS 2.5.x and XOOPS 4.0.x**. The concepts are universal—only the syntax differs:
    
    | Feature | XOOPS 2.5.x | XOOPS 4.0 |
    |---------|-------------|------------|
    | PHP Version | 7.4+ | 8.2+ |
    | Constructor Injection | ✅ Manual wiring | ✅ Container autowiring |
    | Typed Properties | `@var` docblocks | Native type declarations |
    | Readonly Properties | ❌ Not available | ✅ `readonly` keyword |
    
    Code examples below use PHP 8.2+ syntax. For 2.5.x, omit `readonly` and use traditional property declarations.


The Service Layer Pattern encapsulates business logic in dedicated service classes, providing a clear separation between controllers and data access layers. This pattern promotes code reusability, testability, and maintainability.

## Service Layer Concept

### Purpose
The Service Layer:
- Contains domain business logic
- Coordinates multiple repositories
- Handles complex operations
- Manages transactions
- Performs validation and authorization
- Provides high-level operations to controllers

### Benefits
- Reusable business logic across multiple controllers
- Easy to test in isolation
- Centralized business rule implementation
- Clear separation of concerns
- Simplified controller code

## Dependency Injection

```php
<?php
// Service with injected dependencies
class UserService
{
    private $userRepository;
    private $emailService;
    
    public function __construct(
        UserRepositoryInterface $userRepository,
        EmailServiceInterface $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }
    
    public function registerUser($username, $email, $password)
    {
        // Validate
        $this->validate($username, $email, $password);
        
        // Create user
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($password);
        
        // Save
        $userId = $this->userRepository->save($user);
        
        // Send welcome email
        $this->emailService->sendWelcome($email, $username);
        
        return $userId;
    }
    
    private function validate($username, $email, $password)
    {
        $errors = [];
        
        if (strlen($username) < 3) {
            $errors['username'] = 'Username too short';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email';
        }
        
        if (strlen($password) < 6) {
            $errors['password'] = 'Password too short';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Invalid input', $errors);
        }
    }
}
?>
```

## Service Container

```php
<?php
class ServiceContainer
{
    private $services = [];
    
    public function __construct($db)
    {
        // Register repositories
        $this->services['userRepository'] = new UserRepository($db);
        
        // Register services
        $this->services['userService'] = new UserService(
            $this->services['userRepository']
        );
    }
    
    public function get($name)
    {
        if (!isset($this->services[$name])) {
            throw new \InvalidArgumentException("Service not found: $name");
        }
        return $this->services[$name];
    }
}
?>
```

## Usage in Controllers

```php
<?php
class UserController
{
    private $userService;
    
    public function __construct(ServiceContainer $container)
    {
        $this->userService = $container->get('userService');
    }
    
    public function registerAction()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }
        
        try {
            $userId = $this->userService->registerUser(
                $_POST['username'],
                $_POST['email'],
                $_POST['password']
            );
            
            return [
                'success' => true,
                'userId' => $userId,
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'errors' => $e->getErrors(),
            ];
        }
    }
}
?>
```

## Best Practices

- Each service handles one domain concern
- Services depend on interfaces, not implementations
- Use constructor injection for dependencies
- Services should be testable in isolation
- Throw domain-specific exceptions
- Services should not depend on HTTP request details
- Keep services focused and cohesive

## Related Documentation

See also:
- [MVC-Pattern](../Patterns/MVC-Pattern.md) for controller integration
- [Repository-Pattern](../Patterns/Repository-Pattern.md) for data access
- [DTO-Pattern](DTO-Pattern.md) for data transfer objects
- [Testing](../Best-Practices/Testing.md) for service testing

---

Tags: #service-layer #business-logic #dependency-injection #design-patterns
