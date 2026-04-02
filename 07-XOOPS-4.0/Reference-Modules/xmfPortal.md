---
title: "xmfPortal: Reference Module"
description: Architecture map of xmfPortal showing widget system, ratings, and event listener patterns
created: 2026-04-02
updated: 2026-04-02
---

# xmfPortal Reference Module

> Page builder with widget composition, demonstrating XMF 2.0 presentation layer and event listeners.

xmfPortal is the companion reference module to xmfBlog. Where xmfBlog showcases backend depth (25+ components, async, API), xmfPortal showcases the **presentation layer** -- widgets, ratings, DataProviders, and dedicated event listener classes.

**Source:** `htdocs/modules/xmfportal/`
**Requirements:** PHP 8.4+, XOOPS 2.5.11+

---

## Architecture

```
                    ┌─────────────────────────────────────────┐
                    │            PortalModule.php             │
                    │         (DI Container Bootstrap)        │
                    └────────────────┬────────────────────────┘
                                     │
          ┌──────────────────────────┼──────────────────────────┐
          │                          │                          │
    ┌─────▼──────┐           ┌──────▼───────┐          ┌──────▼───────┐
    │  Admin UI  │           │  Frontend    │          │   REST API   │
    │  11 pages  │           │  index.php   │          │   api.php    │
    │            │           │  page.php    │          │  (widgets,   │
    │            │           │              │          │   ratings)   │
    └─────┬──────┘           └──────┬───────┘          └──────────────┘
          │                          │
          └──────────┬───────────────┘
                     │
    ┌────────────────▼────────────────────────┐
    │           Repositories                  │
    │  PageRepository   PageSectionRepository │
    │  (lifecycle hooks: audit, events)       │
    └────────────────┬────────────────────────┘
                     │
    ┌────────────────▼────────────────────────┐
    │           Widget System                 │
    │  WidgetRegistry → WidgetRenderer        │
    │  WidgetComposer → WidgetAreaManager     │
    │  Stock widgets + module extensions      │
    │  DataProvider for content binding       │
    └────────────────┬────────────────────────┘
                     │
          ┌──────────┼──────────┐
          │          │          │
    ┌─────▼───┐ ┌───▼────┐ ┌──▼──────────┐
    │ Events  │ │ Rating │ │ Listeners   │
    │ 5 types │ │ System │ │ Analytics   │
    │         │ │ 5 styles│ │ A/B Testing │
    └─────────┘ └────────┘ └─────────────┘
```

---

## XMF Component Map

### Core Architecture (shared with xmfBlog)

| XMF Component | Source File | Guide |
|---|---|---|
| `Container` (DI) | `src/PortalModule.php` | [PSR-11 DI Guide](../Implementation-Guides/PSR-11-Dependency-Injection-Guide.md) |
| `EntityRepository` | `src/PageRepository.php` | [Repository & Query Patterns](../Implementation-Guides/Repository-Query-Patterns-Guide.md) |
| `#[Table]` / `#[Column]` attributes | `src/Page.php` | [Entity Mapping Guide](../Implementation-Guides/Entity-Mapping-Database-Patterns-Guide.md) |
| `DomainEventTrait` | `src/Page.php` | [Event-Driven Architecture](../Implementation-Guides/Event-Driven-Architecture-Guide.md) |
| `VersionAwareInterface` | `src/Page.php` | -- |
| `AuditableInterface` | `src/Page.php` | -- |

### Widget System (unique to xmfPortal)

This is xmfPortal's primary showcase -- the complete XMF widget pipeline.

| XMF Component | Source File | What It Does |
|---|---|---|
| `WidgetRegistry` | `src/PortalModule.php` | Discovers widgets from module `/src/Widget/` + framework stock widgets |
| `WidgetRenderer` | `src/PortalModule.php` | Renders widgets to HTML with timing metrics |
| `WidgetComposer` | `src/PortalModule.php` | Composes multiple widgets into page areas |
| `WidgetAreaManager` | `src/PortalModule.php` | Maps named areas (header, sidebar, content) to widget placements |
| `WidgetDefinition` | `src/Widget/PortalPostsWidget.php` | Widget metadata via `#[Widget]` attribute |
| `#[Prop]` attribute | `src/Widget/PortalPostsWidget.php` | Declarative widget property definitions |
| `#[WidgetVariant]` | `src/Widget/PortalHeroWidget.php` | Template variants (centered, split, video, landing) |
| `VisibilityChecker` | `src/PortalModule.php` | Group-based widget visibility |
| `WidgetShortcodeHandler` | `src/PortalModule.php` | Embed widgets in page body via shortcodes |
| `DataProviderInterface` | `src/DataProvider/RecentContentProvider.php` | Content binding for widgets |
| `Stock\PostsWidget` | `src/Widget/PortalPostsWidget.php` | Base class extended with DataProvider support |
| `Stock\HeroWidget` | `src/Widget/PortalHeroWidget.php` | Base class extended with video/overlay props |

#### Widget Extension Pattern

xmfPortal demonstrates how modules extend framework stock widgets:

```php
// Module extends a stock widget, adding database-backed content
#[Widget(
    name: 'xmfportal:posts',
    title: 'Portal Content Cards',
    category: 'content',
)]
class PortalPostsWidget extends PostsWidget
{
    #[Prop(type: 'select', default: 'recent_pages', options: [...], label: 'Content Source')]
    protected string $source = 'recent_pages';

    private ?DataProviderInterface $dataProvider = null;

    public function getData(): array
    {
        $data = parent::getData();
        if ($this->dataProvider !== null && $data['items'] === []) {
            $providerData = $this->dataProvider->getData([
                'source' => $this->source,
                'limit'  => $data['limit'],
            ]);
            $data['items'] = $providerData['items'] ?? [];
        }
        return $data;
    }
}
```

### Rating System (unique to xmfPortal)

| XMF Component | Source File | What It Does |
|---|---|---|
| `RatingManager` | `src/PortalModule.php` | Records and aggregates user ratings |
| `RatingApiController` | `src/PortalModule.php` | REST API for rating submission |
| `RatingStyle` enum | `xoops_version.php` (config) | 5 styles: thumbs, reactions, stars, numeric5, numeric10 |

### Event Listeners (unique to xmfPortal)

xmfPortal demonstrates **dedicated listener classes** with clear single responsibilities, while xmfBlog handles events inline in lifecycle hooks.

| Listener | Event | What It Does | Source |
|---|---|---|---|
| `AnalyticsListener` | `WidgetRendered` | Records widget name + render time in memory | `src/Listener/AnalyticsListener.php` |
| `AbTestListener` | `WidgetRendering` | Assigns A/B variant to CTA widgets via deterministic hash | `src/Listener/AbTestListener.php` |

```php
// Dedicated listener class -- single responsibility, testable
final class AnalyticsListener
{
    private static array $records = [];

    public function __invoke(WidgetRendered $event): void
    {
        self::$records[] = [
            'name'         => $event->source->name,
            'renderTimeMs' => $event->renderTimeMs,
        ];
    }
}

// Registration in PortalModule::boot()
$events->listen(WidgetRendered::class, new AnalyticsListener());
```

### DataProvider Pattern (unique to xmfPortal)

| XMF Component | Source File | What It Does |
|---|---|---|
| `DataProviderInterface` | `src/DataProvider/RecentContentProvider.php` | Fetches content from database for widget consumption |

The DataProvider decouples widgets from data sources. A widget declares *what kind* of data it needs; the provider resolves *where* that data comes from:

```php
// RecentContentProvider supports 4 sources via match expression
$items = match ($source) {
    'recent_pages'      => $this->getRecentPages($limit),
    'popular_pages'     => $this->getPopularPages($limit),
    'recent_blog_posts' => $this->getRecentBlogPosts($limit),  // cross-module!
    'category'          => $this->getByCategory($categoryId, $limit),
    default             => [],
};
```

The cross-module query (`getRecentBlogPosts`) demonstrates **graceful degradation** -- it checks if the `xmfblog_posts` table exists before querying, so xmfPortal works with or without xmfBlog installed.

### Other Components

| XMF Component | Source File | What It Does |
|---|---|---|
| `Seo\MetaGenerator` | `page.php` | SEO meta tag generation |
| `Xmf\IPAddress` | `api.php` | IP extraction for rate tracking |
| Shortcode engine | `src/PortalModule.php` | Optional `thunderer/shortcode` integration |

---

## Domain Events

| Event | Trigger | Source |
|---|---|---|
| `PagePublishedEvent` | Page saved with Published status | `src/Event/PagePublishedEvent.php` |
| `PageUnpublishedEvent` | Page saved with non-Published status | `src/Event/PageUnpublishedEvent.php` |
| `PageDeletedEvent` | Page deleted | `src/Event/PageDeletedEvent.php` |
| `SectionPlacedEvent` | Widget placed on a page area | `src/Event/SectionPlacedEvent.php` |
| `SectionRemovedEvent` | Widget removed from page | `src/Event/SectionRemovedEvent.php` |

All events are `final readonly` classes with constructor promotion.

---

## Entities

### Page (`src/Page.php`)

Content entity supporting three page types: `content`, `landing`, `dashboard`.

- 30 columns mapped via `#[Column]`
- PHP 8.4 `public private(set)` on primary key
- `getVersionableFields()` returns 13 fields
- Computed methods: `isContent()`, `isLanding()`, `isDashboard()`
- Full audit trail + versioning columns

### PageSection (`src/PageSection.php`)

Widget placement within a page area. Lightweight entity without versioning or audit.

- Links a widget name + variant + props to a page area
- JSON props storage (`propsJson`)
- Visibility groups for XOOPS group-based access control
- Weight-based ordering within areas

---

## Database Schema

| Table | Purpose | Columns |
|---|---|---|
| `xmfportal_pages` | Portal pages (content, landing, dashboard) | 28 columns |
| `xmfportal_sections` | Widget placements within page areas | 9 columns |

Shared XMF tables (`xmf_ratings`, `xmf_versions`, `xmf_audit_log`, etc.) are created via `include/oninstall.php` with table-existence checks.

---

## Key Source Files

If you only read five files, read these:

| File | Lines | Why |
|---|---|---|
| `src/PortalModule.php` | ~550 | Widget system wiring, listener registration, rating setup |
| `src/Widget/PortalPostsWidget.php` | 75 | How to extend stock widgets with DataProvider |
| `src/DataProvider/RecentContentProvider.php` | 160 | Cross-module content aggregation with graceful degradation |
| `src/Listener/AnalyticsListener.php` | 45 | Dedicated event listener pattern |
| `src/Listener/AbTestListener.php` | 45 | Event-driven A/B testing |

---

## What xmfPortal Does NOT Demonstrate

These are covered by [xmfBlog](xmfBlog.md) instead:

- REST API with full middleware pipeline (rate limit, auth, permissions)
- Queue jobs and async notification delivery
- Scheduled tasks (CRON-based)
- Media management (upload, resize, crop)
- Taxonomy and tagging system
- Comment system
- Plugin system
- Migration class with infrastructure table creation
- FormBuilder for admin forms
- Unit tests
