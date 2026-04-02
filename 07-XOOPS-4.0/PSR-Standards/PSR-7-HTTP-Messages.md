---
title: PSR-7 HTTP Messages in XOOPS 4.0
description: Implementation of PSR-7 HTTP Message interfaces for request and response handling
version: 1.0.0
created: 2025-12-01
updated: 2026-01-28
---

# PSR-7 HTTP Messages

## Overview

PSR-7 describes common interfaces for representing HTTP messages. XOOPS 4.0 uses PSR-7 throughout its request/response lifecycle, enabling standardized handling and middleware compatibility.

## Core Interfaces

### MessageInterface

The base interface for both requests and responses:

```php
namespace Psr\Http\Message;

interface MessageInterface
{
    public function getProtocolVersion(): string;
    public function withProtocolVersion(string $version): MessageInterface;

    public function getHeaders(): array;
    public function hasHeader(string $name): bool;
    public function getHeader(string $name): array;
    public function getHeaderLine(string $name): string;
    public function withHeader(string $name, $value): MessageInterface;
    public function withAddedHeader(string $name, $value): MessageInterface;
    public function withoutHeader(string $name): MessageInterface;

    public function getBody(): StreamInterface;
    public function withBody(StreamInterface $body): MessageInterface;
}
```

### RequestInterface

```php
namespace Psr\Http\Message;

interface RequestInterface extends MessageInterface
{
    public function getRequestTarget(): string;
    public function withRequestTarget(string $requestTarget): RequestInterface;

    public function getMethod(): string;
    public function withMethod(string $method): RequestInterface;

    public function getUri(): UriInterface;
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface;
}
```

### ServerRequestInterface

Extended interface for server-side requests:

```php
namespace Psr\Http\Message;

interface ServerRequestInterface extends RequestInterface
{
    public function getServerParams(): array;
    public function getCookieParams(): array;
    public function withCookieParams(array $cookies): ServerRequestInterface;

    public function getQueryParams(): array;
    public function withQueryParams(array $query): ServerRequestInterface;

    public function getUploadedFiles(): array;
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface;

    public function getParsedBody(): null|array|object;
    public function withParsedBody($data): ServerRequestInterface;

    public function getAttributes(): array;
    public function getAttribute(string $name, $default = null): mixed;
    public function withAttribute(string $name, $value): ServerRequestInterface;
    public function withoutAttribute(string $name): ServerRequestInterface;
}
```

### ResponseInterface

```php
namespace Psr\Http\Message;

interface ResponseInterface extends MessageInterface
{
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface;
    public function getReasonPhrase(): string;
}
```

## XOOPS Implementation

### Creating Requests from Globals

```php
namespace Xoops\Core\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

class RequestFactory
{
    public static function fromGlobals(): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        return $creator->fromGlobals();
    }
}

// Usage in index.php
$request = RequestFactory::fromGlobals();
$response = $kernel->handle($request);
```

### Response Helper

```php
namespace Xoops\Core\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ApiResponse
{
    private Psr17Factory $factory;

    public function __construct()
    {
        $this->factory = new Psr17Factory();
    }

    /**
     * Create HTML response
     */
    public function html(string $content, int $status = 200): ResponseInterface
    {
        $response = $this->factory->createResponse($status);
        $body = $this->factory->createStream($content);

        return $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($body);
    }

    /**
     * Create JSON response
     */
    public function json(mixed $data, int $status = 200): ResponseInterface
    {
        $response = $this->factory->createResponse($status);
        $body = $this->factory->createStream(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
    }

    /**
     * Create redirect response
     */
    public function redirect(string $url, int $status = 302): ResponseInterface
    {
        return $this->factory->createResponse($status)
            ->withHeader('Location', $url);
    }

    /**
     * Create file download response
     */
    public function download(
        string $filePath,
        string $filename = null,
        string $contentType = 'application/octet-stream'
    ): ResponseInterface {
        $filename = $filename ?? basename($filePath);
        $body = $this->factory->createStreamFromFile($filePath);

        return $this->factory->createResponse(200)
            ->withHeader('Content-Type', $contentType)
            ->withHeader(
                'Content-Disposition',
                sprintf('attachment; filename="%s"', $filename)
            )
            ->withBody($body);
    }

    /**
     * Create error response
     */
    public function error(string $message, int $status = 500): ResponseInterface
    {
        return $this->json([
            'error' => true,
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    /**
     * Create empty response
     */
    public function noContent(): ResponseInterface
    {
        return $this->factory->createResponse(204);
    }
}
```

### Controller Usage

```php
namespace Xoops\Module\Publisher\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xoops\Core\Http\ApiResponse;
use Xoops\Core\View\ViewRendererInterface;
use Xoops\Module\Publisher\Service\ArticleService;

class ArticleController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ViewRendererInterface $view,
        private readonly ApiResponse $response
    ) {}

    /**
     * List articles - returns HTML
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $articles = $this->articleService->getPaginated($page);

        $html = $this->view->render('@modules/publisher/list', [
            'articles' => $articles,
            'page' => $page,
        ]);

        return $this->response->html($html);
    }

    /**
     * API endpoint - returns JSON
     */
    public function apiList(ServerRequestInterface $request): ResponseInterface
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $articles = $this->articleService->getPaginated($page);

        return $this->response->json([
            'data' => array_map(fn($a) => $a->toArray(), $articles),
            'meta' => [
                'page' => $page,
                'per_page' => 20,
            ],
        ]);
    }

    /**
     * Create article - handles POST
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        // Validate
        if (empty($body['title'])) {
            return $this->response->error('Title is required', 400);
        }

        $article = $this->articleService->create($body);

        return $this->response->json([
            'data' => $article->toArray(),
            'message' => 'Article created successfully',
        ], 201);
    }

    /**
     * View single article
     */
    public function view(ServerRequestInterface $request): ResponseInterface
    {
        // Route parameters are stored as request attributes
        $id = (int) $request->getAttribute('id');

        $article = $this->articleService->findById($id);

        if ($article === null) {
            return $this->response->error('Article not found', 404);
        }

        $html = $this->view->render('@modules/publisher/view', [
            'article' => $article,
        ]);

        return $this->response->html($html);
    }
}
```

## Working with Request Data

### Query Parameters

```php
// GET /articles?page=2&sort=date&order=desc

public function list(ServerRequestInterface $request): ResponseInterface
{
    $params = $request->getQueryParams();

    $page = (int) ($params['page'] ?? 1);
    $sort = $params['sort'] ?? 'date';
    $order = $params['order'] ?? 'desc';

    // Use parameters...
}
```

### POST Body

```php
// POST with form data or JSON

public function store(ServerRequestInterface $request): ResponseInterface
{
    // getParsedBody() returns array for form data or JSON
    $body = $request->getParsedBody();

    $title = $body['title'] ?? '';
    $content = $body['content'] ?? '';

    // For raw body access (e.g., custom format)
    $rawBody = (string) $request->getBody();
}
```

### File Uploads

```php
use Psr\Http\Message\UploadedFileInterface;

public function upload(ServerRequestInterface $request): ResponseInterface
{
    /** @var UploadedFileInterface[] $files */
    $files = $request->getUploadedFiles();

    if (!isset($files['image'])) {
        return $this->response->error('No file uploaded', 400);
    }

    $file = $files['image'];

    // Check for upload errors
    if ($file->getError() !== UPLOAD_ERR_OK) {
        return $this->response->error('Upload failed', 400);
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file->getClientMediaType(), $allowedTypes)) {
        return $this->response->error('Invalid file type', 400);
    }

    // Move uploaded file
    $filename = sprintf('%s_%s', time(), $file->getClientFilename());
    $targetPath = XOOPS_UPLOAD_PATH . '/' . $filename;

    $file->moveTo($targetPath);

    return $this->response->json([
        'filename' => $filename,
        'size' => $file->getSize(),
    ], 201);
}
```

### Request Attributes

Attributes are used to pass data through middleware:

```php
// In middleware
public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {
    // Add user to request attributes
    $user = $this->auth->getUser();
    $request = $request->withAttribute('user', $user);
    $request = $request->withAttribute('isAdmin', $user?->isAdmin() ?? false);

    return $handler->handle($request);
}

// In controller
public function dashboard(ServerRequestInterface $request): ResponseInterface
{
    $user = $request->getAttribute('user');
    $isAdmin = $request->getAttribute('isAdmin', false);

    // Use attributes...
}
```

## Immutability

PSR-7 objects are immutable. Methods that "modify" the object return a new instance:

```php
// Creating a modified request
$request = $originalRequest
    ->withHeader('X-Custom-Header', 'value')
    ->withAttribute('processed', true)
    ->withQueryParams(['page' => 2]);

// Original request is unchanged
assert($originalRequest->getHeader('X-Custom-Header') === []);

// Creating a modified response
$response = $originalResponse
    ->withStatus(201)
    ->withHeader('Location', '/articles/42');
```

## Safe IO Integration

XOOPS provides a Safe IO layer on top of PSR-7:

```php
namespace Xoops\Core\SafeIo;

use Psr\Http\Message\ServerRequestInterface;

class Request
{
    private static ?ServerRequestInterface $request = null;

    public static function setRequest(ServerRequestInterface $request): void
    {
        self::$request = $request;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : $default;
    }

    public static function getString(string $key, string $default = ''): string
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        // Remove null bytes and trim
        return trim(str_replace("\0", '', (string) $value));
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getArray(string $key, array $default = []): array
    {
        $params = self::$request?->getParsedBody() ?? [];
        $value = $params[$key] ?? self::$request?->getQueryParams()[$key] ?? null;

        return is_array($value) ? $value : $default;
    }

    private static function get(string $key): mixed
    {
        $body = self::$request?->getParsedBody() ?? [];
        $query = self::$request?->getQueryParams() ?? [];

        return $body[$key] ?? $query[$key] ?? null;
    }
}

// Usage
$page = Request::getInt('page', 1);
$title = Request::getString('title', '');
$active = Request::getBool('active', false);
$tags = Request::getArray('tags', []);
```

## Response Emitter

Sending the response to the client:

```php
namespace Xoops\Core\Http;

use Psr\Http\Message\ResponseInterface;

class ResponseEmitter
{
    public function emit(ResponseInterface $response): void
    {
        // Emit status line
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());

        // Emit headers
        foreach ($response->getHeaders() as $name => $values) {
            $first = true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first);
                $first = false;
            }
        }

        // Emit body
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }
}

// In index.php
$emitter = new ResponseEmitter();
$emitter->emit($response);
```

## Testing with PSR-7

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

class ArticleControllerTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testListReturnsArticles(): void
    {
        // Create mock request
        $request = $this->factory->createServerRequest('GET', '/articles')
            ->withQueryParams(['page' => 1]);

        // Create controller with mocked dependencies
        $controller = new ArticleController(
            $this->createMock(ArticleService::class),
            $this->createMock(ViewRendererInterface::class),
            new ApiResponse()
        );

        $response = $controller->list($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'text/html',
            $response->getHeaderLine('Content-Type')
        );
    }
}
```

## See Also

- [[PSR-Standards-Overview|PSR Standards Overview]]
- [[PSR-15-Middleware|PSR-15 Middleware]]
- [[../Roadmap/Architecture-Vision|Architecture Vision]]

## External Resources

- [PSR-7 Specification](https://www.php-fig.org/psr/psr-7/)
- [Nyholm PSR-7 Implementation](https://github.com/Nyholm/psr7)
- [PSR-7 Meta Document](https://www.php-fig.org/psr/psr-7/meta/)

---

#xoops-4.0 #psr-7 #http-messages #request #response
