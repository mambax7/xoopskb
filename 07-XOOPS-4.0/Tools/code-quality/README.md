# XOOPS 4.0 Code Quality Configuration

Pre-configured static analysis and code style tools for XOOPS 4.0 modules.

## Included Tools

| Tool | Purpose | Config File |
|------|---------|-------------|
| **PHPStan** | Static analysis | `phpstan.neon` |
| **PHP-CS-Fixer** | Code formatting | `.php-cs-fixer.dist.php` |
| **Psalm** | Type checking | `psalm.xml` |

## Installation

### 1. Add Dependencies

Add to your module's `composer.json`:

```json
{
    "require-dev": {
        "phpstan/phpstan": "^1.10",
        "phpstan/phpstan-strict-rules": "^1.5",
        "friendsofphp/php-cs-fixer": "^3.50",
        "vimeo/psalm": "^5.22"
    }
}
```

Or install directly:

```bash
composer require --dev phpstan/phpstan phpstan/phpstan-strict-rules friendsofphp/php-cs-fixer vimeo/psalm
```

### 2. Copy Configuration Files

Copy all files from this directory to your module root:

```bash
cp phpstan.neon /path/to/module/
cp .php-cs-fixer.dist.php /path/to/module/
cp psalm.xml /path/to/module/
```

### 3. Add Composer Scripts

Add these scripts to your `composer.json`:

```json
{
    "scripts": {
        "analyse": "phpstan analyse",
        "psalm": "psalm",
        "cs-check": "php-cs-fixer fix --dry-run --diff",
        "cs-fix": "php-cs-fixer fix",
        "quality": [
            "@cs-check",
            "@analyse",
            "@psalm"
        ]
    }
}
```

## Usage

### PHPStan (Static Analysis)

```bash
# Run analysis
composer analyse

# Or directly
./vendor/bin/phpstan analyse

# With specific level (0-9)
./vendor/bin/phpstan analyse --level=8

# Generate baseline (ignore existing errors)
./vendor/bin/phpstan analyse --generate-baseline
```

**Analysis Levels:**
- Level 0-4: Basic checks
- Level 5-6: Strict type checking
- Level 7-8: Very strict (recommended)
- Level 9: Maximum strictness

### PHP-CS-Fixer (Code Formatting)

```bash
# Check for issues (dry run)
composer cs-check

# Auto-fix issues
composer cs-fix

# Fix specific file
./vendor/bin/php-cs-fixer fix path/to/file.php

# Show diff without fixing
./vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Psalm (Type Checking)

```bash
# Run type checking
composer psalm

# Or directly
./vendor/bin/psalm

# Generate baseline
./vendor/bin/psalm --set-baseline=psalm-baseline.xml

# Show info-level issues
./vendor/bin/psalm --show-info=true
```

### Run All Quality Checks

```bash
composer quality
```

## Configuration Details

### PHPStan (`phpstan.neon`)

**Level 8** analysis with:
- Strict type checking
- Missing typehint detection
- XOOPS legacy function allowances

**Customize:**
```yaml
parameters:
    level: 6  # Lower for legacy code
    paths:
        - src  # Change paths as needed
```

### PHP-CS-Fixer (`.php-cs-fixer.dist.php`)

**PSR-12 + PHP 8.4** with:
- Strict types enforcement
- Trailing commas in multiline
- Ordered imports
- No Yoda conditions
- Strict comparisons

**Key Rules:**
```php
'declare_strict_types' => true,
'strict_comparison' => true,
'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
```

### Psalm (`psalm.xml`)

**Error Level 2** with:
- Unused code detection
- Mixed type reporting
- XOOPS global variable types

## CI/CD Integration

### GitHub Actions

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - name: Install dependencies
        run: composer install --no-progress

      - name: PHP-CS-Fixer
        run: composer cs-check

      - name: PHPStan
        run: composer analyse

      - name: Psalm
        run: composer psalm
```

### GitLab CI

```yaml
code-quality:
  image: php:8.2-cli
  before_script:
    - composer install --no-progress
  script:
    - composer quality
```

## IDE Integration

### PhpStorm

1. **PHP-CS-Fixer:**
   - Settings → PHP → Quality Tools → PHP CS Fixer
   - Set path to `vendor/bin/php-cs-fixer`
   - Enable inspection

2. **PHPStan:**
   - Settings → PHP → Quality Tools → PHPStan
   - Set path to `vendor/bin/phpstan`
   - Set configuration to `phpstan.neon`

3. **Psalm:**
   - Install "Psalm" plugin from marketplace
   - Configure path to `vendor/bin/psalm`

### VS Code

Install extensions:
- `bmewburn.vscode-intelephense-client`
- `junstyle.php-cs-fixer`

Add to `.vscode/settings.json`:
```json
{
    "php-cs-fixer.executablePath": "${workspaceFolder}/vendor/bin/php-cs-fixer",
    "php-cs-fixer.config": ".php-cs-fixer.dist.php",
    "php-cs-fixer.onsave": true
}
```

## Creating XOOPS Stubs

For better type information, create stub files:

**`stubs/XoopsDatabase.phpstub`:**
```php
<?php
class XoopsDatabase {
    public function query(string $sql): mixed {}
    public function queryF(string $sql): mixed {}
    public function fetchArray(mixed $result): ?array {}
    public function fetchRow(mixed $result): ?array {}
    public function prefix(string $table): string {}
    public function quoteString(string $value): string {}
}
```

Then reference in configs:
- PHPStan: `stubFiles: [stubs/XoopsDatabase.phpstub]`
- Psalm: `<file name="stubs/XoopsDatabase.phpstub"/>`

## Baseline Files

To ignore existing errors during migration:

```bash
# PHPStan
./vendor/bin/phpstan analyse --generate-baseline

# Psalm
./vendor/bin/psalm --set-baseline=psalm-baseline.xml
```

Then add to configs:
```yaml
# phpstan.neon
includes:
    - phpstan-baseline.neon
```

```xml
<!-- psalm.xml -->
<psalm errorBaseline="psalm-baseline.xml">
```

## Related Documentation

- [[../../Quick-Reference-Card]]
- [[../../Migration-Guides/Upgrading-Existing-Modules-to-4.0-Architecture]]
- [[../../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development]]
