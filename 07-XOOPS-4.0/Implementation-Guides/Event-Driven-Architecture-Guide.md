---
title: Event-Driven Architecture Guide
description: Domain events, EventBus, listeners, queue jobs, and scheduled tasks in XOOPS 4.0
created: 2026-04-02
updated: 2026-04-02
version: 1.0.0
author: XOOPS Team
category: implementation-guide
parent: "[[../XOOPS-4.0-Architecture]]"
php_version: "8.4+"
tags:
  - events
  - event-driven
  - listeners
  - queue
  - scheduler
  - domain-events
status: reference
---

# Event-Driven Architecture Guide

> **Domain events, EventBus, listeners, async jobs, and scheduled tasks for XOOPS 4.0 modules.**

XOOPS 4.0 uses an event-driven architecture where domain entities record significant state changes as events, repositories dispatch them after persistence, and listeners react with side effects. This decouples the core domain from concerns like notifications, analytics, and cache invalidation.

This guide uses **xmfblog** for domain events, queue jobs, and scheduled tasks, and **xmfportal** for dedicated event listener classes.

---

## Architecture

```
Entity records event           Repository dispatches            Listeners react
     │                              │                               │
     ▼                              ▼                               ▼
  Post.php                    PostRepository                   Listeners / Jobs
  ┌──────────────┐           ┌──────────────┐           ┌──────────────────────┐
  │ recordEvent( │           │  afterSave() │           │  AnalyticsListener   │
  │   new Post   │──save()──▶│  afterDelete()│──dispatch─▶│  AbTestListener    │
  │   Published  │           │              │    via     │  NotificationJob     │
  │   Event()    │           │              │  EventBus  │  CacheInvalidation   │
  │ )            │           │              │           │                      │
  └──────────────┘           └──────────────┘           └──────────────────────┘
```

---

## Domain Events

### Defining Events

Domain events are immutable `final readonly` classes with constructor promotion:

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

/**
 * Fired when a post is published.
 * Recorded on entity, flushed by Repository::save() through EventBus.
 */
final readonly class PostPublishedEvent
{
    public function __construct(
        public int $postId,
        public string $title,
        public int $authorId,
    ) {
    }
}
```

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

/**
 * Fired when a category is created.
 */
final readonly class CategoryCreatedEvent
{
    public function __construct(
        public int $categoryId,
        public string $name,
        public int $parentId,
    ) {
    }
}
```

### Event Design Rules

| Rule | Rationale |
|------|-----------|
| `final readonly class` | Events are immutable facts — never modified after creation |
| Constructor promotion only | No setters, no methods, pure data transfer |
| Named arguments in constructors | Self-documenting at dispatch site |
| Past tense naming | Events describe something that already happened |
| No logic | Events carry data; listeners contain behavior |

### xmfblog Events

| Event | When Fired | Data |
|-------|-----------|------|
| `PostPublishedEvent` | Post saved with Published status | postId, title, authorId |
| `PostUnpublishedEvent` | Existing post saved with non-Published status | postId, title |
| `PostDeletedEvent` | Post deleted | postId, title |
| `PostEvent` | API create/update/delete | action, postId |
| `CategoryCreatedEvent` | New category saved | categoryId, name, parentId |
| `CategoryUpdatedEvent` | Existing category saved | categoryId, name |
| `CategoryDeletedEvent` | Category deleted | categoryId, name |
| `CommentRejectedEvent` | Comment moderation rejection | commentId, reason |

### xmfportal Events

| Event | When Fired | Data |
|-------|-----------|------|
| `PagePublishedEvent` | Page saved with Published status | pageId, title, authorId |
| `PageUnpublishedEvent` | Page saved with non-Published status | pageId, title |
| `PageDeletedEvent` | Page deleted | pageId, title |
| `SectionPlacedEvent` | Widget placed on page | sectionId, pageId, widgetName, areaName |
| `SectionRemovedEvent` | Widget removed from page | sectionId, pageId |

---

## Recording and Dispatching Events

### Recording on Entities

Entities use `DomainEventTrait` to accumulate events during their lifecycle:

```php
use Xmf\Object\DomainEventTrait;

class Post implements DomainEventAwareInterface
{
    use DomainEventTrait;
    // DomainEventTrait provides:
    //   recordEvent(object $event): void
    //   releaseEvents(): array
    //   clearEvents(): void
}
```

### Dispatching from Repository Hooks

Events are recorded during `afterSave()` and `afterDelete()`, then flushed by the framework through EventBus:

```php
// PostRepository::afterSave()
if ($entity->status === ObjectStatus::Published->value) {
    $entity->recordEvent(new PostPublishedEvent(
        postId: $entity->id,
        title: $entity->title,
        authorId: $entity->uid,
    ));
}
```

The `EntityRepository` base class dispatches recorded events after the hook completes:

```
save() → beforeSave() → SQL → afterSave() → EventBus::dispatch(recorded events)
```

Events fire **after** persistence succeeds. If the save fails, no events are dispatched.

---

## Event Listeners

### ListenerProvider Registration

Listeners are registered in the module bootstrap:

```php
// BlogModule::boot()
$container->singleton('events', function () {
    return new ListenerProvider();
});

$container->singleton('event_bus', function (Container $c) {
    return new EventBus($c->get('events'));
});
```

### xmfportal Listener Examples

xmfportal demonstrates dedicated listener classes with clear single responsibilities:

**Analytics Listener** — records widget render metrics:

```php
<?php
declare(strict_types=1);

namespace XmfPortal\Listener;

use Xmf\Presentation\Widget\Event\WidgetRendered;
use Xmf\Presentation\Widget\WidgetInterface;

final class AnalyticsListener
{
    /** @var list<array{name: string, renderTimeMs: float}> */
    private static array $records = [];

    public function __invoke(WidgetRendered $event): void
    {
        $name = $event->source instanceof WidgetInterface
            ? $event->source->name()
            : $event->source->name;

        self::$records[] = [
            'name'         => $name,
            'renderTimeMs' => $event->renderTimeMs,
        ];
    }

    public static function getRecords(): array
    {
        return self::$records;
    }
}
```

**A/B Test Listener** — assigns test variants before widget rendering:

```php
<?php
declare(strict_types=1);

namespace XmfPortal\Listener;

use Xmf\Presentation\Widget\Event\WidgetRendering;

final class AbTestListener
{
    public function __construct(private string $salt = 'xmfportal-ab')
    {
    }

    public function __invoke(WidgetRendering $event): void
    {
        if ($event->definition->name !== 'xmfportal:cta') {
            return;
        }

        $hash = crc32($this->salt . date('YmdH'));
        $variant = ($hash % 2 === 0) ? 'A' : 'B';

        $event->props->set('abVariant', $variant);
    }
}
```

**Registration in PortalModule::boot():**

```php
/** @var ListenerProvider $events */
$events = $container->get('events');

$events->listen(WidgetRendering::class, new AbTestListener());
$events->listen(WidgetRendered::class, new AnalyticsListener());
```

### Listener Design Rules

| Rule | Rationale |
|------|-----------|
| `__invoke(EventClass $event)` | Single method, type-hinted to specific event |
| `final class` | Listeners are leaf classes — no inheritance |
| One listener per concern | Separate analytics from A/B testing from notifications |
| No return value | Listeners react; they don't influence the dispatch chain |

---

## Legacy Event Bridge

`PreloadEventBridge` connects the old XOOPS preload event system to the new EventBus, allowing legacy modules and new modules to coexist:

```php
// BlogModule::boot()
$container->singleton('preload_bridge', function (Container $c) {
    return new PreloadEventBridge($c->get('event_bus'));
});
```

This enables legacy preload handlers to fire events that new listeners can hear, and vice versa.

---

## Async Queue Jobs

For operations that shouldn't block the HTTP response (emails, external API calls), use the queue:

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

use Xmf\Container\Container;
use Xmf\Notification\NotificationManager;
use Xmf\Notification\NotificationPayload;
use Xmf\Queue\JobInterface;
use Xmf\Queue\QueuedJob;

#[QueuedJob(queue: 'notifications', retries: 3, backoff: 30)]
final class SendCommentNotificationJob implements JobInterface
{
    private string $id;

    public function __construct(
        private readonly int $postId,
        private readonly int $postAuthorId,
        private readonly string $commenterName,
        private readonly string $commentExcerpt,
    ) {
        $this->id = uniqid('comment_notify_', true);
    }

    public function handle(Container $c): void
    {
        if (!$c->has('notification_manager')) {
            return;
        }

        /** @var NotificationManager $nm */
        $nm = $c->get('notification_manager');

        $payload = new NotificationPayload(
            subject: 'New comment on your post',
            body: sprintf(
                '%s commented on your post: "%s"',
                $this->commenterName,
                mb_substr($this->commentExcerpt, 0, 100),
            ),
            type: 'new_comment',
        );

        $nm->deliverSync(['in_app'], $this->postAuthorId, $payload);
    }

    public function getId(): string
    {
        return $this->id;
    }
}
```

### Queue Configuration via Attributes

The `#[QueuedJob]` attribute configures queue behavior declaratively:

| Parameter | Purpose | Example |
|-----------|---------|---------|
| `queue` | Named queue for priority separation | `'notifications'` |
| `retries` | Max retry attempts on failure | `3` |
| `backoff` | Seconds between retries | `30` |

### Dispatching Jobs

```php
$queue = $container->get('queue');
$queue->push(new SendCommentNotificationJob(
    postId: $post->id,
    postAuthorId: $post->uid,
    commenterName: $commenterName,
    commentExcerpt: $commentBody,
));
```

Jobs are persisted in `xmf_queue_jobs` and processed by `QueueRunner`.

---

## Scheduled Tasks

For recurring operations (cleanup, expiry, reporting), implement `ScheduledTaskInterface`:

```php
<?php
declare(strict_types=1);

namespace XmfBlog;

use Xmf\Container\Container;
use Xmf\Enum\ObjectStatus;
use Xmf\Scheduler\ScheduledTaskInterface;

final class ExpirePostsTask implements ScheduledTaskInterface
{
    public function __construct(private readonly Container $container)
    {
    }

    public function getName(): string
    {
        return 'xmfblog.expire_posts';
    }

    public function getSchedule(): string
    {
        return '0 * * * *';  // Every hour at minute 0
    }

    public function execute(): void
    {
        if (!$this->container->has('db')) {
            return;
        }

        /** @var \XoopsDatabase $db */
        $db = $this->container->get('db');
        $now = time();
        $published = ObjectStatus::Published->value;
        $draft = ObjectStatus::Draft->value;

        $sql = 'UPDATE ' . $db->prefix('xmfblog_posts')
             . ' SET status = ' . $draft . ', date_updated = ' . $now
             . ' WHERE status = ' . $published
             . ' AND date_expire > 0 AND date_expire < ' . $now;
        $db->exec($sql);
    }
}
```

### xmfblog Scheduled Tasks

| Task | Schedule | Purpose |
|------|----------|---------|
| `ExpirePostsTask` | `0 * * * *` (hourly) | Unpublish posts past `date_expire` |
| `PruneOldVersionsTask` | `0 3 * * *` (daily 3 AM) | Clean old version snapshots |
| `PruneAuditLogTask` | `0 4 * * 0` (weekly Sun 4 AM) | Trim old audit log entries |

### Task Registration

```php
// BlogModule::boot()
$container->singleton('task_registry', function (Container $c) {
    $registry = new TaskRegistry();
    $registry->register(new ExpirePostsTask($c));
    $registry->register(new PruneOldVersionsTask($c));
    $registry->register(new PruneAuditLogTask($c));
    return $registry;
});

$container->singleton('task_runner', function (Container $c) {
    return new TaskRunner($c->get('task_registry'), new CronMatcher());
});
```

---

## Putting It Together

### Complete Event Flow

1. **User publishes a post** via admin form
2. `PostRepository::afterSave()` detects status = Published
3. Entity records `PostPublishedEvent` via `DomainEventTrait`
4. `EntityRepository` flushes events through `EventBus`
5. Registered listeners react:
   - Cache invalidation listener clears RSS cache
   - Notification listener queues email to subscribers
6. Queue job runs async, delivers notifications without blocking response
7. Scheduled task later expires the post when `date_expire` passes

### Event vs. Queue vs. Task

| Mechanism | When to Use | Timing |
|-----------|------------|--------|
| **Domain Event** | React to state changes in the same request | Synchronous |
| **Queue Job** | Defer expensive work (email, API calls) | Asynchronous |
| **Scheduled Task** | Recurring operations (cleanup, expiry) | Cron-based |

---

## Related

- [[Entity-Mapping-Database-Patterns-Guide|Entity Mapping & Database Patterns]]
- [[Repository-Query-Patterns-Guide|Repository & Query Patterns]]
- [[Error-Handling-Validation-Guide|Error Handling & Validation]]
- [[../../02-Core-Concepts/Event-System|XOOPS Event System (2.5.x)]]

---

#events #event-driven #listeners #queue #scheduler #xoops-4.0
