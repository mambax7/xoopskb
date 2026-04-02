# XOOPS Knowledge Base

> **Comprehensive documentation for XOOPS CMS development**

[![Documentation](https://img.shields.io/badge/docs-MkDocs-blue)](https://xoops.github.io/knowledge-base/)
[![XOOPS Version](https://img.shields.io/badge/XOOPS-2.5.x%20%7C%204.0-orange)](https://xoops.org)
[![License](https://img.shields.io/badge/License-CC%20BY--SA%204.0-lightgrey)](LICENSE)

---

## Quick Start

### For Site Administrators
- [Installation Guide](01-Getting-Started/Installation/Installation.md)
- [Basic Configuration](01-Getting-Started/Configuration/Basic-Configuration.md)
- [Admin Panel Overview](01-Getting-Started/First-Steps/Admin-Panel-Overview.md)

### For Module Developers
- [Hello World Module](03-Module-Development/Tutorials/Hello-World-Module.md)
- [Design Patterns](02-Core-Concepts/Architecture/Design-Patterns.md)
- [XMF Framework](05-XMF-Framework/XMF-Framework.md)

### For Contributors
- [XOOPS Architecture](02-Core-Concepts/Architecture/XOOPS-Architecture.md)
- [Contributing Guide](09-Contributing/Contributing.md)
- [XOOPS 4.0 Roadmap](07-XOOPS-4.0/XOOPS-4.0-Roadmap.md)

---

---

## Documentation Structure

```
XOOPS-Knowledge-Base/
├── 00-Home/              # Welcome and navigation
├── 01-Getting-Started/   # Installation, configuration
├── 02-Core-Concepts/     # Architecture, templates, security
├── 03-Module-Development/# Patterns, tutorials, best practices
├── 04-API-Reference/     # XoopsObject, XoopsDatabase, etc.
├── 05-XMF-Framework/     # Modern helper library
├── 06-Publisher-Module/  # Content management module
├── 07-XOOPS-4.0/        # Future roadmap, PSR standards
├── 08-Troubleshooting/   # Common issues, debugging
├── 09-Contributing/      # How to contribute
└── 99-Archive/           # Legacy documentation
```

---

## Building the Documentation

### Local Development (Obsidian)

1. Open this folder in [Obsidian](https://obsidian.md/)
2. Navigate using wikilinks
3. Use the graph view to explore connections

### Web Documentation (MkDocs)

```bash
# Install dependencies
pip install mkdocs mkdocs-material

# Build the documentation
mkdocs build

# Serve locally
mkdocs serve
```

Visit `http://localhost:8000` to view the documentation.

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](09-Contributing/Contributing.md) for guidelines.

### Quick Contribution Checklist

- [ ] Use MkDocs-compatible markdown (standard links, not wikilinks)
- [ ] Add version badges where appropriate
- [ ] Include code examples with syntax highlighting
- [ ] Test links before submitting

---

## Resources

- **XOOPS Website:** [xoops.org](https://xoops.org)
- **GitHub Organization:** [github.com/XOOPS](https://github.com/XOOPS)
- **Community Forums:** [xoops.org/modules/newbb/](https://xoops.org/modules/newbb/)

---

## License

This documentation is licensed under [Creative Commons Attribution-ShareAlike 4.0](LICENSE).

---

*Built with ❤️ by the XOOPS Documentation Team*
