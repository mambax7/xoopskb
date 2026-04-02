---
title: XMF Reference Implementations
description: Reference implementations for proposed XMF library components
created: 2026-01-29
updated: 2026-01-29
version: 1.0.0
author: XOOPS Team
category: reference-implementation
parent: "[[../../XOOPS-4.0-Architecture]]"
tags:
  - xmf
  - ulid
  - slug
  - reference
  - implementation
status: proposal
---

# 📦 XMF Reference Implementations

> **Proposed additions to the XMF (XOOPS Module Framework) library.**

This directory contains reference implementations for components proposed to be added to XMF. These are production-ready implementations that can be contributed to the XMF library.

---

## Components

### Xmf\EntityId (Trait)

**Base trait for ULID-based entity identifiers**

Reduces code duplication when creating ID value objects.

**File:** `EntityId.php`

**Usage:**
```php
use Xmf\EntityId;

final readonly class ArticleId implements \Stringable, \JsonSerializable
{
    use EntityId;

    protected static function exceptionClass(): string
    {
        return InvalidArticleId::class;
    }
}

// Full API available:
$id = ArticleId::generate();
$id = ArticleId::fromString('01HV8X5Z0KDMVR8SDPY62J9ACP');
$id->toString();
$id->getTimestamp();
$id->equals($otherId);
$id->compareTo($otherId);
```

---

### Xmf\Ulid

**Universally Unique Lexicographically Sortable Identifier**

A 128-bit identifier that offers significant advantages over UUIDs:

| Feature | ULID | UUID v4 |
|---------|------|---------|
| Length | 26 chars | 36 chars |
| Sortable | ✓ Lexicographic | ✗ |
| URL-safe | ✓ | ✗ |
| Time-extractable | ✓ | ✗ |
| DB Index Performance | Excellent | Poor |

**File:** `Ulid.php`

**Usage:**
```php
use Xmf\Ulid;

// Generate new ULID
$ulid = Ulid::generate();
echo $ulid->toString(); // "01HV8X5Z0KDMVR8SDPY62J9ACP"

// Parse existing ULID
$ulid = Ulid::fromString('01HV8X5Z0KDMVR8SDPY62J9ACP');

// Validate
if (Ulid::isValid($input)) { ... }

// Extract timestamp
$timestamp = $ulid->getTimestamp();

// Compare (ULIDs are sortable)
$ulid1->compareTo($ulid2); // -1, 0, or 1
```

---

### Xmf\Slug

**URL-Friendly String Generator**

Generates clean, SEO-friendly slugs from any text with full Unicode support:

- Multi-language transliteration (Latin, CJK, Cyrillic, Arabic, Greek)
- Configurable separators and length limits
- Word-boundary aware truncation
- Built-in uniqueness suffix support

**File:** `Slug.php`

**Usage:**
```php
use Xmf\Slug;

// Create from text
$slug = Slug::create('Hello World!');
echo $slug->toString(); // "hello-world"

// With options
$slug = Slug::create('My Article Title', [
    'separator' => '-',
    'maxLength' => 50,
    'lowercase' => true,
]);

// Unicode transliteration
$slug = Slug::create('Привет мир'); // "privet-mir"
$slug = Slug::create('你好世界');    // "ni-hao-shi-jie"

// Handle duplicates
$slug = $baseSlug->withSuffix(2); // "my-article-2"

// Validate
if (Slug::isValid($input)) { ... }
```

---

## Requirements

- PHP 8.4+
- `ext-intl` (optional, for enhanced transliteration)
- `ext-mbstring`

---

## Installation (Future)

Once these are contributed to XMF:

```bash
composer require xoops/xmf
```

---

## Integration Example

Using both components together in a domain model:

```php
<?php

namespace MyModule\Domain\ValueObject;

use Xmf\Ulid;
use Xmf\Slug;

final readonly class ArticleId
{
    private function __construct(private Ulid $ulid) {}

    public static function generate(): self
    {
        return new self(Ulid::generate());
    }

    public static function fromString(string $id): self
    {
        return new self(Ulid::fromString($id));
    }

    public function toString(): string
    {
        return $this->ulid->toString();
    }
}

final readonly class ArticleSlug
{
    private function __construct(private Slug $slug) {}

    public static function fromTitle(string $title): self
    {
        return new self(Slug::create($title, [
            'maxLength' => 100,
        ]));
    }

    public function toString(): string
    {
        return $this->slug->toString();
    }
}
```

---

## Contributing to XMF

These implementations are designed to be contributed to the official XMF library. To contribute:

1. Fork the [XMF repository](https://github.com/XOOPS/XMF)
2. Add the component files to `src/`
3. Add corresponding unit tests to `tests/`
4. Update documentation
5. Submit a pull request

---

## Testing

Unit tests are provided in the `Tests/` directory:

- `Tests/UlidTest.php` - Comprehensive ULID tests
- `Tests/SlugTest.php` - Comprehensive Slug tests

Run tests (when integrated into XMF):

```bash
composer test

# Or run specific test class
./vendor/bin/phpunit Tests/UlidTest.php
./vendor/bin/phpunit Tests/SlugTest.php
```

### Test Coverage

| Component | Tests | Coverage |
|-----------|-------|----------|
| Ulid | 25+ | Generation, parsing, validation, comparison, binary conversion |
| Slug | 30+ | Creation, transliteration, validation, suffixes, edge cases |

---

## 🔗 Related

- [[../../Implementation-Guides/XMF-Components-Guide|XMF Components Guide]]
- [[../../XOOPS-4.0-Architecture|XOOPS 4.0 Architecture]]

---

#xmf #ulid #slug #reference-implementation #proposal
