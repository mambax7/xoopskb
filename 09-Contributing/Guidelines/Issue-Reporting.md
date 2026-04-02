---
title: Issue Reporting Guidelines
description: How to report bugs, feature requests, and other issues effectively
created: 2024-01-28
updated: 2024-01-28
version: 1.0.0
category: guidelines
---

# Issue Reporting Guidelines

> Effective bug reports and feature requests are crucial for XOOPS development. This guide helps you create high-quality issues.

---

## Before Reporting

### Check Existing Issues

**Always search first:**

1. Go to [GitHub Issues](https://github.com/XOOPS/XoopsCore25/issues)
2. Search for keywords related to your issue
3. Check closed issues - might be already resolved
4. Look at pull requests - might be in progress

Use search filters:
- `is:issue is:open label:bug` - Open bugs
- `is:issue is:open label:feature` - Open feature requests
- `is:issue sort:updated` - Recently updated issues

### Is It Really an Issue?

Consider first:

- **Configuration issue?** - Check the documentation
- **Usage question?** - Ask on [[../../01-Getting-Started/Getting-Started|forums]] or [[../../00-Home|Discord community]]
- **Security issue?** - See [[#security-issues]] section below
- **Module-specific?** - Report to module maintainer
- **Theme-specific?** - Report to theme author

---

## Issue Types

### Bug Report

A bug is an unexpected behavior or defect.

**Examples:**
- Login not working
- Database errors
- Missing form validation
- Security vulnerability

### Feature Request

A feature request is a suggestion for new functionality.

**Examples:**
- Add support for new feature
- Improve existing functionality
- Add missing documentation
- Performance improvements

### Enhancement

An enhancement improves existing functionality.

**Examples:**
- Better error messages
- Improved performance
- Better API design
- Better user experience

### Documentation

Documentation issues include missing or incorrect documentation.

**Examples:**
- Incomplete API documentation
- Outdated guides
- Missing code examples
- Typos in documentation

---

## Reporting a Bug

### Bug Report Template

```markdown
## Description
Brief, clear description of the bug.

## Steps to Reproduce
1. Step one
2. Step two
3. Step three

## Expected Behavior
What should happen.

## Actual Behavior
What actually happens.

## Environment
- XOOPS Version: X.Y.Z
- PHP Version: 7.4/8.0/8.1
- Database: MySQL/MariaDB version
- Operating System: Windows/macOS/Linux
- Browser: Chrome/Firefox/Safari

## Screenshots
If applicable, add screenshots showing the issue.

## Additional Context
Any other relevant information.

## Possible Fix
If you have suggestions for fixing the issue (optional).
```

### Good Bug Report Example

```markdown
## Description
Login page shows blank page when database connection fails.

## Steps to Reproduce
1. Stop the MySQL service
2. Navigate to the login page
3. Observe the behavior

## Expected Behavior
Show a user-friendly error message explaining the database connection issue.

## Actual Behavior
The page is completely blank - no error message, no interface visible.

## Environment
- XOOPS Version: 2.5.12
- PHP Version: 8.0.28
- Database: MySQL 5.7
- Operating System: Ubuntu 20.04
- Browser: Chrome 120

## Additional Context
This likely affects other pages too. The error should be displayed to admins or logged appropriately.

## Possible Fix
Check database connection in header.php before rendering the template.
```

### Poor Bug Report Example

```markdown
## Description
Login doesn't work

## Steps to Reproduce
It doesn't work

## Expected Behavior
It should work

## Actual Behavior
It doesn't

## Environment
Latest version
```

---

## Reporting a Feature Request

### Feature Request Template

```markdown
## Description
Clear, concise description of the feature.

## Problem Statement
Why is this feature needed? What problem does it solve?

## Proposed Solution
Describe your ideal implementation or UX.

## Alternatives Considered
Are there other ways to achieve this goal?

## Additional Context
Any mockups, examples, or references.

## Expected Impact
How would this benefit users? Would it be breaking?
```

### Good Feature Request Example

```markdown
## Description
Add two-factor authentication (2FA) for user accounts.

## Problem Statement
With increasing security breaches, many CMS platforms now offer 2FA. XOOPS users want stronger account security beyond passwords.

## Proposed Solution
Implement TOTP-based 2FA (compatible with Google Authenticator, Authy, etc.).
- Users can enable 2FA in their profile
- Display QR code for setup
- Generate backup codes for recovery
- Require 2FA code at login

## Alternatives Considered
- SMS-based 2FA (requires carrier integration, less secure)
- Hardware keys (too complex for average users)

## Additional Context
Similar to GitHub, GitLab, and WordPress implementations.
Reference: [TOTP Standard RFC 6238](https://tools.ietf.org/html/rfc6238)

## Expected Impact
Increases account security. Could be optional initially, mandatory in future versions.
```

---

## Security Issues

### Do NOT Report Publicly

**Never create a public issue for security vulnerabilities.**

### Report Privately

1. **Email the security team:** security@xoops.org
2. **Include:**
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Your contact information

### Responsible Disclosure

- We will acknowledge receipt within 48 hours
- We will provide updates every 7 days
- We will work on a fix timeline
- You may request credit for the discovery
- Coordinate public disclosure timing

### Security Issue Example

```
Subject: [SECURITY] XSS Vulnerability in Comment Form

Description:
The comment form in Publisher module does not properly escape user input,
allowing stored XSS attacks.

Steps to Reproduce:
1. Create a comment with: <img src=x onerror="alert('xss')">
2. Submit the form
3. The JavaScript executes when viewing the comment

Impact:
Attackers can steal user session tokens, perform actions as users,
or deface the website.

Environment:
- XOOPS 2.5.12
- Publisher Module 1.x
```

---

## Issue Title Best Practices

### Good Titles

```
✅ Login page shows blank error when database connection fails
✅ Add two-factor authentication support
✅ Form validation not preventing SQL injection in name field
✅ Improve performance of user list query
✅ Update installation documentation for PHP 8.1
```

### Poor Titles

```
❌ Bug in system
❌ Help me!!
❌ It doesn't work
❌ Question about XOOPS
❌ Error
```

### Title Guidelines

- **Be specific** - Mention what and where
- **Be concise** - Under 75 characters
- **Use present tense** - "shows blank page" not "showed blank"
- **Include context** - "in admin panel", "during installation"
- **Avoid generic words** - Not "fix", "help", "problem"

---

## Issue Description Best Practices

### Include Essential Information

1. **What** - Clear description of the issue
2. **Where** - Which page, module, or feature
3. **When** - Steps to reproduce
4. **Environment** - Version, OS, browser, PHP
5. **Why** - Why this is important

### Use Code Formatting

```markdown
Error message: `Error: Cannot find user`

Code snippet:
```php
$user = $this->getUser($id);
if (!$user) {
    echo "Error: Cannot find user";
}
```
```

### Include Screenshots

For UI issues, include:
- Screenshot of the problem
- Screenshot of expected behavior
- Annotate what's wrong (arrows, circles)

### Use Labels

Add labels to categorize:
- `bug` - Bug report
- `enhancement` - Enhancement request
- `documentation` - Documentation issue
- `help wanted` - Looking for help
- `good first issue` - Good for new contributors

---

## After Reporting

### Be Responsive

- Check for questions in the issue comments
- Provide additional information if requested
- Test suggested fixes
- Verify bug still exists with new versions

### Follow Etiquette

- Be respectful and professional
- Assume good intentions
- Don't demand fixes - developers are volunteers
- Offer to help if possible
- Thank contributors for their work

### Keep Issue Focused

- Stay on topic
- Don't discuss unrelated issues
- Link to related issues instead
- Don't use issues for feature voting

---

## What Happens to Issues

### Triage Process

1. **New issue created** - GitHub notifies maintainers
2. **Initial review** - Checked for clarity and duplicates
3. **Label assignment** - Categorized and prioritized
4. **Assignment** - Assigned to someone if appropriate
5. **Discussion** - Additional info gathered if needed

### Priority Levels

- **Critical** - Data loss, security, complete breakage
- **High** - Major feature broken, affects many users
- **Medium** - Part of feature broken, workaround available
- **Low** - Minor issue, cosmetic, or niche use case

### Resolution Outcomes

- **Fixed** - Issue resolved in a PR
- **Won't fix** - Rejected for technical or strategic reasons
- **Duplicate** - Same as another issue
- **Invalid** - Not actually an issue
- **Needs more info** - Waiting for additional details

---

## Issue Examples

### Example: Good Bug Report

```markdown
## Description
Admin users cannot delete items when using MySQL with strict mode enabled.

## Steps to Reproduce
1. Enable `sql_mode='STRICT_TRANS_TABLES'` in MySQL
2. Navigate to Publisher admin panel
3. Click delete button on any article
4. Error is shown

## Expected Behavior
Article should be deleted or show meaningful error.

## Actual Behavior
Error: "SQL Error - Unknown column 'deleted_at' in ON clause"

## Environment
- XOOPS Version: 2.5.12
- PHP Version: 8.1.5
- Database: MySQL 8.0.32 with STRICT_TRANS_TABLES
- Operating System: Ubuntu 22.04
- Browser: Firefox 120

## Screenshots
[Screenshot of error message]

## Additional Context
This only happens with strict SQL mode. Works fine with default settings.
The query is in class/PublisherItem.php:248

## Possible Fix
Use single quotes around 'deleted_at' or use backticks for all column names.
```

### Example: Good Feature Request

```markdown
## Description
Add REST API endpoints for read-only access to public content.

## Problem Statement
Developers want to build mobile apps and external services using XOOPS data.
Currently limited to SOAP API which is outdated and poorly documented.

## Proposed Solution
Implement RESTful API with:
- Endpoints for articles, users, comments (read-only)
- Token-based authentication
- Standard HTTP status codes and errors
- OpenAPI/Swagger documentation
- Pagination support

## Alternatives Considered
- Enhanced SOAP API (legacy, not standards-compliant)
- GraphQL (more complex, maybe future)

## Additional Context
See Publisher module API refactoring for similar patterns.
Would align with modern web development practices.

## Expected Impact
Enable ecosystem of third-party tools and mobile apps.
Would improve XOOPS adoption and ecosystem.
```

---

## Related Documentation

- [[Code-of-Conduct|Code of Conduct]]
- [[Contribution-Workflow|Contribution Workflow]]
- [[Pull-Request-Guidelines|Pull Request Guidelines]]
- [[../Contributing|Contributing Overview]]

---

#xoops #issues #bug-reporting #feature-requests #github
