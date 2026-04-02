# XOOPS 4.0 Module Generator

A CLI tool that scaffolds new XOOPS modules with Clean Architecture structure, DDD patterns, and all boilerplate files.

## Quick Start

```bash
# Generate a basic module
php generate-module.php articles

# Generate with all features
php generate-module.php blog --entity=Post --with-api --with-admin --with-blocks

# Specify author and output directory
php generate-module.php inventory --author="John Doe" --output=/var/www/xoops/modules
```

## Options

| Option | Description | Default |
|--------|-------------|---------|
| `--entity=<name>` | Primary entity name | Derived from module name |
| `--author=<name>` | Author name for headers | "XOOPS Developer" |
| `--output=<path>` | Output directory | Current directory |
| `--with-api` | Include REST API scaffolding | No |
| `--with-admin` | Include admin interface | No |
| `--with-blocks` | Include block scaffolding | No |
| `--help` | Show help message | - |

## Generated Structure

```
mymodule/
├── Domain/
│   ├── Entity/
│   │   └── MyEntity.php
│   ├── ValueObject/
│   │   ├── MyEntityId.php
│   │   ├── MyEntityTitle.php
│   │   ├── MyEntityContent.php
│   │   └── MyEntityStatus.php
│   ├── Repository/
│   │   └── MyEntityRepositoryInterface.php
│   └── Exception/
│       └── MyEntityException.php
├── Application/
│   ├── Command/
│   │   ├── CreateMyEntityCommand.php
│   │   ├── CreateMyEntityHandler.php
│   │   ├── UpdateMyEntityCommand.php
│   │   ├── UpdateMyEntityHandler.php
│   │   ├── DeleteMyEntityCommand.php
│   │   └── DeleteMyEntityHandler.php
│   └── Query/
│       ├── GetMyEntityQuery.php
│       ├── GetMyEntityHandler.php
│       ├── ListMyEntitiesQuery.php
│       └── ListMyEntitiesHandler.php
├── Infrastructure/
│   ├── Persistence/
│   │   └── MySqlMyEntityRepository.php
│   ├── Xoops/
│   │   └── Container.php
│   └── Api/                    # --with-api
│       └── Controller/
│           └── MyEntitiesApiController.php
├── Presentation/
│   ├── Controller/
│   │   └── MyEntityController.php
│   └── templates/
│       ├── mymodule_index.tpl
│       ├── mymodule_view.tpl
│       ├── mymodule_form.tpl
│       ├── admin/              # --with-admin
│       └── blocks/             # --with-blocks
├── admin/                      # --with-admin
│   ├── index.php
│   └── menu.php
├── api/v1/                     # --with-api
│   ├── index.php
│   ├── .htaccess
│   └── openapi.yaml
├── blocks/                     # --with-blocks
│   └── blocks.php
├── sql/
│   └── mysql.sql
├── language/
│   └── english/
│       ├── main.php
│       ├── modinfo.php
│       ├── admin.php           # --with-admin
│       └── blocks.php          # --with-blocks
├── index.php
├── view.php
├── xoops_version.php
├── composer.json
└── README.md
```

## Examples

### Basic Module

```bash
php generate-module.php notes
```

Creates a `notes` module with a `Note` entity.

### Blog Module with Posts

```bash
php generate-module.php blog --entity=Post --with-api --with-admin
```

Creates a `blog` module with:
- `Post` entity (instead of default `Blog`)
- REST API endpoints at `/modules/blog/api/v1/`
- Admin interface

### Full-Featured Module

```bash
php generate-module.php articles --with-api --with-admin --with-blocks --author="Your Name"
```

Creates a complete module with API, admin panel, and blocks.

## What's Included

### Domain Layer
- **Entity**: Aggregate root with factory method and reconstitution
- **Value Objects**: Id (ULID), Title, Content, Status (enum)
- **Repository Interface**: Persistence contract
- **Exceptions**: Domain-specific error handling

### Application Layer
- **Commands**: Create, Update, Delete with handlers
- **Queries**: Get single, List all with handlers

### Infrastructure Layer
- **MySQL Repository**: Full implementation with hydration
- **DI Container**: Simple service container
- **API Controller**: REST endpoints (optional)

### Presentation Layer
- **Controller**: Web request handling
- **Templates**: Index, View, Form pages
- **Admin Templates**: Dashboard (optional)
- **Block Templates**: Recent items (optional)

### Configuration
- **xoops_version.php**: Module definition
- **composer.json**: Dependencies and autoloading
- **SQL Schema**: Database tables with ULID keys
- **Language Files**: English translations

## Customization

After generation, customize your module:

1. **Add more value objects**: Copy the pattern from generated ones
2. **Add relationships**: Create junction tables and repository methods
3. **Add more queries**: Search, filter, pagination
4. **Customize templates**: Update Smarty templates for your design
5. **Add validation**: Enhance value object validation rules

## Next Steps

After generating your module:

1. Review and customize the generated code
2. Run `composer install` to set up autoloading
3. Install module via XOOPS admin
4. Add your specific business logic
5. Write tests for your domain layer

## Related Documentation

- [[../../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development]]
- [[../../Tutorials/Adding-REST-API-to-Your-Module]]
- [[../../Quick-Reference-Card]]
- [Error Handling & Validation](../../Implementation-Guides/Error-Handling-Validation-Guide.md)
