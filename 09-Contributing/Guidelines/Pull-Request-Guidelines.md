---
title: Pull Request Guidelines
description: Guidelines for submitting pull requests to XOOPS projects
tags:
  - contributing
  - pull-requests
  - git
  - github
  - code-review
  - development
created: 2026-01-31
updated: 2026-01-31
version: 2026.01
---

# Pull Request Guidelines

This document provides comprehensive guidelines for submitting pull requests to XOOPS projects. Following these guidelines ensures smooth code reviews and faster merge times.

## Before Creating a Pull Request

### Step 1: Check for Existing Issues

```
1. Visit the GitHub repository
2. Go to Issues tab
3. Search for existing issues related to your change
4. Check both open and closed issues
```

### Step 2: Fork and Clone the Repository

```bash
# Fork the repository on GitHub
# Click "Fork" button on the repository page

# Clone your fork
git clone https://github.com/YOUR_USERNAME/XOOPS.git
cd XOOPS

# Add upstream remote
git remote add upstream https://github.com/XOOPS/XOOPS.git

# Verify remotes
git remote -v
# Should show: origin (your fork) and upstream (official)
```

### Step 3: Create a Feature Branch

```bash
# Update main branch
git fetch upstream
git checkout main
git merge upstream/main

# Create feature branch
# Use descriptive names: bugfix/issue-number or feature/description
git checkout -b bugfix/123-fix-database-connection
git checkout -b feature/add-psr-7-support
```

### Step 4: Make Your Changes

```bash
# Make changes to your files
# Follow code style guidelines

# Stage changes
git add .

# Commit with clear message
git commit -m "Fix database connection timeout issue"

# Create multiple commits for logical changes
git commit -m "Add connection retry logic"
git commit -m "Improve error messages for debugging"
```

## Commit Message Standards

### Good Commit Messages

Use clear, descriptive messages following these patterns:

```
# Format
<type>: <subject>

<body>

<footer>

# Example 1: Bug fix
fix: resolve database connection timeout

Add exponential backoff retry mechanism to database connection.
Connections now retry up to 3 times with increasing delays.

Fixes #123
```

```
# Example 2: Feature
feat: implement PSR-7 HTTP message interfaces

Implement Psr\Http\Message interfaces for request/response handling.
Provides type-safe HTTP message handling across the framework.

BREAKING CHANGE: Updated RequestHandler signature
```

### Commit Type Categories

| Type | Description | Example |
|------|-------------|---------|
| `feat` | New feature | `feat: add user dashboard widget` |
| `fix` | Bug fix | `fix: resolve cache invalidation bug` |
| `docs` | Documentation | `docs: update API reference` |
| `style` | Code style (no logic change) | `style: format imports` |
| `refactor` | Code refactoring | `refactor: simplify service layer` |
| `perf` | Performance improvement | `perf: optimize database queries` |
| `test` | Test changes | `test: add integration tests` |
| `chore` | Build/tooling changes | `chore: update dependencies` |

## Pull Request Description

### PR Template

```markdown
## Description
Clear description of changes made and why.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issues
Closes #123
Related to #456

## Changes Made
- Change 1
- Change 2
- Change 3

## Testing
- [ ] Tested locally
- [ ] All tests pass
- [ ] Added new tests
- [ ] Manual testing steps included

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex logic
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Added tests for new functionality
- [ ] All tests passing
```

## Code Quality Requirements

### Code Style

Follow [[Code-Style]] guidelines:

```php
<?php
// Good: PSR-12 style
namespace MyModule\Controller;

use MyModule\Model\Item;
use MyModule\Repository\ItemRepository;

class ItemController
{
    private ItemRepository $repository;

    public function __construct(ItemRepository $repository)
    {
        $this->repository = $repository;
    }

    public function indexAction()
    {
        $items = $this->repository->findAll();
        return $this->render('items', ['items' => $items]);
    }
}
```

## Testing Requirements

### Unit Tests

```php
// tests/Feature/DatabaseConnectionTest.php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Xoops\Database\XoopsDatabase;

class DatabaseConnectionTest extends TestCase
{
    private XoopsDatabase $database;

    protected function setUp(): void
    {
        $this->database = new XoopsDatabase();
    }

    public function testConnectionWithValidCredentials()
    {
        $result = $this->database->connect();
        $this->assertTrue($result);
    }

    public function testConnectionWithInvalidCredentials()
    {
        $this->database->setCredentials('invalid', 'invalid');
        $result = $this->database->connect();
        $this->assertFalse($result);
    }
}
```

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Feature/DatabaseConnectionTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## Working with Branches

### Keep Branch Updated

```bash
# Fetch latest from upstream
git fetch upstream

# Rebase on latest main
git rebase upstream/main

# Or merge if you prefer
git merge upstream/main

# Force push if rebased (warning: only on your branch!)
git push -f origin bugfix/123-fix-database-connection
```

## Creating the Pull Request

### PR Title Format

```
[Type] Short description (fix/feature/docs)

Examples:
- [FIX] Resolve database connection timeout issue (#123)
- [FEATURE] Implement PSR-7 HTTP message interfaces
- [DOCS] Update API reference for Criteria class
```

## Code Review Process

### What Reviewers Look For

1. **Correctness**
   - Does the code solve the stated problem?
   - Are edge cases handled?
   - Is error handling appropriate?

2. **Quality**
   - Does it follow coding standards?
   - Is it maintainable?
   - Is it well-tested?

3. **Performance**
   - Any performance regressions?
   - Are queries optimized?
   - Is memory usage reasonable?

4. **Security**
   - Input validation?
   - SQL injection prevention?
   - Authentication/authorization?

### Responding to Feedback

```bash
# Address feedback
# Edit files based on review comments

# Commit changes
git commit -m "Address code review feedback

- Add additional error handling
- Improve test coverage for edge cases
- Update documentation"

# Push changes
git push origin bugfix/123-fix-database-connection
```

## Common PR Issues and Solutions

### Issue 1: PR is Too Large

**Problem:** Reviewers can't review massive PRs effectively

**Solution:** Break into smaller PRs
- First PR: Core changes
- Second PR: Tests
- Third PR: Documentation

### Issue 2: No Tests Included

**Problem:** Reviewers can't verify functionality

**Solution:** Add comprehensive tests before submitting

### Issue 3: Conflicts with Main

**Problem:** Your branch is out of sync with main

**Solution:** Rebase on latest main

```bash
git fetch upstream
git rebase upstream/main
git push -f origin your-branch
```

## After Merge

### Cleanup

```bash
# Switch to main
git checkout main

# Update main
git pull upstream main

# Delete local branch
git branch -d bugfix/123-fix-database-connection

# Delete remote branch
git push origin --delete bugfix/123-fix-database-connection
```

## Best Practices Summary

### Do's

- Create descriptive commit messages
- Make focused, single-purpose PRs
- Include tests for new functionality
- Update documentation
- Reference related issues
- Keep PR descriptions clear
- Respond promptly to reviews

### Don'ts

- Include unrelated changes
- Merge main into your branch (use rebase)
- Force push after review starts
- Skip tests
- Submit work in progress
- Ignore code review feedback

## Related Documentation

- [[../Contributing]] - Contributing overview
- [[Code-Style]] - Code style guidelines
- [[../../03-Module-Development/Best-Practices/Testing]] - Testing best practices
- [[../Architecture-Decisions/ADR-Index]] - Architectural guidelines

## Resources

- [Git Documentation](https://git-scm.com/doc)
- [GitHub Pull Request Help](https://help.github.com/en/github/collaborating-with-issues-and-pull-requests)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [XOOPS GitHub Organization](https://github.com/XOOPS)

---

**Last Updated:** 2026-01-31
**Applies To:** All XOOPS projects
**Repository:** https://github.com/XOOPS/XOOPS
