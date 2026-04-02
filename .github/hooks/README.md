# Git Hooks for XOOPS Knowledge Base

This directory contains Git hooks to maintain code quality and prevent common issues.

## Installation

Run the installation script from the repository root:

```bash
.github/hooks/install-hooks.sh
```

Or manually copy the hooks:

```bash
cp .github/hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## Available Hooks

### pre-commit

Runs before each commit to validate:

| Check | Severity | Description |
|-------|----------|-------------|
| Files with spaces | **Error** | Blocks commits with spaces in filenames |
| Wikilinks with spaces | Warning | Warns about `[[Link With Spaces]]` |
| Long filenames | Warning | Warns about filenames >100 characters |

### Why No Spaces?

Files with spaces in their names cause issues with:

1. **MkDocs**: Build failures and broken navigation
2. **URLs**: Spaces become `%20` which looks ugly and can break
3. **Command line**: Requires quoting everywhere
4. **Cross-platform**: Some systems handle spaces differently

### Naming Convention

Use **hyphens** (`-`) instead of spaces:

| ❌ Bad | ✅ Good |
|--------|---------|
| `Getting Started.md` | `Getting-Started.md` |
| `My New Feature.md` | `My-New-Feature.md` |
| `API Reference Guide.md` | `API-Reference-Guide.md` |

## Bypassing Hooks

If you absolutely need to bypass the hooks (not recommended):

```bash
git commit --no-verify
```

## Troubleshooting

### Hook not running?

1. Check if the hook is installed:
   ```bash
   ls -la .git/hooks/pre-commit
   ```

2. Check if it's executable:
   ```bash
   chmod +x .git/hooks/pre-commit
   ```

### False positives?

The hooks exclude:
- `.git/` directory
- `.obsidian/` directory (Obsidian config)
- `_build/` directory (build artifacts)

If you need to exclude more paths, edit the `pre-commit` script.
