---
title: Core Concepts
description: Central hub for understanding XOOPS core architecture, design patterns, and fundamental systems
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: core-concepts
---

# Core Concepts

This section provides comprehensive documentation on the fundamental concepts, architecture, and systems that power XOOPS. Understanding these core concepts is essential for effective module development and system customization.

## Overview

XOOPS (eXtensible Object Oriented Portal System) is built on solid architectural foundations that emphasize modularity, extensibility, and maintainability. The system employs well-established design patterns and provides robust abstractions for common web application concerns.

## Architecture

The architecture section covers the fundamental structure and design principles of XOOPS.

- [[../02-Core-Concepts/Architecture/XOOPS-Architecture]] - Complete overview of the XOOPS system architecture
- [[../02-Core-Concepts/Architecture/Design-Patterns]] - Design patterns used throughout XOOPS (MVC, Singleton, Factory, Observer, etc.)

## Database Layer

Understanding the database layer is crucial for data management and persistence.

- [[../02-Core-Concepts/Database/Database-Layer]] - Comprehensive guide to XoopsDatabase, XoopsDatabaseFactory, and database operations

## Template System

The template system provides separation between logic and presentation.

- [[../02-Core-Concepts/Templates/Smarty-Basics]] - Fundamentals of Smarty templating in XOOPS
- [[../02-Core-Concepts/Themes/Theme-Development]] - Creating and customizing XOOPS themes

## Forms System

XOOPS provides a powerful form handling system with built-in validation and rendering.

- [[../02-Core-Concepts/Forms/XOOPS-Forms]] - Complete guide to form creation, validation, and rendering

## Security

Security is paramount in web application development.

- [[../02-Core-Concepts/Security/Security-Best-Practices]] - Security guidelines, input validation, and access control

## User Management

Understanding user and permission systems is essential for access control.

- [[../02-Core-Concepts/Users-Permissions/User-Management]] - User system, groups, and permission management

## Quick Reference

### Key Classes

| Class | Purpose | Location |
|-------|---------|----------|
| `XoopsDatabase` | Abstract database base class | `class/database/` |
| `XoopsDatabaseFactory` | Database connection factory | `class/database/` |
| `XoopsObject` | Base class for all data objects | `class/` |
| `XoopsPersistableObjectHandler` | CRUD operations handler | `class/` |
| `XoopsForm` | Base form class | `class/xoopsform/` |
| `Smarty` | Template engine | `class/smarty/` |

### Key Design Patterns

| Pattern | Usage in XOOPS |
|---------|---------------|
| Singleton | Database connections, global instances |
| Factory | Object creation, database connections |
| MVC | Module structure, separation of concerns |
| Observer | Event handling, notifications |
| Decorator | Form elements, template modifiers |

### Directory Structure

```
xoops/
├── class/                  # Core classes
│   ├── database/           # Database abstraction
│   ├── xoopsform/          # Form elements
│   └── smarty/             # Template engine
├── include/                # Include files
├── kernel/                 # Kernel classes
├── modules/                # Installed modules
├── themes/                 # Theme files
└── uploads/                # User uploads
```

## Getting Started

For new developers, we recommend exploring the documentation in this order:

1. **[[../02-Core-Concepts/Architecture/XOOPS-Architecture]]** - Understand the overall system structure
2. **[[../02-Core-Concepts/Architecture/Design-Patterns]]** - Learn the patterns used throughout XOOPS
3. **[[../02-Core-Concepts/Database/Database-Layer]]** - Master data persistence
4. **[[../02-Core-Concepts/Templates/Smarty-Basics]]** - Learn template development
5. **[[../02-Core-Concepts/Forms/XOOPS-Forms]]** - Create user interfaces
6. **[[../02-Core-Concepts/Security/Security-Best-Practices]]** - Implement secure code
7. **[[../02-Core-Concepts/Users-Permissions/User-Management]]** - Handle users and permissions

## Related Resources

- [[../01-Getting-Started/Getting-Started|Getting Started Guide]]
- [[../03-Module-Development/Module-Development|Module Development]]
- [[../04-API-Reference/API-Reference|API Reference]]

---

#xoops #core-concepts #architecture #documentation #index
