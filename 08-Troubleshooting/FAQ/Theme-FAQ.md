---
title: Theme FAQ
description: Frequently asked questions about XOOPS themes
created: 2025-01-31
updated: 2025-01-31
version: 1.0.0
category: FAQ
---

# Theme Frequently Asked Questions

> Common questions and answers about XOOPS themes, customization, and management.

---

## Theme Installation & Activation

### Q: How do I install a new theme in XOOPS?

**A:**
1. Download the theme zip file
2. Go to XOOPS Admin > Appearance > Themes
3. Click "Upload" and select the zip file
4. The theme appears in the theme list
5. Click to activate it for your site

Alternative: Extract manually into `/themes/` directory and refresh admin panel.

---

### Q: Theme upload fails with "Permission denied"

**A:** Fix theme directory permissions:

```bash
# Make themes directory writable
chmod 755 /path/to/xoops/themes

# Fix uploads if uploading
chmod 777 /path/to/xoops/uploads

# Fix ownership if needed
chown -R www-data:www-data /path/to/xoops/themes
```

---

### Q: How do I set a different theme for specific users?

**A:**
1. Go to User Manager > Edit User
2. Go to "Other" tab
3. Select preferred theme in "User Theme" dropdown
4. Save

User-selected themes override the default site theme.

---

### Q: Can I have different themes for admin and user sites?

**A:** Yes, set in XOOPS Admin > Settings:

1. **Frontend theme** - Default site theme
2. **Admin theme** - Admin control panel theme (usually separate)

Look for settings like:
- `theme_set` - Frontend theme
- `admin_theme` - Admin theme

---

## Theme Customization

### Q: How do I customize an existing theme?

**A:** Create a child theme to preserve updates:

```
themes/
├── original_theme/
│   ├── style.css
│   ├── templates/
│   └── images/
└── custom_theme/          {* Create copy for editing *}
    ├── style.css
    ├── templates/
    └── images/
```

Then edit `theme.html` in your custom theme.

---

### Q: How do I change the theme colors?

**A:** Edit the theme's CSS file:

```bash
# Locate theme CSS
themes/mytheme/style.css

# Or theme template
themes/mytheme/theme.html
```

For XOOPS themes:

```css
/* themes/mytheme/style.css */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --accent-color: #e74c3c;
}

body {
    background-color: var(--primary-color);
    color: #333;
}

a {
    color: var(--secondary-color);
}

.button {
    background-color: var(--accent-color);
}
```

---

### Q: How do I add custom CSS to a theme?

**A:** Several options:

**Option 1: Edit theme.html**
```html
<!-- themes/mytheme/theme.html -->
<head>
    {* Existing CSS *}
    <link rel="stylesheet" href="{$xoops_url}/themes/{$xoops_theme}/custom.css">
</head>
```

**Option 2: Create custom.css**
```bash
# Create file
themes/mytheme/custom.css

# Add your styles
body { background: #fff; }
```

**Option 3: Admin Settings (if supported)**
Go to XOOPS Admin > Settings > Theme Settings and add custom CSS.

---

### Q: How do I modify theme HTML templates?

**A:** Locate the template file:

```bash
# List theme templates
ls -la themes/mytheme/templates/

# Common templates
themes/mytheme/templates/theme.html      {* Main layout *}
themes/mytheme/templates/header.html     {* Header *}
themes/mytheme/templates/footer.html     {* Footer *}
themes/mytheme/templates/sidebar.html    {* Sidebar *}
```

Edit with proper Smarty syntax:

```html
<!-- themes/mytheme/templates/theme.html -->
{* XOOPS Theme Template *}
<!DOCTYPE html>
<html>
<head>
    <meta charset="{$xoops_charset}">
    <title>{$xoops_pagetitle}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{$xoops_url}/themes/{$xoops_theme}/style.css">
</head>
<body>
    <header>
        {include file="file:header.html"}
    </header>

    <main>
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    {$xoops_contents}
                </div>
                <aside class="col-md-3">
                    {include file="file:sidebar.html"}
                </aside>
            </div>
        </div>
    </main>

    <footer>
        {include file="file:footer.html"}
    </footer>
</body>
</html>
```

---

## Theme Structure

### Q: What files are required in a theme?

**A:** Minimum structure:

```
themes/mytheme/
├── theme.html              {* Main template (required) *}
├── style.css              {* Stylesheet (optional but recommended) *}
├── screenshot.png         {* Preview image for admin (optional) *}
├── images/                {* Theme images *}
│   └── logo.png
└── templates/             {* Optional: Additional templates *}
    ├── header.html
    ├── footer.html
    └── sidebar.html
```

See [[../../02-Core-Concepts/Themes/Theme-Structure|Theme Structure]] for details.

---

### Q: How do I create a theme from scratch?

**A:** Create the structure:

```bash
mkdir -p themes/mytheme/images
cd themes/mytheme
```

Create `theme.html`:
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="{$xoops_charset}">
    <title>{$xoops_pagetitle}</title>
    <link rel="stylesheet" href="{$xoops_url}/themes/{$xoops_theme}/style.css">
</head>
<body>
    <header>{$xoops_headers}</header>
    <main>{$xoops_contents}</main>
    <footer>{$xoops_footers}</footer>
</body>
</html>
```

Create `style.css`:
```css
* { margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; }
header { background: #333; color: #fff; padding: 20px; }
main { padding: 20px; }
footer { background: #f5f5f5; padding: 20px; border-top: 1px solid #ddd; }
```

---

## Theme Variables

### Q: What variables are available in theme templates?

**A:** Common XOOPS theme variables:

```smarty
{* Site Information *}
{$xoops_sitename}          {* Site name *}
{$xoops_url}               {* Site URL *}
{$xoops_theme}             {* Current theme name *}

{* Page Content *}
{$xoops_contents}          {* Main page content *}
{$xoops_pagetitle}         {* Page title *}
{$xoops_headers}           {* Meta tags, styles in head *}

{* Module Information *}
{$xoops_module_header}     {* Module-specific header *}
{$xoops_moduledesc}        {* Module description *}

{* User Information *}
{$xoops_isuser}            {* Is user logged in? *}
{$xoops_userid}            {* User ID *}
{$xoops_uname}             {* Username *}

{* Blocks *}
{$xoops_blocks}            {* All block content *}

{* Other *}
{$xoops_charset}           {* Document charset *}
{$xoops_version}           {* XOOPS version *}
```

---

### Q: How do I add custom variables to my theme?

**A:** In your PHP code before rendering:

```php
<?php
// In module or admin code
require_once XOOPS_ROOT_PATH . '/class/xoopstpl.php';
$xoopsTpl = new XoopsTpl();

// Add custom variables
$xoopsTpl->assign('my_variable', 'value');
$xoopsTpl->assign('data_array', ['key1' => 'val1', 'key2' => 'val2']);

// Use in theme template
$xoopsTpl->display('file:theme.html');
?>
```

In theme:
```smarty
<p>{$my_variable}</p>
<p>{$data_array.key1}</p>
```

---

## Theme Styling

### Q: How do I make my theme responsive?

**A:** Use CSS Grid or Flexbox:

```css
/* themes/mytheme/style.css */

/* Mobile first approach */
body {
    font-size: 14px;
}

.container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

main {
    order: 2;
}

aside {
    order: 3;
}

/* Tablet and up */
@media (min-width: 768px) {
    .container {
        grid-template-columns: 2fr 1fr;
    }
}

/* Desktop and up */
@media (min-width: 1200px) {
    .container {
        grid-template-columns: 3fr 1fr;
    }
}
```

Or use Bootstrap integration:
```html
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<div class="container">
    <div class="row">
        <div class="col-md-9">{$xoops_contents}</div>
        <div class="col-md-3">{* Sidebar *}</div>
    </div>
</div>
```

---

### Q: How do I add a dark mode to my theme?

**A:**
```css
/* themes/mytheme/style.css */

/* Light mode (default) */
:root {
    --bg-color: #ffffff;
    --text-color: #000000;
    --border-color: #cccccc;
}

body {
    background-color: var(--bg-color);
    color: var(--text-color);
    transition: background-color 0.3s, color 0.3s;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    :root {
        --bg-color: #1a1a1a;
        --text-color: #ffffff;
        --border-color: #444444;
    }
}

/* Or with CSS class */
body.dark-mode {
    --bg-color: #1a1a1a;
    --text-color: #ffffff;
    --border-color: #444444;
}
```

Toggle with JavaScript:
```html
<script>
document.getElementById('dark-mode-toggle').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
});

// Load preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}
</script>
```

---

## Theme Issues

### Q: Theme shows "unrecognized template variable" errors

**A:** The variable isn't being passed to the template. Check:

1. **Variable is assigned** in PHP:
```php
<?php
$xoopsTpl->assign('variable_name', $value);
?>
```

2. **Template exists** where specified
3. **Template syntax is correct**:
```smarty
{* Correct *}
{$variable_name}

{* Wrong *}
$variable_name
{variable_name}
```

---

### Q: CSS changes don't appear in browser

**A:** Clear browser cache:

1. Hard refresh: `Ctrl+Shift+R` (Cmd+Shift+R on Mac)
2. Clear theme cache on server:
```bash
rm -rf xoops_data/caches/smarty_cache/themes/*
rm -rf xoops_data/caches/smarty_compile/themes/*
```

3. Check CSS file path in theme:
```bash
ls -la themes/mytheme/style.css
```

---

### Q: Images in theme don't load

**A:** Check image paths:

```html
{* WRONG - relative path from web root *}
<img src="themes/mytheme/images/logo.png">

{* CORRECT - use xoops_url *}
<img src="{$xoops_url}/themes/{$xoops_theme}/images/logo.png">

{* Or in CSS *}
background-image: url('{$xoops_url}/themes/{$xoops_theme}/images/bg.png');
```

---

### Q: Theme templates missing or causing errors

**A:** See [[../Common-Issues/Template-Errors|Template Errors]] for debugging.

---

## Theme Distribution

### Q: How do I package a theme for distribution?

**A:** Create a distributable zip:

```bash
# Structure
mytheme/
├── theme.html           {* Required *}
├── style.css
├── screenshot.png       {* 300x225 recommended *}
├── README.txt
├── LICENSE
├── images/
│   ├── logo.png
│   └── favicon.ico
└── templates/           {* Optional *}
    ├── header.html
    └── footer.html

# Create zip
zip -r mytheme.zip mytheme/
```

---

### Q: Can I sell my XOOPS theme?

**A:** Check XOOPS license:
- Themes using XOOPS classes/templates must respect XOOPS license
- Pure CSS/HTML themes have fewer restrictions
- Check [[../../09-Contributing/Contributing|XOOPS Contributing Guidelines]] for details

---

## Theme Performance

### Q: How do I optimize theme performance?

**A:**
1. **Minimize CSS/JS** - Remove unused code
2. **Optimize images** - Use proper formats (WebP, AVIF)
3. **Use CDN** for resources
4. **Lazy load** images:
```html
<img src="image.jpg" loading="lazy">
```

5. **Cache-bust versions**:
```html
<link rel="stylesheet" href="{$xoops_url}/themes/{$xoops_theme}/style.css?v={$xoops_version}">
```

See [[../FAQ/Performance-FAQ|Performance FAQ]] for more details.

---

## Related Documentation

- [[../Common-Issues/Template-Errors|Template Errors]]
- [[../../02-Core-Concepts/Themes/Theme-Structure|Theme Structure]]
- [[../FAQ/Performance-FAQ|Performance FAQ]]
- [[../Debugging/Smarty-Debugging|Smarty Debugging]]

---

#xoops #themes #faq #troubleshooting #customization
