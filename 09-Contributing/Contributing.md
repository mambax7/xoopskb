---
title: Contributing Guidelines
description: How to contribute to XOOPS CMS development, coding standards, and community guidelines
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: contributing
---

# 🤝 Contributing to XOOPS

> Join the XOOPS community and help make it the best CMS in the world.

---

## 📋 Overview

XOOPS is an open-source project that thrives on community contributions. Whether you're fixing bugs, adding features, improving documentation, or helping others, your contributions are valuable.

---

## 🗂️ Section Contents

### Guidelines
- [[Guidelines/Code-of-Conduct|Code of Conduct]]
- [[Guidelines/Contribution-Workflow|Contribution Workflow]]
- [[Guidelines/Pull-Request-Guidelines|Pull Request Guidelines]]
- [[Guidelines/Issue-Reporting|Issue Reporting]]

### Code Style
- [[../09-Contributing/Code-Style/PHP-Standards|PHP Coding Standards]]
- [[Code-Style/JavaScript-Standards|JavaScript Standards]]
- [[Code-Style/CSS-Guidelines|CSS Guidelines]]
- [[Code-Style/Smarty-Templates|Smarty Template Standards]]

### Architecture Decisions
- [[../09-Contributing/Architecture-Decisions/ADR-Index|ADR Index]]
- [[Architecture-Decisions/ADR-Template|ADR Template]]
- [[../09-Contributing/Architecture-Decisions/ADR-001-Modular-Architecture|ADR-001: Modular Architecture]]
- [[../09-Contributing/Architecture-Decisions/ADR-002-Database-Abstraction|ADR-002: Database Abstraction]]

---

## 🚀 Getting Started

### 1. Set Up Development Environment

```bash
# Fork the repository on GitHub
# Then clone your fork
git clone https://github.com/YOUR_USERNAME/XoopsCore25.git
cd XoopsCore25

# Add upstream remote
git remote add upstream https://github.com/XOOPS/XoopsCore25.git

# Install dependencies
composer install
```

### 2. Create Feature Branch

```bash
# Sync with upstream
git fetch upstream
git checkout -b feature/my-feature upstream/main
```

### 3. Make Changes

Follow the coding standards and write tests for new features.

### 4. Submit Pull Request

```bash
# Commit changes
git add .
git commit -m "Add: Brief description of changes"

# Push to your fork
git push origin feature/my-feature
```

Then create a Pull Request on GitHub.

---

## 📝 Coding Standards

### PHP Standards

XOOPS follows PSR-1, PSR-4, and PSR-12 coding standards.

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule;

use Xmf\Request;
use XoopsObject;

/**
 * Class Item
 *
 * Represents an item in the module
 */
class Item extends XoopsObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initVar('id', \XOBJ_DTYPE_INT, null, false);
        $this->initVar('title', \XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('content', \XOBJ_DTYPE_TXTAREA, '', false);
        $this->initVar('created', \XOBJ_DTYPE_INT, time(), false);
    }

    /**
     * Get formatted title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getVar('title', 'e');
    }
}
```

### Key Conventions

| Rule | Example |
|------|---------|
| Class names | `PascalCase` |
| Method names | `camelCase` |
| Constants | `UPPER_SNAKE_CASE` |
| Variables | `$camelCase` |
| Files | `ClassName.php` |
| Indentation | 4 spaces |
| Line length | Max 120 characters |

### Smarty Templates

```smarty
{* File: templates/mymodule_index.tpl *}
{* Description: Index page template *}

<{include file="db:mymodule_header.tpl"}>

<div class="mymodule-container">
    <h1><{$page_title}></h1>

    <{if $items|@count > 0}>
        <ul class="item-list">
            <{foreach item=item from=$items}>
                <li class="item">
                    <a href="<{$item.url}>"><{$item.title}></a>
                </li>
            <{/foreach}>
        </ul>
    <{else}>
        <p class="no-items"><{$smarty.const._MD_MYMODULE_NO_ITEMS}></p>
    <{/if}>
</div>

<{include file="db:mymodule_footer.tpl"}>
```

---

## 🔀 Git Workflow

### Branch Naming

| Type | Pattern | Example |
|------|---------|---------|
| Feature | `feature/description` | `feature/add-user-export` |
| Bugfix | `fix/description` | `fix/login-validation` |
| Hotfix | `hotfix/description` | `hotfix/security-patch` |
| Release | `release/version` | `release/2.5.12` |

### Commit Messages

Follow conventional commits:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance

**Examples:**
```
feat(auth): add two-factor authentication

Implement TOTP-based 2FA for user accounts.
- Add QR code generation for authenticator apps
- Store encrypted secrets in user profile
- Add backup codes feature

Closes #123
```

```
fix(forms): resolve XSS vulnerability in text input

Properly escape user input in XoopsFormText render method.

Security: CVE-2024-XXXX
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite unit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Writing Tests

```php
<?php

namespace XoopsModulesTest\MyModule;

use PHPUnit\Framework\TestCase;
use XoopsModules\MyModule\Item;

class ItemTest extends TestCase
{
    private Item $item;

    protected function setUp(): void
    {
        $this->item = new Item();
    }

    public function testInitialValues(): void
    {
        $this->assertNull($this->item->getVar('id'));
        $this->assertEquals('', $this->item->getVar('title'));
    }

    public function testSetTitle(): void
    {
        $this->item->setVar('title', 'Test Title');
        $this->assertEquals('Test Title', $this->item->getVar('title'));
    }

    public function testTitleEscaping(): void
    {
        $this->item->setVar('title', '<script>alert("xss")</script>');
        $escaped = $this->item->getTitle();
        $this->assertStringNotContainsString('<script>', $escaped);
    }
}
```

---

## 📋 Pull Request Checklist

Before submitting a PR, ensure:

- [ ] Code follows XOOPS coding standards
- [ ] All tests pass
- [ ] New features have tests
- [ ] Documentation updated if needed
- [ ] No merge conflicts with main branch
- [ ] Commit messages are descriptive
- [ ] PR description explains changes
- [ ] Related issues are linked

---

## 🏗️ Architecture Decision Records

ADRs document significant architectural decisions.

### ADR Template

```markdown
# ADR-XXX: Title

## Status
Proposed | Accepted | Deprecated | Superseded

## Context
What is the issue we're addressing?

## Decision
What is the change being proposed?

## Consequences
What are the positive and negative effects?

## Alternatives Considered
What other options were evaluated?
```

### Current ADRs

| ADR | Title | Status |
|-----|-------|--------|
| [[../09-Contributing/Architecture-Decisions/ADR-001-Modular-Architecture|ADR-001]] | Modular Architecture | Accepted |
| [[../09-Contributing/Architecture-Decisions/ADR-002-Database-Abstraction|ADR-002]] | Object-Oriented Database Access | Accepted |
| [[../09-Contributing/Architecture-Decisions/ADR-003-Template-Engine|ADR-003]] | Smarty Template Engine | Accepted |
| [[Architecture-Decisions/ADR-004-Security-System|ADR-004]] | Security System Design | Accepted |
| [[Architecture-Decisions/ADR-005-Middleware|ADR-005]] | PSR-15 Middleware (4.0.x) | Proposed |

---

## 🎖️ Recognition

Contributors are recognized through:

- **Contributors List** - Listed in repository
- **Release Notes** - Credited in releases
- **Hall of Fame** - Outstanding contributors
- **Module Certification** - Quality badge for modules

---

## 🔗 Related Documentation

- [[../07-XOOPS-4.0/XOOPS-4.0-Roadmap|XOOPS 4.0 Roadmap]]
- [[../02-Core-Concepts/Core-Concepts|Core Concepts]]
- [[../03-Module-Development/Module-Development|Module Development]]

---

## 📚 Resources

- [GitHub Repository](https://github.com/XOOPS/XoopsCore25)
- [Issue Tracker](https://github.com/XOOPS/XoopsCore25/issues)
- [XOOPS Forums](https://xoops.org/modules/newbb/)
- [Discord Community](https://discord.gg/xoops)

---

#xoops #contributing #open-source #community #development #coding-standards
