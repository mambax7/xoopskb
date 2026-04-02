---
title: PSR-11 Container in XOOPS 4.0
description: Dependency injection container implementation following PSR-11 standards
version: 1.0.0
created: 2025-12-01
updated: 2026-01-28
---

# PSR-11 Container

## Overview

PSR-11 defines a common interface for dependency injection containers. XOOPS 4.0 implements a fully PSR-11 compliant container that manages service instantiation, dependency resolution, and lifecycle management.

## PSR-11 Interface

### ContainerInterface

```php
namespace Psr\Container;

interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundExceptionInterface  No entry was found.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function get(string $id): mixed;

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool;
}
```

### Exception Interfaces

```php
namespace Psr\Container;

interface ContainerExceptionInterface extends \Throwable {}

interface NotFoundExceptionInterface extends ContainerExceptionInterface {}
```

## XOOPS Container Implementation

### XoopsContainer Class

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class XoopsContainer implements ContainerInterface
{
    /** @var array<string, callable|object> */
    private array $services = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /**
     * Register a service factory
     */
    public function set(string $id, callable|object $service): void
    {
        $this->services[$id] = $service;
        unset($this->instances[$id]); // Clear cached instance
    }

    /**
     * Register a service alias
     */
    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    /**
     * Get a service by ID
     */
    public function get(string $id): mixed
    {
        // Resolve alias
        $resolvedId = $this->aliases[$id] ?? $id;

        // Return cached instance if available
        if (isset($this->instances[$resolvedId])) {
            return $this->instances[$resolvedId];
        }

        if (!isset($this->services[$resolvedId])) {
            throw new ServiceNotFoundException(
                sprintf('Service "%s" not found in container.', $id)
            );
        }

        $service = $this->services[$resolvedId];

        // If it's a callable (factory), execute it
        if (is_callable($service)) {
            $instance = $service($this);
            $this->instances[$resolvedId] = $instance;
            return $instance;
        }

        // If it's already an object, cache and return it
        $this->instances[$resolvedId] = $service;
        return $service;
    }

    /**
     * Check if a service exists
     */
    public function has(string $id): bool
    {
        $resolvedId = $this->aliases[$id] ?? $id;
        return isset($this->services[$resolvedId]);
    }

    /**
     * Get a new instance (bypass cache)
     */
    public function make(string $id): mixed
    {
        $resolvedId = $this->aliases[$id] ?? $id;

        if (!isset($this->services[$resolvedId])) {
            throw new ServiceNotFoundException(
                sprintf('Service "%s" not found in container.', $id)
            );
        }

        $service = $this->services[$resolvedId];

        if (is_callable($service)) {
            return $service($this);
        }

        // For objects, create a clone
        if (is_object($service)) {
            return clone $service;
        }

        return $service;
    }
}
```

### Service Not Found Exception

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Container;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
```

## Service Registration

### Service Providers

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Container;

interface ServiceProviderInterface
{
    /**
     * Register services with the container
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot services after all providers are registered
     */
    public function boot(ContainerInterface $container): void;
}
```

### Core Service Provider

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Provider;

use Psr\Container\ContainerInterface;
use Xoops\Core\Container\ServiceProviderInterface;
use Xoops\Core\Database\Connection;
use Xoops\Core\View\SmartyViewRenderer;
use Xoops\Core\View\ViewRendererInterface;
use Xoops\Core\Http\ApiResponse;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class CoreServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // Database Connection
        $container->set('database', function (ContainerInterface $c) {
            $config = $c->get('config');
            return new Connection([
                'host' => $config['db_host'],
                'database' => $config['db_name'],
                'username' => $config['db_user'],
                'password' => $config['db_pass'],
                'prefix' => $config['db_prefix'],
            ]);
        });

        // Alias for type-hint usage
        $container->alias(Connection::class, 'database');

        // Logger
        $container->set('logger', function (ContainerInterface $c) {
            $logger = new Logger('xoops');
            $logger->pushHandler(new RotatingFileHandler(
                XOOPS_VAR_PATH . '/logs/xoops.log',
                30,
                Logger::WARNING
            ));
            return $logger;
        });

        $container->alias(\Psr\Log\LoggerInterface::class, 'logger');

        // View Renderer
        $container->set(ViewRendererInterface::class, function (ContainerInterface $c) {
            return new SmartyViewRenderer($c->get('smarty'));
        });

        // API Response Helper
        $container->set(ApiResponse::class, function () {
            return new ApiResponse();
        });

        // Configuration
        $container->set('config', function () {
            return require XOOPS_VAR_PATH . '/configs/xoopsconfig.php';
        });
    }

    public function boot(ContainerInterface $container): void
    {
        // Boot logic, if needed
    }
}
```

### Module Service Provider

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher;

use Psr\Container\ContainerInterface;
use Xoops\Core\Container\ServiceProviderInterface;

class ModuleServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // Repository
        $container->set('publisher.article_repository', function (ContainerInterface $c) {
            return new Repository\ArticleRepository($c->get('database'));
        });

        $container->alias(
            Repository\ArticleRepositoryInterface::class,
            'publisher.article_repository'
        );

        // Service
        $container->set('publisher.article_service', function (ContainerInterface $c) {
            return new Service\ArticleService(
                $c->get('publisher.article_repository'),
                $c->get('event_dispatcher')
            );
        });

        // Controller
        $container->set(Controller\ArticleController::class, function (ContainerInterface $c) {
            return new Controller\ArticleController(
                $c->get('publisher.article_service'),
                $c->get(ViewRendererInterface::class),
                $c->get(ApiResponse::class)
            );
        });
    }

    public function boot(ContainerInterface $container): void
    {
        // Register event listeners, etc.
    }
}
```

## Container Bootstrap

### Bootstrap File

```php
<?php

// core/bootstrap_container.php

declare(strict_types=1);

use Xoops\Core\Container\XoopsContainer;
use Xoops\Core\Provider\CoreServiceProvider;

$container = new XoopsContainer();

// Register core services
$coreProvider = new CoreServiceProvider();
$coreProvider->register($container);

// Discover and register module providers
$activeModules = $container->get('module_manager')->getActiveModules();

foreach ($activeModules as $module) {
    $providerClass = sprintf(
        'Xoops\\Module\\%s\\ModuleServiceProvider',
        ucfirst($module->dirname)
    );

    if (class_exists($providerClass)) {
        $provider = new $providerClass();
        $provider->register($container);
    }
}

// Boot all providers
$coreProvider->boot($container);

foreach ($activeModules as $module) {
    $providerClass = sprintf(
        'Xoops\\Module\\%s\\ModuleServiceProvider',
        ucfirst($module->dirname)
    );

    if (class_exists($providerClass)) {
        $provider = new $providerClass();
        $provider->boot($container);
    }
}

return $container;
```

## Service Locator Bridge

For gradual migration from legacy code:

```php
<?php

declare(strict_types=1);

namespace Xoops\Core;

use Psr\Container\ContainerInterface;

/**
 * Static service locator for legacy code compatibility
 *
 * @deprecated Use dependency injection instead
 */
class Xoops
{
    private static ?ContainerInterface $container = null;

    /**
     * Set the container instance
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the container instance
     */
    public static function services(): ContainerInterface
    {
        if (self::$container === null) {
            self::$container = require XOOPS_ROOT_PATH . '/core/bootstrap_container.php';
        }

        return self::$container;
    }

    /**
     * Get a service by ID
     *
     * @deprecated Use constructor injection instead
     */
    public static function service(string $id): mixed
    {
        return self::services()->get($id);
    }

    /**
     * Check if a service exists
     */
    public static function hasService(string $id): bool
    {
        return self::services()->has($id);
    }
}

// Legacy usage (deprecated but supported)
$logger = \Xoops::service('logger');
$db = \Xoops::service('database');
```

## Auto-Wiring

### Auto-Wiring Container Extension

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;

class AutoWiringContainer implements ContainerInterface
{
    public function __construct(
        private readonly XoopsContainer $container
    ) {}

    public function get(string $id): mixed
    {
        // First, try the regular container
        if ($this->container->has($id)) {
            return $this->container->get($id);
        }

        // If not found and it's a class, try auto-wiring
        if (class_exists($id)) {
            return $this->autowire($id);
        }

        throw new ServiceNotFoundException("Service '$id' not found");
    }

    public function has(string $id): bool
    {
        return $this->container->has($id) || class_exists($id);
    }

    /**
     * Auto-wire a class by resolving constructor dependencies
     */
    private function autowire(string $className): object
    {
        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Class $className is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new \RuntimeException(
                    "Cannot resolve parameter '{$parameter->getName()}' for $className"
                );
            }

            $dependencies[] = $this->get($type->getName());
        }

        return new $className(...$dependencies);
    }
}
```

## Using the Container

### In Controllers

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Controller;

use Xoops\Core\View\ViewRendererInterface;
use Xoops\Core\Http\ApiResponse;
use Xoops\Module\Publisher\Service\ArticleService;

class ArticleController
{
    // Dependencies injected via constructor
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ViewRendererInterface $view,
        private readonly ApiResponse $response
    ) {}

    // Controller methods use injected services
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $articles = $this->articleService->getPaginated();
        return $this->response->html(
            $this->view->render('@modules/publisher/list', ['articles' => $articles])
        );
    }
}
```

### In Services

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Service;

use Psr\Log\LoggerInterface;
use Xoops\Module\Publisher\Repository\ArticleRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class ArticleService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {}

    public function publish(int $articleId): void
    {
        $article = $this->repository->findById($articleId);

        if ($article === null) {
            $this->logger->warning("Article not found: $articleId");
            throw new ArticleNotFoundException();
        }

        $article->publish();
        $this->repository->save($article);

        $this->eventDispatcher->dispatch(
            new ArticlePublishedEvent($article->id)
        );

        $this->logger->info("Article published: $articleId");
    }
}
```

## Using PHP-DI

For a more feature-rich container, XOOPS supports PHP-DI:

### Installation

```bash
composer require php-di/php-di
```

### Configuration

```php
<?php

// core/container.php

use DI\ContainerBuilder;

$builder = new ContainerBuilder();

// Enable compilation for production
if (getenv('XOOPS_DEBUG') !== 'true') {
    $builder->enableCompilation(XOOPS_VAR_PATH . '/cache/container');
}

// Add definitions
$builder->addDefinitions([
    // Using factories
    'database' => function (ContainerInterface $c) {
        return new Connection($c->get('config')['database']);
    },

    // Using DI\create() helper
    LoggerInterface::class => DI\create(Logger::class)
        ->constructor('xoops'),

    // Auto-wiring by default
    ArticleController::class => DI\autowire(),

    // Interface binding
    ArticleRepositoryInterface::class => DI\get(ArticleRepository::class),
]);

return $builder->build();
```

## Testing with Containers

```php
<?php

use PHPUnit\Framework\TestCase;
use Xoops\Core\Container\XoopsContainer;

class ContainerTest extends TestCase
{
    private XoopsContainer $container;

    protected function setUp(): void
    {
        $this->container = new XoopsContainer();
    }

    public function testSetAndGet(): void
    {
        $service = new \stdClass();
        $this->container->set('test', $service);

        $this->assertTrue($this->container->has('test'));
        $this->assertSame($service, $this->container->get('test'));
    }

    public function testFactoryExecution(): void
    {
        $this->container->set('counter', function () {
            static $count = 0;
            return ++$count;
        });

        // Factory should only be called once (singleton)
        $this->assertEquals(1, $this->container->get('counter'));
        $this->assertEquals(1, $this->container->get('counter'));
    }

    public function testAlias(): void
    {
        $this->container->set('original', new \stdClass());
        $this->container->alias('aliased', 'original');

        $this->assertSame(
            $this->container->get('original'),
            $this->container->get('aliased')
        );
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->container->get('nonexistent');
    }
}
```

## Best Practices

### 1. Prefer Constructor Injection

```php
// Good: Constructor injection
class ArticleService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $repository
    ) {}
}

// Avoid: Service locator in methods
class ArticleService
{
    public function findAll(): array
    {
        // Don't do this
        $repo = Xoops::service('article_repository');
        return $repo->findAll();
    }
}
```

### 2. Depend on Interfaces

```php
// Good: Depend on interface
public function __construct(
    private readonly ArticleRepositoryInterface $repository
) {}

// Avoid: Depend on concrete class
public function __construct(
    private readonly ArticleRepository $repository
) {}
```

### 3. Keep Services Stateless

```php
// Good: Stateless service
class ArticleService
{
    public function findById(int $id): ?Article
    {
        return $this->repository->findById($id);
    }
}

// Avoid: Stateful service
class ArticleService
{
    private ?Article $currentArticle = null;

    public function setCurrentArticle(Article $article): void
    {
        $this->currentArticle = $article;
    }
}
```

## See Also

- [[PSR-Standards-Overview|PSR Standards Overview]]
- [[../Roadmap/Architecture-Vision|Architecture Vision]]
- [[PSR-15-Middleware|PSR-15 Middleware]]

## External Resources

- [PSR-11 Specification](https://www.php-fig.org/psr/psr-11/)
- [PHP-DI Documentation](https://php-di.org/doc/)
- [Dependency Injection Best Practices](https://php-di.org/doc/best-practices.html)

---

#xoops-4.0 #psr-11 #container #dependency-injection #services
