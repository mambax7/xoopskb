---
title: Creating Your First Page
description: Step-by-step guide to creating and publishing content in XOOPS, including formatting, media embedding, and publishing options
created: 2025-01-28
updated: 2025-01-28
version: 2.5.8
category: First Steps
---

# Creating Your First Page in XOOPS

Learn how to create, format, and publish your first piece of content in XOOPS.

## Understanding XOOPS Content

### What is a Page/Post?

In XOOPS, content is managed through modules. The most common content types are:

| Type | Description | Use Case |
|---|---|---|
| **Page** | Static content | About us, Contact, Services |
| **Post/Article** | Time-stamped content | News, Blog posts |
| **Category** | Content organization | Group related content |
| **Comment** | User feedback | Allow visitor interaction |

This guide covers creating a basic page/article using XOOPS' default content module.

## Accessing the Content Editor

### From Admin Panel

1. Log in to admin panel: `http://your-domain.com/xoops/admin/`
2. Navigate to **Content > Pages** (or your content module)
3. Click "Add New Page" or "New Post"

### Frontend (if Enabled)

If your XOOPS is configured to allow frontend content creation:

1. Log in as registered user
2. Go to your profile
3. Look for "Submit Content" option
4. Follow the same steps below

## Content Editor Interface

The content editor includes:

```
┌─────────────────────────────────────┐
│ Content Editor                      │
├─────────────────────────────────────┤
│                                     │
│ Title: [________________]           │
│                                     │
│ Category: [Dropdown]                │
│                                     │
│ [B I U] [Link] [Image] [Video]    │
│ ┌─────────────────────────────────┐ │
│ │ Enter your content here...      │ │
│ │                                 │ │
│ │ You can use HTML tags here      │ │
│ └─────────────────────────────────┘ │
│                                     │
│ Description (Meta): [____________]  │
│                                     │
│ [Publish] [Save Draft] [Preview]   │
│                                     │
└─────────────────────────────────────┘
```

## Step-by-Step Guide: Creating Your First Page

### Step 1: Access Content Editor

1. In admin panel, click **Content > Pages**
2. Click **"Add New Page"** or **"Create"**
3. You'll see the content editor

### Step 2: Enter Page Title

In the "Title" field, enter your page name:

```
Title: Welcome to Our Website
```

Best practices for titles:
- Clear and descriptive
- Include keywords if possible
- 50-60 characters ideal
- Avoid ALL CAPS (hard to read)
- Be specific (not "Page 1")

### Step 3: Select Category

Choose where to organize this content:

```
Category: [Dropdown ▼]
```

Options might include:
- General
- News
- Blog
- Announcements
- Services

If categories don't exist, ask administrator to create them.

### Step 4: Write Your Content

Click in the content editor area and type your text.

#### Basic Text Formatting

Use the editor toolbar:

| Button | Action | Result |
|---|---|---|
| **B** | Bold | **Bold text** |
| *I* | Italic | *Italic text* |
| <u>U</u> | Underline | <u>Underlined text</u> |

#### Using HTML

XOOPS allows safe HTML tags. Common examples:

```html
<!-- Paragraphs -->
<p>This is a paragraph.</p>

<!-- Headings -->
<h1>Main Heading</h1>
<h2>Subheading</h2>

<!-- Lists -->
<ul>
  <li>Item 1</li>
  <li>Item 2</li>
  <li>Item 3</li>
</ul>

<!-- Bold and Italic -->
<strong>Bold text</strong>
<em>Italic text</em>

<!-- Links -->
<a href="https://example.com">Link text</a>

<!-- Line breaks -->
<br>

<!-- Horizontal rule -->
<hr>
```

#### Safe HTML Examples

**Recommended tags:**
- Paragraphs: `<p>`, `<br>`
- Headings: `<h1>` to `<h6>`
- Text: `<strong>`, `<em>`, `<u>`
- Lists: `<ul>`, `<ol>`, `<li>`
- Links: `<a href="">`
- Blockquotes: `<blockquote>`
- Tables: `<table>`, `<tr>`, `<td>`

**Avoid these tags** (may be disabled for security):
- Scripts: `<script>`
- Styles: `<style>`
- Iframes: `<iframe>` (unless configured)
- Forms: `<form>`, `<input>`

### Step 5: Add Images

#### Option 1: Insert Image URL

Using the editor:

1. Click **Insert Image** button (image icon)
2. Enter image URL: `https://example.com/image.jpg`
3. Enter alt text: "Description of image"
4. Click "Insert"

HTML equivalent:

```html
<img src="https://example.com/image.jpg" alt="Description">
```

#### Option 2: Upload Image

1. Upload image to XOOPS first:
   - Go to **Content > Media Manager**
   - Upload your image
   - Copy the image URL

2. In content editor, insert using URL (above steps)

#### Image Best Practices

- Use appropriate file sizes (optimize images)
- Use descriptive filenames
- Always include alt text (accessibility)
- Supported formats: JPG, PNG, GIF, WebP
- Recommended width: 600-800 pixels for content

### Step 6: Embed Media

#### Embed Video from YouTube

```html
<iframe width="560" height="315"
  src="https://www.youtube.com/embed/VIDEO_ID"
  frameborder="0" allowfullscreen>
</iframe>
```

Replace `VIDEO_ID` with the YouTube video ID.

**To find YouTube video ID:**
1. Open video on YouTube
2. URL is: `https://www.youtube.com/watch?v=VIDEO_ID`
3. Copy the ID (characters after `v=`)

#### Embed Video from Vimeo

```html
<iframe src="https://player.vimeo.com/video/VIDEO_ID"
  width="640" height="360" frameborder="0"
  allow="autoplay; fullscreen" allowfullscreen>
</iframe>
```

### Step 7: Add Meta Description

In the "Description" field, add a brief summary:

```
Description: Learn how to get started with our website.
This page provides an overview of our services and how we can help you.
```

**Meta description best practices:**
- 150-160 characters
- Include main keywords
- Should accurately summarize content
- Used in search engine results
- Make it compelling (users see this)

### Step 8: Configure Publishing Options

#### Publish Status

Choose publication status:

```
Status: ☑ Published
```

Options:
- **Published:** Visible to public
- **Draft:** Only visible to admins
- **Pending Review:** Awaiting approval
- **Archived:** Hidden but kept

#### Visibility

Set who can see this content:

```
Visibility: ☐ Public
           ☐ Registered Users Only
           ☐ Private (Admin Only)
```

#### Publication Date

Set when content becomes visible:

```
Publish Date: [Date Picker] [Time]
```

Leave as "Now" to publish immediately.

#### Allow Comments

Enable or disable visitor comments:

```
Allow Comments: ☑ Yes
```

If enabled, visitors can add feedback.

### Step 9: Save Your Content

Multiple save options:

```
[Publish Now]  [Save as Draft]  [Schedule]  [Preview]
```

- **Publish Now:** Make visible immediately
- **Save as Draft:** Keep private for now
- **Schedule:** Publish at future date/time
- **Preview:** See how it looks before saving

Click your choice:

```
Click [Publish Now]
```

### Step 10: Verify Your Page

After publishing, verify your content:

1. Go to your website homepage
2. Navigate to your content area
3. Look for your newly created page
4. Click to view it
5. Check:
   - [ ] Content displays correctly
   - [ ] Images appear
   - [ ] Formatting looks good
   - [ ] Links work
   - [ ] Title and description correct

## Example: Complete Page

### Title
```
Getting Started with XOOPS
```

### Content
```html
<h2>Welcome to XOOPS</h2>

<p>XOOPS is a powerful and flexible open-source
content management system. It allows you to build
dynamic websites with minimal technical knowledge.</p>

<h3>Key Features</h3>

<ul>
  <li>Easy content management</li>
  <li>User registration and management</li>
  <li>Module system for extensibility</li>
  <li>Flexible theming system</li>
  <li>Built-in security features</li>
</ul>

<h3>Getting Started</h3>

<p>Here are the first steps to get your XOOPS site
running:</p>

<ol>
  <li>Configure basic settings</li>
  <li>Create your first page</li>
  <li>Set up user accounts</li>
  <li>Install additional modules</li>
  <li>Customize appearance</li>
</ol>

<img src="https://example.com/xoops-logo.jpg"
  alt="XOOPS Logo">

<p>For more information, visit
<a href="https://xoops.org/">xoops.org</a></p>
```

### Meta Description
```
Get started with XOOPS CMS. Learn about features
and the first steps to launch your dynamic website.
```

## Advanced Content Features

### Using WYSIWYG Editor

If a rich text editor is installed:

```
[B] [I] [U] [Link] [Image] [Code] [Quote]
```

Click buttons to format text without HTML.

### Inserting Code Blocks

Display code examples:

```html
<pre><code>
// PHP Example
$variable = "Hello World";
echo $variable;
</code></pre>
```

### Creating Tables

Organize data in tables:

```html
<table border="1" cellpadding="5">
  <tr>
    <th>Feature</th>
    <th>Description</th>
  </tr>
  <tr>
    <td>Flexible</td>
    <td>Easy to customize</td>
  </tr>
  <tr>
    <td>Powerful</td>
    <td>Full-featured CMS</td>
  </tr>
</table>
```

### Inline Quotes

Highlight important text:

```html
<blockquote>
"XOOPS is a powerful content management system
that empowers you to build dynamic websites."
</blockquote>
```

## SEO Best Practices for Content

Optimize your content for search engines:

### Title
- Include main keyword
- 50-60 characters
- Unique per page

### Meta Description
- Include keyword naturally
- 150-160 characters
- Compelling and accurate

### Content
- Write naturally, avoid keyword stuffing
- Use headings (h2, h3) appropriately
- Include internal links to other pages
- Use alt text on all images
- Aim for 300+ words for articles

### URL Structure
- Keep URLs short and descriptive
- Use hyphens to separate words
- Avoid special characters
- Example: `/about-our-company`

## Managing Your Content

### Edit Existing Page

1. Go to **Content > Pages**
2. Find your page in the list
3. Click **Edit** or the page title
4. Make changes
5. Click **Update**

### Delete Page

1. Go to **Content > Pages**
2. Find your page
3. Click **Delete**
4. Confirm deletion

### Change Publication Status

1. Go to **Content > Pages**
2. Find page, click **Edit**
3. Change status in dropdown
4. Click **Update**

## Troubleshooting Content Creation

### Content Not Appearing

**Symptom:** Published page not showing on website

**Solution:**
1. Check publication status: Should be "Published"
2. Check publish date: Should be current or past
3. Check visibility: Should be "Public"
4. Clear cache: Admin > Tools > Clear Cache
5. Check permissions: User group must have access

### Formatting Not Working

**Symptom:** HTML tags or formatting appear as text

**Solution:**
1. Verify HTML is enabled in module settings
2. Use proper HTML syntax
3. Close all tags: `<p>Text</p>`
4. Use allowed tags only
5. Use HTML entities: `&lt;` for `<`, `&amp;` for `&`

### Images Not Displaying

**Symptom:** Images show broken icon

**Solution:**
1. Verify image URL is correct
2. Check image file exists
3. Verify proper permissions on image
4. Try uploading image to XOOPS instead
5. Check for external blocking (may need CORS)

### Character Encoding Issues

**Symptom:** Special characters appear as gibberish

**Solution:**
1. Save file as UTF-8 encoding
2. Ensure page charset is UTF-8
3. Add to HTML head: `<meta charset="UTF-8">`
4. Avoid copy-pasting from Word (use plain text)

## Content Workflow Best Practices

### Recommended Process

1. **Write in Editor First:** Use admin content editor
2. **Preview Before Publishing:** Click Preview button
3. **Add Metadata:** Complete title, description, tags
4. **Save as Draft First:** Save as draft to avoid losing work
5. **Final Review:** Re-read before publishing
6. **Publish:** Click Publish when ready
7. **Verify:** Check on live site
8. **Edit if Needed:** Make corrections quickly

### Version Control

Always keep backups:

1. **Before Major Changes:** Save as new version or backup
2. **Archive Old Content:** Keep unpublished versions
3. **Date Your Drafts:** Use clear naming: "Page-Draft-2025-01-28"

## Publishing Multiple Pages

Create a content strategy:

```
Homepage
├── About Us
├── Services
│   ├── Service 1
│   ├── Service 2
│   └── Service 3
├── Blog
│   ├── Article 1
│   ├── Article 2
│   └── Article 3
├── Contact
└── FAQ
```

Create pages to follow this structure.

## Next Steps

After creating your first page:

1. [[Managing-Users|Set up user accounts]]
2. [[Installing-Modules|Install additional modules]]
3. [[Admin-Panel-Overview|Explore admin features]]
4. [[../Configuration/Basic-Configuration|Configure settings]]
5. Optimize with [[../Configuration/Performance-Optimization|performance settings]]

---

**Tags:** #content-creation #pages #publishing #editor

**Related Articles:**
- [[Admin-Panel-Overview]]
- [[Managing-Users]]
- [[Installing-Modules]]
- [[../Configuration/Basic-Configuration]]
