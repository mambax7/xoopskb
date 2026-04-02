# XOOPS Event System

<span class="version-badge version-25x">2.5.x: Preloads</span> <span class="version-badge version-40x">4.0.x: PSR-14</span>

!!! abstract "Not sure which event system to use?"
    See [Choosing an Event System](Choosing-Event-System.md) for a decision tree with code examples for both approaches.


!!! info "Two Event Systems in XOOPS"
    | System | Version | Use Case |
    |--------|---------|----------|
    | **Preload System** | ✅ XOOPS 2.5.x (current) | Hook into core events via `class/Preload.php` |
    | **PSR-14 Event Dispatcher** | 🚧 XOOPS 4.0 (future) | Modern event dispatching with typed events |
    
    **For XOOPS 2.5.x modules**, use the [Preload System](#preload-system-legacy) section below. The PSR-14 section is for future 2026 development.


## Overview

The XOOPS event system enables loose coupling between modules through an observer pattern. Components can emit events that other parts of the system can listen to and respond to.

## Event Types

### Core Events

| Event | Trigger Point |
|-------|---------------|
| `core.header.start` | Before header processing |
| `core.header.end` | After header processing |
| `core.footer.start` | Before footer rendering |
| `core.footer.end` | After footer rendering |
| `core.exception` | When exception occurs |

### Module Lifecycle Events

| Event | Trigger Point |
|-------|---------------|
| `module.install` | After module installation |
| `module.update` | After module update |
| `module.uninstall` | Before module removal |
| `module.activate` | When module activated |
| `module.deactivate` | When module deactivated |

### User Events

| Event | Trigger Point |
|-------|---------------|
| `user.login` | After successful login |
| `user.logout` | After logout |
| `user.register` | After registration |
| `user.delete` | Before user deletion |

## Preload System (Legacy)

### Creating a Preload

```php
<?php
// class/Preload.php

namespace XoopsModules\MyModule;

use Xmf\Module\Helper\AbstractHelper;

final class Preload extends AbstractHelper
{
    public function eventCoreHeaderStart(array $args): void
    {
        // Runs on every page before header
    }

    public function eventCoreFooterStart(array $args): void
    {
        // Runs before footer renders
    }

    public function eventUserLogin(array $args): void
    {
        $userId = $args['userid'];
        // Handle login event
    }

    public function eventCoreException(array $args): void
    {
        $exception = $args['exception'];
        // Log or handle exception
    }
}
```

### Event Method Naming

```
event{Category}{Action}

Examples:
- eventCoreHeaderStart
- eventUserLogin
- eventModuleNewsArticleCreate
```

## PSR-14 Event Dispatcher (XOOPS 4.0)

### Event Class

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Event;

final class ArticleCreatedEvent
{
    public function __construct(
        public readonly int $articleId,
        public readonly int $authorId,
        public readonly string $title,
        public readonly \DateTimeImmutable $createdAt
    ) {}
}
```

### Dispatching Events

```php
use Psr\EventDispatcher\EventDispatcherInterface;

final class ArticleService
{
    public function __construct(
        private readonly ArticleRepository $repository,
        private readonly EventDispatcherInterface $dispatcher
    ) {}

    public function create(CreateArticleDTO $dto): Article
    {
        $article = Article::create($dto);
        $this->repository->save($article);

        // Dispatch event
        $this->dispatcher->dispatch(new ArticleCreatedEvent(
            articleId: $article->getId(),
            authorId: $article->getAuthorId(),
            title: $article->getTitle(),
            createdAt: new \DateTimeImmutable()
        ));

        return $article;
    }
}
```

### Event Listener

```php
<?php

declare(strict_types=1);

namespace XoopsModules\MyModule\Listener;

use XoopsModules\MyModule\Event\ArticleCreatedEvent;

final class SendNotificationOnArticleCreated
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {}

    public function __invoke(ArticleCreatedEvent $event): void
    {
        $this->notifications->notifySubscribers(
            'new_article',
            [
                'article_id' => $event->articleId,
                'title' => $event->title,
            ]
        );
    }
}
```

### Registering Listeners

```php
// config/events.php

return [
    ArticleCreatedEvent::class => [
        SendNotificationOnArticleCreated::class,
        UpdateSearchIndex::class,
        ClearArticleCache::class,
    ],

    ArticleUpdatedEvent::class => [
        UpdateSearchIndex::class,
        ClearArticleCache::class,
    ],

    ArticleDeletedEvent::class => [
        RemoveFromSearchIndex::class,
        ClearArticleCache::class,
    ],
];
```

## Stoppable Events

```php
use Psr\EventDispatcher\StoppableEventInterface;

final class ArticlePublishingEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;
    private ?string $rejectionReason = null;

    public function __construct(
        public readonly Article $article
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function reject(string $reason): void
    {
        $this->propagationStopped = true;
        $this->rejectionReason = $reason;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }
}

// Listener can stop propagation
final class ContentModerationListener
{
    public function __invoke(ArticlePublishingEvent $event): void
    {
        if ($this->containsProhibitedContent($event->article)) {
            $event->reject('Content violates community guidelines');
        }
    }
}
```

## Best Practices

1. **Immutable Events** - Events should be read-only
2. **Specific Events** - Create specific events, not generic ones
3. **Async When Possible** - Use queues for slow operations
4. **No Side Effects in Dispatch** - Dispatch should be quick
5. **Document Events** - List available events for module users

## Related Documentation

- [Module-Development](../03-Module-Development/Module-Development.md) - Module development
- [Event-System-Guide](../07-XOOPS-4.0/Implementation-Guides/Event-System-Guide.md) - PSR-14 guide
- [Hooks-Events](Hooks-Events.md) - Legacy hooks
