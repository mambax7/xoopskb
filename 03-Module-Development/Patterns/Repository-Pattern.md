---
title: Repository Pattern in XOOPS
description: Data access abstraction layer implementation
tags:
  - patterns
  - repository
  - data-access
  - design-patterns
  - module-development
created: 2026-01-28
updated: 2026-01-28
---

# Repository Pattern in XOOPS

<span class="version-badge version-25x">2.5.x ✅</span> <span class="version-badge version-40x">4.0.x ✅</span>

!!! abstract "Not sure if this is the right pattern?"
    See [Choosing a Data Access Pattern](../Choosing-Data-Access-Pattern.md) for a decision tree comparing handlers, repositories, services, and CQRS.


!!! tip "Works Today & Tomorrow"
    The Repository pattern **works in both XOOPS 2.5.x and XOOPS 4.0.x**. In 2.5.x, wrap your existing `XoopsPersistableObjectHandler` in a Repository class to get the abstraction benefits:
    
    | Approach | XOOPS 2.5.x | XOOPS 4.0 |
    |----------|-------------|------------|
    | Direct handler access | `xoops_getModuleHandler()` | Via DI container |
    | Repository wrapper | ✅ Recommended | ✅ Native pattern |
    | Testing with mocks | ✅ With manual DI | ✅ Container autowiring |
    
    **Start with Repository pattern today** to prepare your modules for easier 2026 migration.


The Repository Pattern is a data access pattern that abstracts database operations, providing a clean interface for accessing data. It acts as a middleman between the business logic and data mapping layers.

## Repository Concept

The Repository Pattern provides:
- Abstraction of database implementation details
- Easy mocking for unit testing
- Centralized data access logic
- Flexibility to change database without affecting business logic
- Reusable data access logic across the application

## When to Use Repositories

**Use Repositories when:**
- Transferring data between application layers
- Needing to change database implementation
- Writing testable code with mocks
- Abstracting data access patterns

## Implementation Pattern

```php
<?php
// Define repository interface
interface UserRepositoryInterface
{
    public function find($id);
    public function findAll($limit = null, $offset = 0);
    public function findBy(array $criteria);
    public function save($entity);
    public function update($id, $entity);
    public function delete($id);
}

// Implement repository
class UserRepository implements UserRepositoryInterface
{
    private $db;
    
    public function __construct($connection)
    {
        $this->db = $connection;
    }
    
    public function find($id)
    {
        // Implementation
    }
    
    public function save($entity)
    {
        // Implementation
    }
}
?>
```

## Usage in Services

```php
<?php
class UserService
{
    private $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function registerUser($username, $email, $password)
    {
        // Check if user exists
        if ($this->userRepository->findByUsername($username)) {
            throw new \InvalidArgumentException('Username exists');
        }
        
        // Create user
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($password);
        
        return $this->userRepository->save($user);
    }
}
?>
```

## Best Practices

- Use interfaces to define repository contracts
- Each repository handles one entity type
- Keep business logic in services, not repositories
- Use entity objects for data mapping
- Throw appropriate exceptions for invalid operations

## Related Documentation

See also:
- [MVC-Pattern](../Patterns/MVC-Pattern.md) for controller integration
- [Service-Layer](../Patterns/Service-Layer.md) for service implementation
- [DTO-Pattern](DTO-Pattern.md) for data transfer objects
- [Testing](../Best-Practices/Testing.md) for repository testing

---

Tags: #repository-pattern #data-access #design-patterns #module-development
