# XOOPS 4.0 PhpStorm Live Templates

Live templates for faster XOOPS 4.0 module development in PhpStorm and IntelliJ IDEA.

## Installation

Copy `XOOPS4.0.xml` to your PhpStorm templates folder:

- **Windows:** `%APPDATA%\JetBrains\PhpStorm<version>\templates\`
- **macOS:** `~/Library/Application Support/JetBrains/PhpStorm<version>/templates/`
- **Linux:** `~/.config/JetBrains/PhpStorm<version>/templates/`

Then restart PhpStorm. Templates will appear under the "XOOPS4.0" group.

## Available Templates

### Value Objects

| Abbreviation | Description |
|--------------|-------------|
| `xvo` | Complete value object class |
| `xid` | Entity ID with ULID support |
| `xstatus` | Status enum with transitions |

### Entities

| Abbreviation | Description |
|--------------|-------------|
| `xentity` | Full domain entity |

### Repository

| Abbreviation | Description |
|--------------|-------------|
| `xrepo` | Repository interface |
| `xrepoimpl` | MySQL repository implementation |

### Exceptions

| Abbreviation | Description |
|--------------|-------------|
| `xexception` | Domain exception with factory method |

### Commands & Queries

| Abbreviation | Description |
|--------------|-------------|
| `xcmd` | Command DTO |
| `xhandler` | Command handler |
| `xquery` | Query DTO |
| `xqueryhandler` | Query handler |

### Testing

| Abbreviation | Description |
|--------------|-------------|
| `xtest` | PHPUnit 11 test class |
| `xtestm` | Single test method |
| `xprovider` | Test with data provider |

### Common Patterns

| Abbreviation | Description |
|--------------|-------------|
| `xphp` | PHP file header with strict types |
| `xulid` | Generate ULID |
| `xulidv` | Validate ULID with exception |
| `xslug` | Create URL slug |
| `xtrans` | Status transition check |
| `xsql` | MySQL query with hydration |
| `xservice` | Container service getter |

### API Patterns

| Abbreviation | Description |
|--------------|-------------|
| `xapires` | JSON API response |
| `xvalidate` | Request validation |

## Usage

1. Type the abbreviation (e.g., `xvo`)
2. Press `Tab` to expand
3. Fill in the variables (PhpStorm highlights them)
4. Press `Tab` to move to next variable
5. Press `Enter` when done

## Customization

### Editing Templates

1. Go to **Settings** → **Editor** → **Live Templates**
2. Find the **XOOPS40** group
3. Select a template to edit
4. Modify the template text or variables

### Adding New Templates

1. In Live Templates settings, click **+**
2. Select **Live Template**
3. Enter abbreviation, description, and template text
4. Define variables using `$VARIABLE$` syntax
5. Set applicable contexts (PHP)

### Variable Functions

PhpStorm supports expressions for variables:

```
$NAMESPACE$ - User input
$CLASS$ - Extracted from $CLASS_FQN$ using regularExpression()
$DATE$ - Use date() function
```

## Tips

### Auto-detecting Namespace

The `xvo` template attempts to auto-detect the module namespace from the file path. If it doesn't work, just type the namespace manually.

### Reformatting

All templates are set to reformat after expansion. If you want to preserve exact formatting, edit the template and uncheck "Reformat according to style".

### Surround Templates

Some templates can be used to surround existing code. Select code first, then press `Ctrl+Alt+J` (Windows/Linux) or `Cmd+Alt+J` (macOS).

## Related

- [[../VS-Code-Snippets|XOOPS 4.0 VS Code Snippets]] - Same snippets for VS Code
- [[../../Quick-Reference-Card]] - Pattern cheat sheet
