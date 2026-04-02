---
title: PSR-15 Middleware in XOOPS 4.0
description: HTTP Server Middleware implementation for the XOOPS request pipeline
version: 1.0.0
created: 2025-12-01
updated: 2026-01-28
---

# PSR-15 Middleware

## Overview

PSR-15 defines interfaces for HTTP server request handlers and middleware. XOOPS 4.0 uses PSR-15 as the foundation for its request processing pipeline, enabling modular, testable, and reusable request handling components.

## PSR-15 Interfaces

### RequestHandlerInterface

```php
namespace Psr\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestHandlerInterface
{
    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
```

### MiddlewareInterface

```php
namespace Psr\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the
     * provided request handler to do so.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

## XOOPS Middleware Pipeline

### Pipeline Implementation

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipeline implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];
    private int $index = 0;
    private RequestHandlerInterface $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * Add middleware to the pipeline
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Handle the request through the middleware stack
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middleware[$this->index])) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = $this->middleware[$this->index];
        $this->index++;

        return $middleware->process($request, $this);
    }

    /**
     * Reset the pipeline for reuse
     */
    public function reset(): void
    {
        $this->index = 0;
    }
}
```

### Kernel Implementation

```php
<?php

declare(strict_types=1);

namespace Xoops\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\Http\MiddlewarePipeline;
use Xoops\Core\Routing\Router;

class Kernel implements RequestHandlerInterface
{
    private MiddlewarePipeline $pipeline;

    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container
    ) {
        $this->pipeline = new MiddlewarePipeline($this);
        $this->configureMiddleware();
    }

    private function configureMiddleware(): void
    {
        // Core middleware stack
        $this->pipeline
            ->pipe($this->container->get(ErrorHandlerMiddleware::class))
            ->pipe($this->container->get(SecurityHeadersMiddleware::class))
            ->pipe($this->container->get(SessionMiddleware::class))
            ->pipe($this->container->get(AuthenticationMiddleware::class))
            ->pipe($this->container->get(CsrfMiddleware::class))
            ->pipe($this->container->get(RouterMiddleware::class));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pipeline->handle($request);
    }
}
```

## Core Middleware Components

### Error Handler Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Xoops\Core\Http\ApiResponse;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ApiResponse $response,
        private readonly bool $debug = false
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    private function handleException(
        \Throwable $e,
        ServerRequestInterface $request
    ): ResponseInterface {
        $this->logger->error($e->getMessage(), [
            'exception' => $e,
            'uri' => (string) $request->getUri(),
            'method' => $request->getMethod(),
        ]);

        $status = $this->getStatusCode($e);
        $message = $this->debug
            ? $e->getMessage()
            : $this->getPublicMessage($status);

        if ($this->wantsJson($request)) {
            return $this->response->json([
                'error' => true,
                'message' => $message,
                'code' => $status,
            ], $status);
        }

        return $this->response->html(
            $this->renderErrorPage($status, $message),
            $status
        );
    }

    private function getStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return 500;
    }

    private function getPublicMessage(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            default => 'An error occurred',
        };
    }

    private function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');
        return str_contains($accept, 'application/json');
    }
}
```

### CSRF Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\Security\CsrfTokenManager;

class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const TOKEN_FIELD = '_csrf_token';
    private const TOKEN_HEADER = 'X-CSRF-Token';

    public function __construct(
        private readonly CsrfTokenManager $tokenManager
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Skip CSRF check for safe methods
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        // Get token from request
        $token = $this->getTokenFromRequest($request);

        // Validate token
        if (!$this->tokenManager->isValid($token)) {
            throw new CsrfValidationException('Invalid CSRF token');
        }

        return $handler->handle($request);
    }

    private function getTokenFromRequest(ServerRequestInterface $request): string
    {
        // Check header first
        $headerToken = $request->getHeaderLine(self::TOKEN_HEADER);
        if ($headerToken !== '') {
            return $headerToken;
        }

        // Check body
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body[self::TOKEN_FIELD])) {
            return (string) $body[self::TOKEN_FIELD];
        }

        return '';
    }
}
```

### Session Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\Session\SessionManager;

class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionManager $sessionManager
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Start session
        $session = $this->sessionManager->start($request);

        // Add session to request attributes
        $request = $request->withAttribute('session', $session);

        // Handle request
        $response = $handler->handle($request);

        // Save session and add cookie to response
        return $this->sessionManager->persist($session, $response);
    }
}
```

### Authentication Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\Auth\AuthenticationService;
use Xoops\Core\Auth\UserInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticationService $auth
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Get session from previous middleware
        $session = $request->getAttribute('session');

        // Authenticate user from session
        $user = $this->auth->authenticateFromSession($session);

        // Add user to request (null if not authenticated)
        $request = $request
            ->withAttribute('user', $user)
            ->withAttribute('isAuthenticated', $user !== null)
            ->withAttribute('isAdmin', $user?->isAdmin() ?? false);

        return $handler->handle($request);
    }
}
```

### Rate Limiting Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\RateLimiter\RateLimiterInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterInterface $rateLimiter,
        private readonly int $maxRequests = 60,
        private readonly int $windowSeconds = 60
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $identifier = $this->getIdentifier($request);
        $result = $this->rateLimiter->attempt($identifier, $this->maxRequests, $this->windowSeconds);

        if (!$result->allowed) {
            throw new TooManyRequestsException(
                'Rate limit exceeded',
                $result->retryAfter
            );
        }

        $response = $handler->handle($request);

        // Add rate limit headers
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining)
            ->withHeader('X-RateLimit-Reset', (string) $result->resetAt);
    }

    private function getIdentifier(ServerRequestInterface $request): string
    {
        $user = $request->getAttribute('user');

        if ($user !== null) {
            return 'user:' . $user->getId();
        }

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        return 'ip:' . $ip;
    }
}
```

### Router Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\Routing\Router;
use Xoops\Core\Routing\RouteMatchInterface;

class RouterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Match route
        $match = $this->router->match($request);

        if ($match === null) {
            throw new NotFoundException('Route not found');
        }

        // Add route parameters to request attributes
        foreach ($match->getParams() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        // Add route info
        $request = $request
            ->withAttribute('_route', $match->getName())
            ->withAttribute('_module', $match->getModuleSlug());

        // Resolve and execute controller
        return $this->executeHandler($match, $request);
    }

    private function executeHandler(
        RouteMatchInterface $match,
        ServerRequestInterface $request
    ): ResponseInterface {
        [$class, $method] = explode('::', $match->getHandler());

        $controller = $this->container->get($class);

        // Build method arguments from route params
        $args = [$request];
        foreach ($match->getParams() as $name => $value) {
            $args[$name] = $value;
        }

        return $controller->$method(...$args);
    }
}
```

## Module-Specific Middleware

### Permission Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\Publisher\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PublisherPermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $permission
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $request->getAttribute('user');

        if ($user === null) {
            throw new UnauthorizedException('Authentication required');
        }

        if (!$user->hasPermission('publisher', $this->permission)) {
            throw new ForbiddenException('Permission denied');
        }

        return $handler->handle($request);
    }
}
```

### Module Initialization Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xoops\Core\Module\ModuleManager;

class ModuleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ModuleManager $moduleManager
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $moduleSlug = $request->getAttribute('_module');

        if ($moduleSlug === null) {
            return $handler->handle($request);
        }

        // Load and initialize module
        $module = $this->moduleManager->load($moduleSlug);

        if ($module === null || !$module->isActive()) {
            throw new NotFoundException("Module '$moduleSlug' not found");
        }

        // Add module to request
        $request = $request->withAttribute('module', $module);

        // Execute module-specific middleware
        $middlewareStack = $module->getMiddleware();

        if (empty($middlewareStack)) {
            return $handler->handle($request);
        }

        // Build module middleware pipeline
        $pipeline = new MiddlewarePipeline($handler);
        foreach ($middlewareStack as $middleware) {
            $pipeline->pipe($middleware);
        }

        return $pipeline->handle($request);
    }
}
```

## Route-Level Middleware

### Defining Middleware in Routes

```json
{
    "routes": {
        "article.create": {
            "path": "/articles",
            "method": ["POST"],
            "action": "Controller\\ArticleController::create",
            "middleware": ["auth", "csrf", "publisher.can_create"]
        },
        "article.delete": {
            "path": "/articles/{id:\\d+}",
            "method": ["DELETE"],
            "action": "Controller\\ArticleController::delete",
            "middleware": ["auth", "csrf", "publisher.can_delete"]
        }
    }
}
```

### Middleware Aliases

```php
// Container registration
$container->set('middleware.auth', fn($c) =>
    new AuthRequiredMiddleware()
);

$container->set('middleware.csrf', fn($c) =>
    $c->get(CsrfMiddleware::class)
);

$container->set('middleware.publisher.can_create', fn($c) =>
    new PublisherPermissionMiddleware('create_article')
);

$container->set('middleware.publisher.can_delete', fn($c) =>
    new PublisherPermissionMiddleware('delete_article')
);
```

## Creating Custom Middleware

### Template

```php
<?php

declare(strict_types=1);

namespace Xoops\Module\MyModule\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MyCustomMiddleware implements MiddlewareInterface
{
    public function __construct(
        // Inject dependencies
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // 1. Pre-processing (before handler)
        $request = $this->beforeHandler($request);

        // 2. Call the next handler
        $response = $handler->handle($request);

        // 3. Post-processing (after handler)
        $response = $this->afterHandler($response);

        return $response;
    }

    private function beforeHandler(ServerRequestInterface $request): ServerRequestInterface
    {
        // Modify request, add attributes, validate, etc.
        return $request;
    }

    private function afterHandler(ResponseInterface $response): ResponseInterface
    {
        // Modify response, add headers, etc.
        return $response;
    }
}
```

### Conditional Middleware

```php
<?php

declare(strict_types=1);

namespace Xoops\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly callable $condition
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (($this->condition)($request)) {
            return $this->middleware->process($request, $handler);
        }

        return $handler->handle($request);
    }
}

// Usage
$middleware = new ConditionalMiddleware(
    new RateLimitMiddleware($limiter),
    fn($request) => $request->getAttribute('user') === null // Only for guests
);
```

## Testing Middleware

```php
<?php

use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private CsrfTokenManager $tokenManager;
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->tokenManager = new CsrfTokenManager();
        $this->middleware = new CsrfMiddleware($this->tokenManager);
        $this->factory = new Psr17Factory();
    }

    public function testSkipsGetRequests(): void
    {
        $request = $this->factory->createServerRequest('GET', '/articles');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->factory->createResponse(200));

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testValidatesPostRequests(): void
    {
        $token = $this->tokenManager->generate();

        $request = $this->factory->createServerRequest('POST', '/articles')
            ->withParsedBody(['_csrf_token' => $token]);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->factory->createResponse(201));

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testRejectsInvalidToken(): void
    {
        $request = $this->factory->createServerRequest('POST', '/articles')
            ->withParsedBody(['_csrf_token' => 'invalid']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $this->expectException(CsrfValidationException::class);

        $this->middleware->process($request, $handler);
    }
}
```

## See Also

- [[PSR-Standards-Overview|PSR Standards Overview]]
- [[PSR-7-HTTP-Messages|PSR-7 HTTP Messages]]
- [[../Roadmap/Architecture-Vision|Architecture Vision]]

## External Resources

- [PSR-15 Specification](https://www.php-fig.org/psr/psr-15/)
- [Laminas Stratigility](https://docs.laminas.dev/laminas-stratigility/)
- [Middleware Best Practices](https://www.php-fig.org/psr/psr-15/meta/)

---

#xoops-4.0 #psr-15 #middleware #request-handling #pipeline
