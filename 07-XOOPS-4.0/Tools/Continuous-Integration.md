# Continuous Integration for XOOPS

## Overview

Continuous Integration (CI) automates testing, code quality checks, and deployment for XOOPS modules. This guide covers GitHub Actions setup for XOOPS development.

## GitHub Actions Workflow

### Complete CI Pipeline

```yaml
# .github/workflows/ci.yml

name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  tests:
    name: PHP ${{ matrix.php }} Tests
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, pdo_mysql, gd
          coverage: xdebug

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Run tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: coverage.xml

  code-quality:
    name: Code Quality
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --no-progress

      - name: Run PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse

      - name: Run Psalm
        run: vendor/bin/psalm --no-progress

  security:
    name: Security Check
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Security Advisories
        uses: symfonycorp/security-checker-action@v5
```

### Matrix Testing

```yaml
jobs:
  test:
    strategy:
      fail-fast: false
      matrix:
        php: ['8.1', '8.2', '8.3']
        xoops: ['2.5.11', '2026']
        include:
          - php: '8.2'
            xoops: '2026'
            coverage: true

    steps:
      - name: Run tests
        run: |
          if [ "${{ matrix.coverage }}" = "true" ]; then
            vendor/bin/phpunit --coverage-clover coverage.xml
          else
            vendor/bin/phpunit
          fi
```

## Code Quality Tools

### PHP CS Fixer Configuration

```php
// .php-cs-fixer.php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP81Migration' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'native_function_invocation' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
```

### PHPStan Configuration

```neon
# phpstan.neon
parameters:
    level: 8
    paths:
        - src
    excludePaths:
        - src/Legacy
    checkMissingIterableValueType: false
```

### Psalm Configuration

```xml
<!-- psalm.xml -->
<?xml version="1.0"?>
<psalm
    errorLevel="4"
    resolveFromConfigFile="true"
    xmlns="https://getpsalm.org/schema/config"
>
    <projectFiles>
        <directory name="src"/>
        <ignoreFiles>
            <directory name="vendor"/>
        </ignoreFiles>
    </projectFiles>
</psalm>
```

## Automated Releases

```yaml
# .github/workflows/release.yml

name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Build release archive
        run: |
          mkdir -p build
          zip -r build/mymodule-${{ github.ref_name }}.zip \
            src/ templates/ language/ sql/ \
            xoops_version.php composer.json README.md \
            -x "*.git*"

      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          files: build/*.zip
          generate_release_notes: true
```

## Branch Protection

### Recommended Settings

```yaml
# Branch protection rules for main
required_status_checks:
  strict: true
  contexts:
    - "PHP 8.4 Tests"
    - "Code Quality"
    - "Security Check"

required_pull_request_reviews:
  required_approving_review_count: 1
  dismiss_stale_reviews: true

enforce_admins: false
restrictions: null
```

## Local Development

### Pre-commit Hooks

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Run PHP CS Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff
if [ $? -ne 0 ]; then
    echo "PHP CS Fixer found issues. Run: vendor/bin/php-cs-fixer fix"
    exit 1
fi

# Run PHPStan
vendor/bin/phpstan analyse
if [ $? -ne 0 ]; then
    echo "PHPStan found issues."
    exit 1
fi

# Run tests
vendor/bin/phpunit --testsuite unit
```

### Composer Scripts

```json
{
    "scripts": {
        "test": "phpunit",
        "test:coverage": "phpunit --coverage-html coverage",
        "cs:check": "php-cs-fixer fix --dry-run --diff",
        "cs:fix": "php-cs-fixer fix",
        "analyse": "phpstan analyse",
        "qa": [
            "@cs:check",
            "@analyse",
            "@test"
        ]
    }
}
```

## Related Documentation

- [[Test-Generator]] - Generate tests
- [[Module-Generator]] - Module scaffolding
- [[../../09-Contributing/Code-Style]] - Coding standards
- [[../../03-Module-Development/Best-Practices/Testing]] - Testing guide
