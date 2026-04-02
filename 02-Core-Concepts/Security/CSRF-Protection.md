---
title: CSRF Protection
description: Understanding and implementing CSRF protection in XOOPS using XoopsSecurity class
tags:
  - security
  - csrf
  - xoops
  - forms
  - tokens
created: 2025-01-28
updated: 2025-01-28
---

# CSRF Protection

<span class="version-badge version-25x">2.5.x ✅</span> <span class="version-badge version-40x">4.0.x ✅</span>

Cross-Site Request Forgery (CSRF) attacks trick users into performing unwanted actions on a site where they are authenticated. XOOPS provides built-in CSRF protection through the `XoopsSecurity` class.

## Related Documentation

- [[Security-Best-Practices]] - Comprehensive security guide
- [[Input-Sanitization]] - MyTextSanitizer and validation
- [[SQL-Injection-Prevention]] - Database security practices

## Understanding CSRF Attacks

A CSRF attack occurs when:

1. A user is authenticated on your XOOPS site
2. The user visits a malicious website
3. The malicious site submits a request to your XOOPS site using the user's session
4. Your site processes the request as if it came from the legitimate user

## The XoopsSecurity Class

XOOPS provides the `XoopsSecurity` class to protect against CSRF attacks. This class manages security tokens that must be included in forms and verified when processing requests.

### Token Generation

The security class generates unique tokens that are stored in the user's session and must be included in forms:

```php
$security = new XoopsSecurity();

// Get token HTML input field
$tokenHTML = $security->getTokenHTML();

// Get just the token value
$tokenValue = $security->createToken();
```

### Token Verification

When processing form submissions, verify that the token is valid:

```php
$security = new XoopsSecurity();

if (!$security->check()) {
    redirect_header('index.php', 3, _MD_TOKENEXPIRED);
    exit();
}
```

## Using XOOPS Token System

### With XoopsForm Classes

When using XOOPS form classes, token protection is straightforward:

```php
// Create a form
$form = new XoopsThemeForm('Add Item', 'form_name', 'submit.php');

// Add form elements
$form->addElement(new XoopsFormText('Title', 'title', 50, 255, ''));
$form->addElement(new XoopsFormTextArea('Content', 'content', ''));

// Add hidden token field - ALWAYS include this
$form->addElement(new XoopsFormHiddenToken());

// Add submit button
$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
```

### With Custom Forms

For custom HTML forms that do not use XoopsForm:

```php
// In your form template or PHP file
$security = new XoopsSecurity();
?>
<form method="post" action="submit.php">
    <input type="text" name="title" />
    <textarea name="content"></textarea>

    <!-- Include the token -->
    <?php echo $security->getTokenHTML(); ?>

    <button type="submit">Submit</button>
</form>
```

### In Smarty Templates

When generating forms in Smarty templates:

```php
// In your PHP file
$security = new XoopsSecurity();
$GLOBALS['xoopsTpl']->assign('token', $security->getTokenHTML());
```

```smarty
{* In your template *}
<form method="post" action="submit.php">
    <input type="text" name="title" />
    <textarea name="content"></textarea>

    {* Include the token *}
    <{$token}>

    <button type="submit">Submit</button>
</form>
```

## Processing Form Submissions

### Basic Token Verification

```php
// In your form processing script
$security = new XoopsSecurity();

// Verify the token
if (!$security->check()) {
    redirect_header('index.php', 3, _MD_TOKENEXPIRED);
    exit();
}

// Token is valid, process the form
$title = $_POST['title'];
// ... continue processing
```

### With Custom Error Handling

```php
$security = new XoopsSecurity();

if (!$security->check()) {
    // Get detailed error information
    $errors = $security->getErrors();

    // Log the error
    error_log('CSRF token validation failed: ' . implode(', ', $errors));

    // Redirect with error message
    redirect_header('form.php', 3, 'Security token expired. Please try again.');
    exit();
}
```

### For AJAX Requests

When working with AJAX requests, include the token in your request:

```javascript
// JavaScript - get token from hidden field
var token = document.querySelector('input[name="XOOPS_TOKEN_REQUEST"]').value;

// Include in AJAX request
fetch('ajax_handler.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=save&XOOPS_TOKEN_REQUEST=' + encodeURIComponent(token)
});
```

```php
// PHP AJAX handler
$security = new XoopsSecurity();

if (!$security->check()) {
    echo json_encode(['error' => 'Invalid security token']);
    exit();
}

// Process AJAX request
```

## Checking HTTP Referer

For additional protection, especially for AJAX requests, you can also check the HTTP referer:

```php
$security = new XoopsSecurity();

// Check referer header
if (!$security->checkReferer()) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Also verify the token
if (!$security->check()) {
    echo json_encode(['error' => 'Invalid token']);
    exit();
}
```

### Combined Security Check

```php
$security = new XoopsSecurity();

// Perform both checks
if (!$security->checkReferer() || !$security->check()) {
    redirect_header('index.php', 3, 'Security validation failed');
    exit();
}
```

## Token Configuration

### Token Lifetime

Tokens have a limited lifetime to prevent replay attacks. You can configure this in XOOPS settings or handle expired tokens gracefully:

```php
$security = new XoopsSecurity();

if (!$security->check()) {
    // Token may have expired
    // Regenerate form with new token
    redirect_header('form.php', 3, 'Your session has expired. Please submit the form again.');
    exit();
}
```

### Multiple Forms on Same Page

When you have multiple forms on the same page, each should have its own token:

```php
// Form 1
$form1 = new XoopsThemeForm('Form 1', 'form1', 'submit1.php');
$form1->addElement(new XoopsFormHiddenToken('token1'));

// Form 2
$form2 = new XoopsThemeForm('Form 2', 'form2', 'submit2.php');
$form2->addElement(new XoopsFormHiddenToken('token2'));
```

## Best Practices

### Always Use Tokens for State-Changing Operations

Include tokens in any form that:

- Creates data
- Updates data
- Deletes data
- Changes user settings
- Performs any administrative action

### Do Not Rely Solely on Referer Checking

The HTTP referer header can be:

- Stripped by privacy tools
- Missing in some browsers
- Spoofed in some cases

Always use token verification as your primary defense.

### Regenerate Tokens Appropriately

Consider regenerating tokens:

- After successful form submission
- After login/logout
- At regular intervals for long sessions

### Handle Token Expiration Gracefully

```php
$security = new XoopsSecurity();

if (!$security->check()) {
    // Store form data temporarily
    $_SESSION['form_backup'] = $_POST;

    // Redirect back to form with message
    redirect_header('form.php?restore=1', 3, 'Please resubmit the form.');
    exit();
}
```

## Common Issues and Solutions

### Token Not Found Error

**Problem**: Security check fails with "token not found"

**Solution**: Ensure the token field is included in your form:

```php
$form->addElement(new XoopsFormHiddenToken());
```

### Token Expired Error

**Problem**: Users see "token expired" after long form completion

**Solution**: Consider using JavaScript to refresh the token periodically:

```javascript
// Refresh token every 10 minutes
setInterval(function() {
    fetch('refresh_token.php')
        .then(response => response.json())
        .then(data => {
            document.querySelector('input[name="XOOPS_TOKEN_REQUEST"]').value = data.token;
        });
}, 600000);
```

### AJAX Token Issues

**Problem**: AJAX requests fail token validation

**Solution**: Ensure the token is passed with every AJAX request and verify it server-side:

```php
// AJAX handler
header('Content-Type: application/json');

$security = new XoopsSecurity();
if (!$security->check(true, false)) { // Don't clear token for AJAX
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit();
}
```

## Example: Complete Form Implementation

```php
<?php
// form.php
require_once dirname(__DIR__) . '/mainfile.php';

$security = new XoopsSecurity();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$security->check()) {
        redirect_header('form.php', 3, 'Security token expired. Please try again.');
        exit();
    }

    // Process valid submission
    $title = $myts->htmlSpecialChars($_POST['title']);
    // ... save to database

    redirect_header('success.php', 3, 'Item saved successfully!');
    exit();
}

// Display form
$GLOBALS['xoopsOption']['template_main'] = 'mymodule_form.tpl';
include XOOPS_ROOT_PATH . '/header.php';

$form = new XoopsThemeForm('Add Item', 'add_item', 'form.php');
$form->addElement(new XoopsFormText('Title', 'title', 50, 255, ''));
$form->addElement(new XoopsFormTextArea('Content', 'content', ''));
$form->addElement(new XoopsFormHiddenToken());
$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));

$GLOBALS['xoopsTpl']->assign('form', $form->render());

include XOOPS_ROOT_PATH . '/footer.php';
```

---

#security #csrf #xoops #forms #tokens #XoopsSecurity
