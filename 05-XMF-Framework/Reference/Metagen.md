---
title: XMF Metagen Class
description: Meta tag generation and SEO helpers in the XMF Framework
tags:
  - xmf
  - seo
  - meta-tags
  - framework
  - html
  - search-engine-optimization
created: 2026-01-31
updated: 2026-01-31
version: 2026.01
---

# XMF Metagen Class

The `Metagen` class in the XMF Framework provides a comprehensive toolkit for generating and managing HTML meta tags, Open Graph tags, and other SEO-related metadata.

## Class Overview

The `Metagen` class handles:
- Standard HTML meta tags (description, keywords, etc.)
- Open Graph meta tags for social sharing
- Twitter Card meta tags
- Structured data and JSON-LD
- Canonical URLs
- Language and locale specifications

### Basic Class Structure

```php
namespace Xmf;

class Metagen
{
    protected $meta = [];
    protected $ogTags = [];
    protected $twitterTags = [];
    protected $jsonLd = [];
    protected $canonicalUrl;
    protected $language;

    public function __construct() {}

    public function setDescription(string $description): self {}

    public function setKeywords(string $keywords): self {}

    public function renderAll(): string {}
}
```

## Basic Usage

### Simple Meta Tags

```php
use Xmf\Metagen;

$metagen = new Metagen();

// Set basic meta tags
$metagen->setDescription('This is my awesome website');
$metagen->setKeywords('php, xoops, web development');

// Render to HTML
echo $metagen->renderAll();

// Output:
// <meta name="description" content="This is my awesome website" />
// <meta name="keywords" content="php, xoops, web development" />
```

## Open Graph Meta Tags

Open Graph tags help control how content appears when shared on social media.

### Basic Open Graph Setup

```php
$metagen = new Metagen();

$metagen->setOpenGraphProperty('og:title', 'My Awesome Article');
$metagen->setOpenGraphProperty('og:description', 'Learn how to use Metagen for SEO');
$metagen->setOpenGraphProperty('og:image', 'https://example.com/image.jpg');
$metagen->setOpenGraphProperty('og:url', 'https://example.com/article');
$metagen->setOpenGraphProperty('og:type', 'article');

echo $metagen->renderAll();
```

## Structured Data and JSON-LD

JSON-LD provides structured data that search engines can better understand.

### Article Structured Data

```php
$metagen = new Metagen();

$articleData = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'Understanding XOOPS 4.0',
    'description' => 'A comprehensive guide to XOOPS modernization',
    'image' => 'https://example.com/article.jpg',
    'datePublished' => '2026-01-31T10:00:00Z',
    'dateModified' => '2026-01-31T15:00:00Z',
    'author' => [
        '@type' => 'Person',
        'name' => 'John Developer',
        'url' => 'https://example.com/author'
    ]
];

$metagen->setJsonLd($articleData);

echo $metagen->renderAll();
```

## Module Integration Examples

### Blog/Article Module

```php
namespace MyModule\Controller;

use Xmf\Metagen;
use MyModule\Repository\ArticleRepository;

class ArticleController
{
    public function viewAction($id)
    {
        $repository = new ArticleRepository();
        $article = $repository->getById($id);

        if (!$article) {
            return $this->notFound();
        }

        // Initialize Metagen
        $metagen = new Metagen();

        // Set article metadata
        $metagen->setTitle($article->getTitle());
        $metagen->setDescription(
            substr($article->getBody(), 0, 160)
        );
        $metagen->setKeywords(
            implode(', ', $article->getTags())
        );
        $metagen->setAuthor($article->getAuthorName());

        // Open Graph
        $metagen->setOpenGraphProperty('og:type', 'article');
        $metagen->setOpenGraphProperty('og:title', $article->getTitle());
        $metagen->setOpenGraphProperty('og:description', $article->getExcerpt());
        $metagen->setOpenGraphProperty('og:image', $article->getFeaturedImage());
        $metagen->setOpenGraphProperty('og:url', $article->getUrl());

        // Canonical URL
        $metagen->setCanonicalUrl($article->getUrl());

        // Store in template
        $this->template['metagen'] = $metagen;

        return $this->render('article/view.php');
    }
}
```

## Template Integration

### Template Implementation

```php
<!-- In your template header -->
<?php if (isset($metagen)): ?>
    <?php echo $metagen->renderAll(); ?>
<?php endif; ?>

<!-- Standard HTML structure -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <?php echo $metagen->renderAll(); ?>
    <title><?php echo $metagen->getTitle(); ?></title>
</head>
<body>
    <!-- Content -->
</body>
</html>
```

## Best Practices

### SEO Optimization

1. **Unique descriptions** for each page (150-160 characters)
2. **Relevant keywords** (5-10 primary keywords per page)
3. **Canonical URLs** for preventing duplicate content
4. **Open Graph tags** for social media optimization
5. **Structured data** for enhanced search results
6. **Mobile viewport** meta tag for responsive design

### Complete SEO Implementation

```php
$metagen = new Metagen();

// Basic meta tags
$metagen->setTitle('My Website - Web Development Services');
$metagen->setDescription('Professional web development services');
$metagen->setKeywords('web development, php, xoops');
$metagen->setAuthor('John Developer');
$metagen->setLanguage('en');

// Canonical URL
$metagen->setCanonicalUrl('https://example.com/services/web-development');

// Open Graph for social sharing
$metagen->setOpenGraphProperty('og:title', 'Web Development Services');
$metagen->setOpenGraphProperty('og:description', 'Professional services');
$metagen->setOpenGraphProperty('og:image', 'https://example.com/og-image.jpg');
$metagen->setOpenGraphProperty('og:url', 'https://example.com/services/web-development');
$metagen->setOpenGraphProperty('og:type', 'website');

// Twitter Card
$metagen->setTwitterCard('summary_large_image');
$metagen->setTwitterProperty('twitter:site', '@mycompany');
$metagen->setTwitterProperty('twitter:title', 'Web Development Services');
$metagen->setTwitterProperty('twitter:image', 'https://example.com/twitter-image.jpg');

echo $metagen->renderAll();
```

## API Reference

### Core Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `setTitle()` | string | self | Set page title |
| `setDescription()` | string | self | Set meta description |
| `setKeywords()` | string | self | Set meta keywords |
| `setAuthor()` | string | self | Set author name |
| `setCanonicalUrl()` | string | self | Set canonical URL |
| `setLanguage()` | string | self | Set page language |
| `setViewport()` | string | self | Set viewport settings |
| `setOpenGraphProperty()` | string, string | self | Add OG tag |
| `setTwitterCard()` | string | self | Set Twitter card type |
| `setJsonLd()` | array | self | Set structured data |
| `renderAll()` | - | string | Render all meta tags |

## Related Documentation

- [[Database]] - XMF database reference
- [[JWT]] - JWT authentication in XMF
- [[../../03-Module-Development/Best-Practices/Frontend-Integration]] - Frontend integration best practices

## Resources

- [Open Graph Protocol](https://ogp.me/)
- [Twitter Card Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Schema.org Structured Data](https://schema.org/)
- [Google Search Central](https://developers.google.com/search)

## Version Information

- **Introduced:** XOOPS 2.5.8
- **Last Updated:** XOOPS 4.0
- **Compatibility:** PHP 8.2+
