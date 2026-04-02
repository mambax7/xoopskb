---
title: PSR Standards Overview for XOOPS 4.0
description: Comprehensive overview of all PHP-FIG PSR standards adopted in XOOPS 4.0
version: 1.0.0
created: 2025-12-01
updated: 2026-01-28
---

# PSR Standards Overview

## Introduction

XOOPS 4.0 embraces the PHP Standards Recommendations (PSR) published by the PHP Framework Interoperability Group (PHP-FIG). This adoption enables better code quality, improved testability, and seamless integration with the broader PHP ecosystem.

## Adopted PSR Standards

### Core Standards

| PSR | Name | Purpose in XOOPS |
|-----|------|------------------|
| [[PSR-4-Autoloading|PSR-4]] | Autoloader | Class autoloading standard |
| [[PSR-7-HTTP-Messages|PSR-7]] | HTTP Message Interface | Request/Response handling |
| [[PSR-11-Container|PSR-11]] | Container Interface | Dependency injection |
| [[PSR-15-Middleware|PSR-15]] | HTTP Server Middleware | Request processing pipeline |

### Supporting Standards

| PSR | Name | Purpose in XOOPS |
|-----|------|------------------|
| PSR-1 | Basic Coding Standard | Code style foundation |
| PSR-3 | Logger Interface | Logging abstraction |
| PSR-12 | Extended Coding Style | Comprehensive code style |
| PSR-14 | Event Dispatcher | Event system architecture |
| PSR-16 | Simple Cache | Caching abstraction |
| PSR-17 | HTTP Factories | HTTP object creation |
| PSR-18 | HTTP Client | External HTTP requests |

## PSR-1: Basic Coding Standard

### Overview

PSR-1 defines the fundamental coding standards that ensure a high level of technical interoperability between PHP code.

### Key Requirements

```php
<?php
// Files MUST use only <?php and <?= tags
// Files MUST use only UTF-8 without BOM for PHP code
// Class names MUST be declared in StudlyCaps
// Class constants MUST be declared in all upper case with underscore separators
// Method names MUST be declared in camelCase

namespace Xoops\Module\Publisher;

class ArticleController
{
    public const MAX_ARTICLES_PER_PAGE = 20;
    public const CACHE_TTL = 3600;

    public function listArticles(): ResponseInterface
    {
        // Method implementation
    }
}
```

## PSR-3: Logger Interface

### Overview

PSR-3 describes a common interface for logging libraries, enabling XOOPS to use any PSR-3 compliant logger.

### Interface Definition

```php
namespace Psr\Log;

interface LoggerInterface
{
    public function emergency(string|\Stringable $message, array $context = []): void;
    public function alert(string|\Stringable $message, array $context = []): void;
    public function critical(string|\Stringable $message, array $context = []): void;
    public function error(string|\Stringable $message, array $context = []): void;
    public function warning(string|\Stringable $message, array $context = []): void;
    public function notice(string|\Stringable $message, array $context = []): void;
    public function info(string|\Stringable $message, array $context = []): void;
    public function debug(string|\Stringable $message, array $context = []): void;
    public function log(mixed $level, string|\Stringable $message, array $context = []): void;
}
```

### XOOPS Implementation with Monolog

```php
namespace Xoops\Core\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\LoggerInterface;

class XoopsLogger extends Logger implements LoggerInterface
{
    public function __construct(string $name = 'xoops')
    {
        parent::__construct($name);

        // Add rotating file handler
        $this->pushHandler(new RotatingFileHandler(
            XOOPS_VAR_PATH . '/logs/xoops.log',
            30,  // Keep 30 days of logs
            Logger::WARNING
        ));

        // Development: add stream handler
        if (getenv('XOOPS_DEBUG') === 'true') {
            $this->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        }
    }

    // Legacy compatibility methods
    public function addQuery(string $sql, ?string $error = null, ?int $errno = null): void
    {
        $this->debug('Database query', [
            'sql' => $sql,
            'error' => $error,
            'errno' => $errno,
        ]);
    }

    public function addDeprecated(string $message): void
    {
        $this->warning('Deprecated: ' . $message);
    }
}
```

## PSR-12: Extended Coding Style

### Overview

PSR-12 extends and expands on PSR-1 and PSR-2 to provide comprehensive coding style guidelines.

### Key Rules

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xoops\Core\View\ViewRendererInterface;
use Xoops\Module\Publisher\Service\ArticleService;

class ArticleController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ViewRendererInterface $view,
    ) {}

    public function list(
        ServerRequestInterface $request,
        int $page = 1,
    ): ResponseInterface {
        $articles = $this->articleService->getPaginated($page);

        return $this->view->render('@modules/publisher/list', [
            'articles' => $articles,
            'page' => $page,
        ]);
    }
}
```

## PSR-14: Event Dispatcher

### Overview

PSR-14 standardizes the way events are dispatched and listeners are attached.

### Interface Definitions

```php
namespace Psr\EventDispatcher;

interface EventDispatcherInterface
{
    public function dispatch(object $event): object;
}

interface ListenerProviderInterface
{
    public function getListenersForEvent(object $event): iterable;
}

interface StoppableEventInterface
{
    public function isPropagationStopped(): bool;
}
```

### XOOPS Event System

```php
namespace Xoops\Core\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProviderInterface $listenerProvider
    ) {}

    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);

            if ($event instanceof StoppableEventInterface
                && $event->isPropagationStopped()
            ) {
                break;
            }
        }

        return $event;
    }
}

// Event definition
class ArticleCreatedEvent
{
    public function __construct(
        public readonly int $articleId,
        public readonly int $authorId,
        public readonly \DateTimeImmutable $createdAt
    ) {}
}

// Listener registration
$provider->addListener(ArticleCreatedEvent::class, function(ArticleCreatedEvent $event) {
    // Send notification, update statistics, etc.
});
```

## PSR-16: Simple Cache

### Overview

PSR-16 provides a simplified caching interface for basic use cases.

### Interface Definition

```php
namespace Psr\SimpleCache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function getMultiple(iterable $keys, mixed $default = null): iterable;
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool;
    public function deleteMultiple(iterable $keys): bool;
    public function has(string $key): bool;
}
```

### XOOPS Cache Implementation

```php
namespace Xoops\Core\Cache;

use Psr\SimpleCache\CacheInterface;

class XoopsCache implements CacheInterface
{
    private const NULL_SENTINEL = '__XOOPS_NULL__';

    public function __construct(
        private readonly CacheInterface $backend
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->backend->get($this->normalizeKey($key), self::NULL_SENTINEL);

        if ($value === self::NULL_SENTINEL) {
            return $default;
        }

        return $value === '__STORED_NULL__' ? null : $value;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $storedValue = $value === null ? '__STORED_NULL__' : $value;
        return $this->backend->set($this->normalizeKey($key), $storedValue, $ttl);
    }

    private function normalizeKey(string $key): string
    {
        // Hash long keys to prevent backend issues
        if (strlen($key) > 64) {
            return hash('xxh3', $key);
        }
        return preg_replace('/[^a-z0-9._:-]/i', '_', $key);
    }
}
```

## PSR-17: HTTP Factories

### Overview

PSR-17 defines factory interfaces for creating PSR-7 HTTP objects.

### Interface Definitions

```php
namespace Psr\Http\Message;

interface RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface;
}

interface ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface;
}

interface ServerRequestFactoryInterface
{
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface;
}

interface StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface;
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface;
    public function createStreamFromResource($resource): StreamInterface;
}

interface UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface;
}

interface UploadedFileFactoryInterface
{
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface;
}
```

## PSR-18: HTTP Client

### Overview

PSR-18 defines a standard interface for sending HTTP requests and receiving responses.

### Interface Definition

```php
namespace Psr\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
```

### XOOPS HTTP Client Usage

```php
namespace Xoops\Core\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HttpClient
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory
    ) {}

    public function get(string $url, array $headers = []): string
    {
        $request = $this->requestFactory->createRequest('GET', $url);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = $this->client->sendRequest($request);

        return (string) $response->getBody();
    }
}
```

## Recommended Libraries

### For Each PSR Standard

| PSR | Recommended Library | Package |
|-----|---------------------|---------|
| PSR-3 | Monolog | `monolog/monolog` |
| PSR-7 | Nyholm PSR-7 | `nyholm/psr7` |
| PSR-11 | PHP-DI | `php-di/php-di` |
| PSR-14 | Symfony EventDispatcher | `symfony/event-dispatcher` |
| PSR-15 | Laminas Stratigility | `laminas/laminas-stratigility` |
| PSR-16 | Symfony Cache | `symfony/cache` |
| PSR-17 | Nyholm PSR-7 | `nyholm/psr7` |
| PSR-18 | Guzzle | `guzzlehttp/guzzle` |

### Composer Requirements

```json
{
    "require": {
        "php": ">=8.4",
        "psr/log": "^3.0",
        "psr/http-message": "^2.0",
        "psr/container": "^2.0",
        "psr/http-server-middleware": "^1.0",
        "psr/simple-cache": "^3.0",
        "psr/http-factory": "^1.0",
        "psr/http-client": "^1.0",
        "psr/event-dispatcher": "^1.0",
        "monolog/monolog": "^3.0",
        "nyholm/psr7": "^1.8",
        "php-di/php-di": "^7.0"
    }
}
```

## Benefits of PSR Adoption

### 1. Interoperability

- Use any PSR-compliant library
- Swap implementations without code changes
- Integrate with modern frameworks

### 2. Testability

- Mock interfaces easily
- Isolate components for testing
- Predictable behavior

### 3. Maintainability

- Consistent code structure
- Clear contracts
- Industry-standard patterns

### 4. Community

- Leverage community packages
- Attract modern PHP developers
- Future-proof architecture

## See Also

- [[PSR-4-Autoloading|PSR-4 Autoloading Implementation]]
- [[PSR-7-HTTP-Messages|PSR-7 HTTP Messages]]
- [[PSR-11-Container|PSR-11 Dependency Injection]]
- [[PSR-15-Middleware|PSR-15 Middleware Pipeline]]
- [[../XOOPS-4.0-Roadmap|XOOPS 4.0 Roadmap]]

## External Resources

- [PHP-FIG Official Website](https://www.php-fig.org/)
- [PSR Index](https://www.php-fig.org/psr/)
- [Packagist - PHP Package Repository](https://packagist.org/)

---

#xoops-4.0 #psr-standards #php-fig #interoperability #best-practices
