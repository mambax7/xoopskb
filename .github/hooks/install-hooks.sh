#!/bin/bash
#
# XOOPS Knowledge Base - Git Hooks Installer
#
# This script installs the pre-commit hooks for this repository.
# Run from the repository root:
#   .github/hooks/install-hooks.sh
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
GIT_HOOKS_DIR="$REPO_ROOT/.git/hooks"

echo "Installing Git hooks for XOOPS Knowledge Base..."
echo ""

# Check if we're in a git repository
if [ ! -d "$REPO_ROOT/.git" ]; then
    echo "❌ Error: Not a git repository"
    echo "Please run this script from the repository root."
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p "$GIT_HOOKS_DIR"

# Install pre-commit hook
if [ -f "$SCRIPT_DIR/pre-commit" ]; then
    cp "$SCRIPT_DIR/pre-commit" "$GIT_HOOKS_DIR/pre-commit"
    chmod +x "$GIT_HOOKS_DIR/pre-commit"
    echo "✅ Installed pre-commit hook"
else
    echo "⚠️  pre-commit hook not found in $SCRIPT_DIR"
fi

echo ""
echo "Git hooks installed successfully!"
echo ""
echo "The following checks will run before each commit:"
echo "  • Files with spaces in names (blocked)"
echo "  • Wikilinks with spaces (warning)"
echo "  • Very long filenames (warning)"
echo ""
echo "To bypass hooks (not recommended):"
echo "  git commit --no-verify"
