---
title: PSR Standards in XOOPS 4.0
description: Overview of PHP Standard Recommendations (PSR) implementation in XOOPS 4.0
tags:
  - psr
  - standards
  - modernization
  - coding-standards
  - php
  - 2026
created: 2026-01-31
updated: 2026-01-31
version: 2026.01
---

# PSR Standards in XOOPS 4.0

XOOPS 4.0 embraces modern PHP standards through implementation of key PHP Standard Recommendations (PSRs). These standards ensure interoperability with other PHP frameworks and libraries while maintaining clean, maintainable code.

## Overview of Adopted PSRs

XOOPS 4.0 implements and supports the following PSR standards:

| PSR | Title | Status | Purpose |
|-----|-------|--------|---------|
| PSR-4 | Autoloading Standard | Implemented | Namespace-based class autoloading |
| PSR-7 | HTTP Message Interfaces | Implemented | Standard HTTP request/response handling |
| PSR-11 | Container Interface | Implemented | Dependency injection containers |
| PSR-14 | Event Dispatcher | Implemented | Standardized event handling |
| PSR-15 | HTTP Server Request Handlers | Planned | Middleware and request handlers |

## PSR-4: Autoloading Standard

PSR-4 defines a standard for namespace-based class autoloading, replacing the older PSR-0 standard.

### XOOPS PSR-4 Structure

```
modules/
  mymodule/
    src/
      Controller/
      Model/
      Repository/
      Service/
    tests/
    xoops_module.php
```

### Namespace Convention

```php
// File: modules/mymodule/src/Controller/ItemController.php
namespace MyModule\Controller;

use MyModule\Model\Item;
use MyModule\Repository\ItemRepository;

class ItemController
{
    // Controller logic
}
```

### Composer Configuration

```json
{
  "autoload": {
    "psr-4": {
      "MyModule\\": "modules/mymodule/src/",
      "MyModule\\Tests\\": "modules/mymodule/tests/"
    }
  }
}
```

### Benefits of PSR-4

- Predictable file locations based on namespace
- Eliminates manual require/include statements
- Compatible with modern PHP tools and IDEs
- Supports better code organization
- Enables lazy loading of classes

## PSR-7: HTTP Message Interfaces

PSR-7 defines standard interfaces for HTTP messages, enabling consistent request/response handling across libraries.

### Core Interfaces

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

// Request handling
interface RequestInterface extends MessageInterface
{
    public function getRequestTarget(): string;
    public function withRequestTarget($requestTarget): RequestInterface;
    public function getMethod(): string;
    public function withMethod($method): RequestInterface;
    public function getUri(): UriInterface;
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface;
}

// Response handling
interface ResponseInterface extends MessageInterface
{
    public function getStatusCode(): int;
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface;
    public function getReasonPhrase(): string;
}
```

## Implementation Strategy

### Phase 1: Foundation (Q1 2026)
- Implement PSR-4 autoloading across all modules
- Adopt PSR-7 HTTP message interfaces
- Migrate to PSR-11 container-based DI

### Phase 2: Integration (Q2 2026)
- Implement PSR-14 event dispatching
- Refactor core components for PSR compliance
- Update module templates and examples

### Phase 3: Advanced (Q3-Q4 2026)
- Implement PSR-15 middleware pipeline
- Create compatibility layer for legacy code
- Provide migration tools and documentation

## Related Documentation

- [[PHP-8-Compatibility]] - PHP 8 compatibility guide
- [[../../03-Module-Development/Patterns/Service-Layer-Pattern]] - Service layer design patterns
- [[../../03-Module-Development/Module-Development]] - Module development overview
- [[Hooks-Events]] - Event handling in XOOPS
