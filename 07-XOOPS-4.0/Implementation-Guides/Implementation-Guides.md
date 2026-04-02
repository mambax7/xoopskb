---
title: XOOPS 4.0 Implementation Guides
description: Step-by-step guides for implementing XOOPS 4.0 features
created: 2026-01-29
updated: 2026-01-29
version: 1.0.0
author: XOOPS Team
category: guides
tags:
  - implementation
  - guides
  - xoops-4.0
status: stable
---

# 📖 XOOPS 4.0 Implementation Guides

> **Comprehensive guides for implementing modern XOOPS 4.0 features.**

These guides provide step-by-step instructions for adopting XOOPS 4.0's modern architecture patterns and features.

---

## Available Guides

```mermaid
flowchart TB
    subgraph Guides["Implementation Guides"]
        PSR[🔄 PSR-15 Middleware]
        MOD[📋 module.json Manifest]
        DI[📦 Dependency Injection]
        EVT[⚡ Event System]
        API[🌐 REST API]
    end

    PSR --> |"HTTP Pipeline"| App[Your Application]
    MOD --> |"Configuration"| App
    DI --> |"Services"| App
    EVT --> |"Decoupling"| App
    API --> |"Endpoints"| App
```

---

## Core Guides

| Guide | Description | Difficulty |
|-------|-------------|------------|
| [[PSR-15-Middleware-Guide]] | Implement HTTP middleware pipeline | ⭐⭐⭐ |
| [[PSR-11-Dependency-Injection-Guide]] | PSR-11 container configuration | ⭐⭐⭐ |
| [[Event-System-Guide]] | Publishing and subscribing to events | ⭐⭐⭐ |
| [[REST-API-Design-Guide]] | Building RESTful APIs | ⭐⭐⭐ |
| [[Module-JSON-Specification]] | Modern module manifest format | ⭐⭐ |
| [[XMF-Components-Guide]] | XMF everyday utilities — ULID, Slug, JWT, YAML | ⭐⭐ |
| [[XMF-Advanced-Components]] | XMF architectural subsystems — CommandBus, Repository, EventBus | ⭐⭐⭐ |
| [[XTF-Theme-Framework-Guide]] | Building XOOPS 4.0 themes with XTF | ⭐⭐⭐ |
| [[XBO-Business-Objects-Guide]] | ERP/HR/Finance domain objects with XBO (PHP 8.4) | ⭐⭐⭐ |

---

## Coming Soon

- **Caching Guide** - PSR-6/PSR-16 caching strategies (see XMF-Advanced-Components)
- **Queue Guide** - Async job processing
- **Console Commands Guide** - Building CLI commands

---

## Learning Path

```mermaid
flowchart LR
    subgraph Beginner["🌱 Beginner"]
        B1[module.json Basics]
        B2[Simple Middleware]
    end

    subgraph Intermediate["🌿 Intermediate"]
        I1[Custom Middleware]
        I2[DI Configuration]
        I3[Event Handling]
    end

    subgraph Advanced["🌳 Advanced"]
        A1[CQRS Patterns]
        A2[Domain Events]
        A3[Custom Pipelines]
    end

    Beginner --> Intermediate --> Advanced
```

---

## 🔗 Related

- [[../XOOPS-4.0-Roadmap|XOOPS 4.0 Roadmap]]
- [[../Roadmap/Architecture-Vision|Architecture Vision]]
- [Reference Modules (xmfBlog + xmfPortal)](../Reference-Modules/README.md)

---

#guides #implementation #xoops-4.0 #learning
