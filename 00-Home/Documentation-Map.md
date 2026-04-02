---
title: Documentation Map
description: Visual guide to navigating the XOOPS Knowledge Base
created: 2026-01-31
updated: 2026-01-31
version: 1.0.0
category: navigation
tags:
  - navigation
  - sitemap
  - guide
---

# 🗺️ Documentation Map

> **Visual guide to finding what you need in the XOOPS Knowledge Base.**

---

## How It All Connects

```mermaid
flowchart TB
    subgraph Start["🏠 Start Here"]
        HOME["Home Page"]
        PATH["Choose Your Path"]
    end

    subgraph Decisions["🧭 Decision Guides"]
        DAP["Choosing a<br/>Data Access Pattern"]
        CES["Choosing an<br/>Event System"]
    end

    subgraph Learn["📚 Learning Tracks"]
        subgraph Track1["Site Admin Track"]
            GS["Getting Started"]
            ADMIN["Admin Panel"]
            CONFIG["Configuration"]
        end

        subgraph Track2["Module Dev Track"]
            HELLO["Hello World Module"]
            PATTERNS["Design Patterns"]
            XMF["XMF Framework"]
        end

        subgraph Track3["Theme Dev Track"]
            SMARTY["Smarty Basics"]
            THEME["Theme Development"]
        end

        subgraph Track4["Contributor Track"]
            ARCH["Architecture"]
            ROADMAP["XOOPS 4.0 Roadmap"]
            CONTRIB["Contributing"]
        end
    end

    subgraph Reference["📖 Reference"]
        API["API Reference"]
        DB["Database Layer"]
        SEC["Security"]
        HMC["Hybrid Mode Contract"]
    end

    subgraph Future["🚀 XOOPS 4.0"]
        WHATS["What's New"]
        PSR["PSR Standards"]
        MIG["Migration Guides"]
    end

    HOME --> PATH
    PATH --> Track1
    PATH --> Track2
    PATH --> Track3
    PATH --> Track4

    Track2 --> DAP
    Track2 --> CES
    Track4 --> HMC

    DAP --> PATTERNS
    CES --> PATTERNS

    PATTERNS --> XMF
    XMF --> ROADMAP
    ROADMAP --> WHATS
    WHATS --> MIG
    MIG --> GOLD

    style Start fill:#e3f2fd,stroke:#1976d2
    style Decisions fill:#fff9c4,stroke:#f9a825
    style Learn fill:#e8f5e9,stroke:#388e3c
    style Reference fill:#fce4ec,stroke:#c2185b
    style Future fill:#ede7f6,stroke:#7b1fa2
```

---

## By Topic

### 🎯 Getting Started

```mermaid
flowchart LR
    A["Installation"] --> B["Configuration"]
    B --> C["Admin Panel"]
    C --> D["First Module"]
    D --> E["First Theme"]

    style A fill:#c8e6c9
    style E fill:#bbdefb
```

| Page | Description | Audience |
|------|-------------|----------|
| [Installation](../01-Getting-Started/Installation/Installation.md) | Set up XOOPS on your server | Everyone |
| [Server Requirements](../01-Getting-Started/Installation/Server-Requirements.md) | PHP/MySQL version requirements | Everyone |
| [Basic Configuration](../01-Getting-Started/Configuration/Basic-Configuration.md) | Essential settings | Admins |
| [Admin Panel](../01-Getting-Started/First-Steps/Admin-Panel-Overview.md) | Dashboard walkthrough | Admins |

---

### 🏗️ Architecture & Patterns

```mermaid
flowchart TD
    ARCH["XOOPS Architecture"] --> CURRENT["Current: Page Controller"]
    ARCH --> FUTURE["Future: PSR-15 Middleware"]

    CURRENT --> HANDLER["Handler Pattern"]
    CURRENT --> PRELOAD["Preload Events"]

    FUTURE --> DI["Dependency Injection"]
    FUTURE --> PSR14["PSR-14 Events"]

    HANDLER --> REPO["Repository Pattern"]
    REPO --> SERVICE["Service Layer"]
    SERVICE --> CQRS["CQRS (advanced)"]

    style CURRENT fill:#c8e6c9
    style FUTURE fill:#bbdefb
    style CQRS fill:#ffcdd2
```

| Page | What You'll Learn | Version       |
|------|-------------------|---------------|
| [XOOPS Architecture](../02-Core-Concepts/Architecture/XOOPS-Architecture.md) | System layers, request lifecycle | 2.5.x/4.0 |
| [Design Patterns](../02-Core-Concepts/Architecture/Design-Patterns.md) | MVC, Singleton, Factory, Observer | 2.5.x/4.0    |
| [Choosing Patterns](../03-Module-Development/Choosing-Data-Access-Pattern.md) | Decision tree for data access | 2.5.x/4.0    |
| [Choosing Events](../02-Core-Concepts/Choosing-Event-System.md) | Preloads vs PSR-14 | 2.5.x/4.0    |

---

### 🔧 Module Development

```mermaid
flowchart LR
    subgraph Beginner["Beginner"]
        HW["Hello World"]
        CRUD["CRUD Module"]
    end

    subgraph Intermediate["Intermediate"]
        PAT["Patterns"]
        XMF["XMF Framework"]
        TEST["Testing"]
    end

    subgraph Advanced["Advanced"]
        REST["REST API"]
        CLI["CLI Commands"]
        DDD["Domain-Driven"]
    end

    Beginner --> Intermediate --> Advanced

    style Beginner fill:#c8e6c9
    style Intermediate fill:#fff9c4
    style Advanced fill:#ffcdd2
```

| Page | Skill Level | Time |
|------|-------------|------|
| [Hello World](../03-Module-Development/Tutorials/Hello-World-Module.md) | 🟢 Beginner | 1 hour |
| [CRUD Module](../03-Module-Development/Tutorials/Building-a-CRUD-Module.md) | 🟢 Beginner | 2-3 hours |
| [Repository Pattern](../03-Module-Development/Patterns/Repository-Pattern.md) | 🟡 Intermediate | 2 hours |
| [XMF Framework](../05-XMF-Framework/XMF-Framework.md) | 🟡 Intermediate | 4 hours |
| [REST API](../07-XOOPS-4.0/Tutorials/Adding-REST-API-to-Your-Module.md) | 🔴 Advanced | 1 day |

---

### 🔮 XOOPS 4.0 Track

```mermaid
flowchart TB
    WHATS["What's New in XOOPS 4.0"] --> PSR["PSR Standards"]
    PSR --> PSR4["PSR-4 Autoloading"]
    PSR --> PSR7["PSR-7 HTTP"]
    PSR --> PSR11["PSR-11 Container"]
    PSR --> PSR15["PSR-15 Middleware"]

    WHATS --> HMC["Hybrid Mode Contract"]
    HMC --> H0["H0: Pure Legacy"]
    HMC --> H1["H1: Shims"]
    HMC --> H2["H2: Hybrid"]
    HMC --> H3["H3: Modern"]

    WHATS --> MIG["Migration Guides"]
    style WHATS fill:#ede7f6
    style HMC fill:#fff9c4
```

| Page                                                                           | Description |
|--------------------------------------------------------------------------------|-------------|
| [What's New in XOOPS 4.0](../07-XOOPS-4.0/Whats-New-in-4.0.md)                 | Quick overview of all changes |
| [Hybrid Mode Contract](../07-XOOPS-4.0/Specifications/Hybrid-Mode-Contract.md) | Compatibility guarantees |
| [PSR Standards](../07-XOOPS-4.0/PSR-Standards/PSR-Standards-Overview.md)       | Modern PHP standards |
| [Migration Guide](../07-XOOPS-4.0/Migration-Guides/From-2.5-to-4.0.md)         | Step-by-step migration |

---

## Quick Links by Role

### 👤 Site Administrator

1. [Install XOOPS](../01-Getting-Started/Installation/Installation.md)
2. [Configure basics](../01-Getting-Started/Configuration/Basic-Configuration.md)
3. [Learn admin panel](../01-Getting-Started/First-Steps/Admin-Panel-Overview.md)
4. [Troubleshoot issues](../08-Troubleshooting/Troubleshooting.md)

### 🔧 Module Developer

1. [Build first module](../03-Module-Development/Tutorials/Hello-World-Module.md)
2. [Choose patterns](../03-Module-Development/Choosing-Data-Access-Pattern.md)
3. [Use XMF](../05-XMF-Framework/XMF-Framework.md)
4. [Reference API](../04-API-Reference/API-Reference.md)

### 🎨 Theme Developer

1. [Learn Smarty](../02-Core-Concepts/Templates/Smarty-Basics.md)
2. [Use Smarty variables](../02-Core-Concepts/Templates/Template-Variables.md)
3. [Build themes](../02-Core-Concepts/Themes/Theme-Development.md)
4. [Prepare for Smarty 4](../02-Core-Concepts/Templates/Smarty-4-Migration.md)

### 🚀 Core Contributor

1. [Understand architecture](../02-Core-Concepts/Architecture/XOOPS-Architecture.md)
2. [Study roadmap](../07-XOOPS-4.0/XOOPS-4.0-Roadmap.md)
3. [Learn compatibility](../07-XOOPS-4.0/Specifications/Hybrid-Mode-Contract.md)
4. [Contribute](../09-Contributing/Contributing.md)

---

## Legend

| Badge                                                            | Meaning |
|------------------------------------------------------------------|---------|
| <span class="version-badge version-25x">2.5.x ✅</span>           | Works in current stable XOOPS |
| <span class="version-badge version-40x">4.0 ✅</span>             | Works in XOOPS 4.0 |
| <span class="version-badge version-xmf">XMF Required</span>      | Requires XMF Framework |
| <span class="version-badge version-deprecated">Deprecated</span> | Will be removed in future |

---

#navigation #sitemap #guide
