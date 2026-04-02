# Adding REST API to Your Module

This tutorial extends the Notes module from [[Getting-Started-with-XOOPS-4.0-Module-Development]] by adding a complete REST API. You'll learn API design patterns, JSON responses, authentication, and error handling.

## What We're Building

A RESTful API for the Notes module with:
- CRUD endpoints for notes
- JSON request/response handling
- JWT-based authentication
- Proper HTTP status codes
- API versioning
- Rate limiting
- OpenAPI documentation

## API Design Overview

```
Base URL: /modules/notes/api/v1

Endpoints:
GET    /notes              List user's notes
POST   /notes              Create a new note
GET    /notes/{id}         Get a single note
PUT    /notes/{id}         Update a note
PATCH  /notes/{id}         Partial update
DELETE /notes/{id}         Delete a note
POST   /notes/{id}/archive Archive a note
POST   /notes/{id}/restore Restore from archive
GET    /notes/search       Search notes
```

---

## Part 1: API Infrastructure

### Step 1: Create the API Router

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api;

/**
 * Router - Simple REST API router.
 *
 * Routes incoming requests to appropriate handlers based on
 * HTTP method and URL pattern.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $pattern, callable $handler): self
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $pattern, callable $handler): self
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $pattern, callable $handler): self
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $pattern, callable $handler): self
    {
        return $this->addRoute('PATCH', $pattern, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $pattern, callable $handler): self
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(string $method, string $path): mixed
    {
        $method = strtoupper($method);

        if (!isset($this->routes[$method])) {
            throw new HttpException(405, 'Method Not Allowed');
        }

        foreach ($this->routes[$method] as $pattern => $handler) {
            $params = $this->match($pattern, $path);

            if ($params !== null) {
                return $handler(...$params);
            }
        }

        throw new HttpException(404, 'Not Found');
    }

    private function addRoute(string $method, string $pattern, callable $handler): self
    {
        $this->routes[$method][$pattern] = $handler;
        return $this;
    }

    /**
     * Match a URL pattern against a path.
     *
     * Patterns use {param} syntax for path parameters.
     * Returns array of matched parameters or null if no match.
     */
    private function match(string $pattern, string $path): ?array
    {
        // Convert {param} to named capture groups
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            // Return only named captures
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
```

### Step 2: Create HTTP Exception Classes

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api;

/**
 * HttpException - Represents an HTTP error response.
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message,
        public readonly array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Convert to API response array.
     */
    public function toArray(): array
    {
        $response = [
            'error' => [
                'code' => $this->statusCode,
                'message' => $this->message,
            ],
        ];

        if (!empty($this->errors)) {
            $response['error']['details'] = $this->errors;
        }

        return $response;
    }
}

/**
 * ValidationException - Thrown when request validation fails.
 */
final class ValidationException extends HttpException
{
    public function __construct(array $errors)
    {
        parent::__construct(
            statusCode: 422,
            message: 'Validation failed',
            errors: $errors
        );
    }
}

/**
 * UnauthorizedException - Thrown when authentication fails.
 */
final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct(401, $message);
    }
}

/**
 * ForbiddenException - Thrown when user lacks permission.
 */
final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct(403, $message);
    }
}

/**
 * NotFoundException - Thrown when resource is not found.
 */
final class NotFoundException extends HttpException
{
    public function __construct(string $resource = 'Resource')
    {
        parent::__construct(404, "{$resource} not found");
    }
}
```

### Step 3: Create the JSON Request Handler

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api;

/**
 * Request - Wraps the incoming HTTP request.
 */
final class Request
{
    private ?array $parsedBody = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers
    ) {}

    /**
     * Create from PHP globals.
     */
    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Remove module base path
        $basePath = '/modules/notes/api/v1';
        if (str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        return new self(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            path: $path,
            query: $_GET,
            headers: self::parseHeaders()
        );
    }

    /**
     * Get parsed JSON body.
     */
    public function getBody(): array
    {
        if ($this->parsedBody === null) {
            $input = file_get_contents('php://input');

            if (empty($input)) {
                $this->parsedBody = [];
            } else {
                $this->parsedBody = json_decode($input, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new HttpException(400, 'Invalid JSON: ' . json_last_error_msg());
                }
            }
        }

        return $this->parsedBody;
    }

    /**
     * Get a specific body field.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->getBody()[$key] ?? $default;
    }

    /**
     * Get a query parameter.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a header value.
     */
    public function header(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    /**
     * Get the Bearer token from Authorization header.
     */
    public function getBearerToken(): ?string
    {
        $auth = $this->header('authorization');

        if ($auth && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }

        return null;
    }

    private static function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
```

### Step 4: Create the JSON Response Handler

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api;

/**
 * Response - JSON API response builder.
 */
final class Response
{
    private function __construct(
        private readonly mixed $data,
        private readonly int $statusCode,
        private readonly array $headers
    ) {}

    /**
     * Create a success response.
     */
    public static function json(mixed $data, int $status = 200): self
    {
        return new self($data, $status, []);
    }

    /**
     * Create a created response (201).
     */
    public static function created(mixed $data, ?string $location = null): self
    {
        $headers = $location ? ['Location' => $location] : [];
        return new self($data, 201, $headers);
    }

    /**
     * Create a no content response (204).
     */
    public static function noContent(): self
    {
        return new self(null, 204, []);
    }

    /**
     * Create an error response.
     */
    public static function error(HttpException $e): self
    {
        return new self($e->toArray(), $e->statusCode, []);
    }

    /**
     * Send the response to the client.
     */
    public function send(): never
    {
        http_response_code($this->statusCode);

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($this->data !== null) {
            echo json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
```

---

## Part 2: Authentication

### Step 5: Create JWT Authentication

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api\Auth;

/**
 * JwtAuth - Simple JWT authentication.
 *
 * In production, use a library like firebase/php-jwt.
 * This is a simplified implementation for learning.
 */
final class JwtAuth
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secretKey,
        private readonly int $ttl = 3600 // 1 hour
    ) {}

    /**
     * Generate a JWT token for a user.
     */
    public function generateToken(int $userId, array $claims = []): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $payload = array_merge($claims, [
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + $this->ttl,
        ]);

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->sign("{$headerEncoded}.{$payloadEncoded}");
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Verify and decode a JWT token.
     *
     * @return array The payload claims
     * @throws UnauthorizedException If token is invalid
     */
    public function verifyToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new UnauthorizedException('Invalid token format');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $expectedSignature = $this->sign("{$headerEncoded}.{$payloadEncoded}");

        if (!hash_equals($this->base64UrlEncode($expectedSignature), $signatureEncoded)) {
            throw new UnauthorizedException('Invalid token signature');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            throw new UnauthorizedException('Invalid token payload');
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new UnauthorizedException('Token has expired');
        }

        return $payload;
    }

    /**
     * Get user ID from token.
     */
    public function getUserId(string $token): int
    {
        $payload = $this->verifyToken($token);
        return (int) ($payload['sub'] ?? 0);
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secretKey, true);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
```

### Step 6: Create Authentication Middleware

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api\Middleware;

use Notes\Infrastructure\Api\Request;
use Notes\Infrastructure\Api\Auth\JwtAuth;
use Notes\Infrastructure\Api\UnauthorizedException;

/**
 * AuthMiddleware - Validates JWT tokens.
 */
final class AuthMiddleware
{
    private ?int $authenticatedUserId = null;

    public function __construct(
        private readonly JwtAuth $jwt
    ) {}

    /**
     * Authenticate the request.
     *
     * @throws UnauthorizedException If authentication fails
     */
    public function authenticate(Request $request): int
    {
        $token = $request->getBearerToken();

        if (!$token) {
            throw new UnauthorizedException('No token provided');
        }

        $this->authenticatedUserId = $this->jwt->getUserId($token);

        return $this->authenticatedUserId;
    }

    /**
     * Get the authenticated user ID.
     */
    public function getUserId(): ?int
    {
        return $this->authenticatedUserId;
    }
}
```

---

## Part 3: API Controllers

### Step 7: Create the Base API Controller

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api\Controller;

use Notes\Infrastructure\Api\Request;
use Notes\Infrastructure\Api\Response;
use Notes\Infrastructure\Api\ValidationException;

/**
 * BaseController - Common API controller functionality.
 */
abstract class BaseController
{
    protected ?int $userId = null;

    /**
     * Set the authenticated user ID.
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Validate request data against rules.
     *
     * @param array $data The data to validate
     * @param array $rules Validation rules ['field' => 'rule1|rule2']
     * @throws ValidationException If validation fails
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            $isRequired = in_array('required', $fieldRules, true);

            // Check required
            if ($isRequired && ($value === null || $value === '')) {
                $errors[$field][] = "The {$field} field is required";
                continue;
            }

            // Skip other validations if not required and empty
            if ($value === null || $value === '') {
                continue;
            }

            // Apply other rules
            foreach ($fieldRules as $rule) {
                if ($rule === 'required') {
                    continue;
                }

                $error = $this->applyRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    /**
     * Apply a single validation rule.
     */
    private function applyRule(string $field, mixed $value, string $rule): ?string
    {
        // Parse rule with parameters (e.g., "max:200")
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;

        return match ($ruleName) {
            'string' => is_string($value) ? null : "The {$field} must be a string",
            'int', 'integer' => is_numeric($value) ? null : "The {$field} must be an integer",
            'bool', 'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1'], true)
                ? null : "The {$field} must be a boolean",
            'min' => mb_strlen((string) $value) >= (int) $param
                ? null : "The {$field} must be at least {$param} characters",
            'max' => mb_strlen((string) $value) <= (int) $param
                ? null : "The {$field} cannot exceed {$param} characters",
            'ulid' => preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', (string) $value)
                ? null : "The {$field} must be a valid ULID",
            default => null,
        };
    }

    /**
     * Format pagination metadata.
     */
    protected function paginate(array $items, int $total, int $page, int $perPage): array
    {
        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }
}
```

### Step 8: Create the Notes API Controller

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api\Controller;

use Notes\Application\Command\CreateNoteCommand;
use Notes\Application\Command\CreateNoteHandler;
use Notes\Application\Command\UpdateNoteCommand;
use Notes\Application\Command\UpdateNoteHandler;
use Notes\Application\Command\DeleteNoteCommand;
use Notes\Application\Command\DeleteNoteHandler;
use Notes\Application\Command\ArchiveNoteCommand;
use Notes\Application\Command\ArchiveNoteHandler;
use Notes\Application\Query\GetNoteQuery;
use Notes\Application\Query\GetNoteHandler;
use Notes\Application\Query\GetUserNotesQuery;
use Notes\Application\Query\GetUserNotesHandler;
use Notes\Application\Query\SearchNotesQuery;
use Notes\Application\Query\SearchNotesHandler;
use Notes\Domain\Exception\NoteException;
use Notes\Domain\Exception\NoteNotFound;
use Notes\Infrastructure\Api\Request;
use Notes\Infrastructure\Api\Response;
use Notes\Infrastructure\Api\NotFoundException;
use Notes\Infrastructure\Api\ForbiddenException;

/**
 * NotesApiController - REST API endpoints for notes.
 */
final class NotesApiController extends BaseController
{
    public function __construct(
        private readonly CreateNoteHandler $createHandler,
        private readonly UpdateNoteHandler $updateHandler,
        private readonly DeleteNoteHandler $deleteHandler,
        private readonly ArchiveNoteHandler $archiveHandler,
        private readonly GetNoteHandler $getNoteHandler,
        private readonly GetUserNotesHandler $getUserNotesHandler,
        private readonly SearchNotesHandler $searchHandler
    ) {}

    /**
     * GET /notes - List user's notes.
     *
     * Query params:
     * - page: int (default 1)
     * - per_page: int (default 20, max 100)
     * - archived: bool (default false)
     * - sort: string (created_at|updated_at, default updated_at)
     * - order: string (asc|desc, default desc)
     */
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $includeArchived = filter_var($request->query('archived', false), FILTER_VALIDATE_BOOLEAN);

        $query = new GetUserNotesQuery(
            userId: $this->userId,
            includeArchived: $includeArchived,
            limit: $perPage,
            offset: ($page - 1) * $perPage
        );

        $notes = $this->getUserNotesHandler->handle($query);
        $total = $this->getUserNotesHandler->count($query);

        return Response::json($this->paginate(
            items: array_map([$this, 'formatNote'], $notes),
            total: $total,
            page: $page,
            perPage: $perPage
        ));
    }

    /**
     * POST /notes - Create a new note.
     *
     * Body:
     * - title: string (required, 1-200 chars)
     * - content: string (optional, max 50000 chars)
     */
    public function store(Request $request): Response
    {
        $data = $this->validate($request->getBody(), [
            'title' => 'required|string|min:1|max:200',
            'content' => 'string|max:50000',
        ]);

        $command = new CreateNoteCommand(
            userId: $this->userId,
            title: $data['title'],
            content: $data['content'] ?? ''
        );

        try {
            $note = $this->createHandler->handle($command);

            return Response::created(
                data: ['data' => $this->formatNote($note)],
                location: "/api/v1/notes/{$note->getId()}"
            );
        } catch (NoteException $e) {
            throw new \Notes\Infrastructure\Api\HttpException(422, $e->getMessage());
        }
    }

    /**
     * GET /notes/{id} - Get a single note.
     */
    public function show(string $id): Response
    {
        $this->validate(['id' => $id], ['id' => 'required|ulid']);

        try {
            $query = new GetNoteQuery(
                noteId: $id,
                userId: $this->userId
            );

            $note = $this->getNoteHandler->handle($query);

            return Response::json(['data' => $this->formatNote($note)]);
        } catch (NoteNotFound) {
            throw new NotFoundException('Note');
        } catch (\DomainException $e) {
            throw new ForbiddenException($e->getMessage());
        }
    }

    /**
     * PUT /notes/{id} - Full update of a note.
     *
     * Body:
     * - title: string (required)
     * - content: string (required)
     */
    public function update(Request $request, string $id): Response
    {
        $this->validate(['id' => $id], ['id' => 'required|ulid']);

        $data = $this->validate($request->getBody(), [
            'title' => 'required|string|min:1|max:200',
            'content' => 'required|string|max:50000',
        ]);

        return $this->doUpdate($id, $data['title'], $data['content']);
    }

    /**
     * PATCH /notes/{id} - Partial update of a note.
     *
     * Body:
     * - title: string (optional)
     * - content: string (optional)
     */
    public function patch(Request $request, string $id): Response
    {
        $this->validate(['id' => $id], ['id' => 'required|ulid']);

        $data = $this->validate($request->getBody(), [
            'title' => 'string|min:1|max:200',
            'content' => 'string|max:50000',
        ]);

        return $this->doUpdate(
            $id,
            $data['title'] ?? null,
            $data['content'] ?? null
        );
    }

    /**
     * DELETE /notes/{id} - Delete a note.
     */
    public function destroy(string $id): Response
    {
        $this->validate(['id' => $id], ['id' => 'required|ulid']);

        try {
            $command = new DeleteNoteCommand(
                noteId: $id,
                userId: $this->userId
            );

            $this->deleteHandler->handle($command);

            return Response::noContent();
        } catch (NoteNotFound) {
            throw new NotFoundException('Note');
        } catch (\DomainException $e) {
            throw new ForbiddenException($e->getMessage());
        }
    }

    /**
     * POST /notes/{id}/archive - Archive a note.
     */
    public function archive(string $id): Response
    {
        $this->validate(['id' => $id], ['id' => 'required|ulid']);

        try {
            $command = new ArchiveNoteCommand(
                noteId: $id,
                userId: $this->userId,
                archive: true
            );

            $note = $this->archiveHandler->handle($command);

            return Response::json(['data' => $this->formatNote($note)]);
        } catch (NoteNotFound) {
            throw new NotFoundException('Note');
        } catch (\DomainException $e) {
            throw new ForbiddenException($e->getMessage());
        }
    }

    /**
     * POST /notes/{id}/restore - Restore from archive.
     */
    public function restore(string $id): Response
    {
        $this->validate(['id' => $id], ['id' => 'required|ulid']);

        try {
            $command = new ArchiveNoteCommand(
                noteId: $id,
                userId: $this->userId,
                archive: false
            );

            $note = $this->archiveHandler->handle($command);

            return Response::json(['data' => $this->formatNote($note)]);
        } catch (NoteNotFound) {
            throw new NotFoundException('Note');
        } catch (\DomainException $e) {
            throw new ForbiddenException($e->getMessage());
        }
    }

    /**
     * GET /notes/search - Search notes.
     *
     * Query params:
     * - q: string (required, search term)
     * - in: string (title|content|all, default all)
     */
    public function search(Request $request): Response
    {
        $term = $request->query('q', '');

        if (mb_strlen($term) < 2) {
            return Response::json(['data' => [], 'meta' => ['total' => 0]]);
        }

        $query = new SearchNotesQuery(
            userId: $this->userId,
            term: $term,
            searchIn: $request->query('in', 'all')
        );

        $notes = $this->searchHandler->handle($query);

        return Response::json([
            'data' => array_map([$this, 'formatNote'], $notes),
            'meta' => ['total' => count($notes)],
        ]);
    }

    /**
     * Common update logic for PUT and PATCH.
     */
    private function doUpdate(string $id, ?string $title, ?string $content): Response
    {
        try {
            $command = new UpdateNoteCommand(
                noteId: $id,
                userId: $this->userId,
                title: $title,
                content: $content
            );

            $note = $this->updateHandler->handle($command);

            return Response::json(['data' => $this->formatNote($note)]);
        } catch (NoteNotFound) {
            throw new NotFoundException('Note');
        } catch (\DomainException $e) {
            throw new ForbiddenException($e->getMessage());
        } catch (NoteException $e) {
            throw new \Notes\Infrastructure\Api\HttpException(422, $e->getMessage());
        }
    }

    /**
     * Format a note for API output.
     */
    private function formatNote($note): array
    {
        return [
            'id' => $note->getId()->toString(),
            'type' => 'note',
            'attributes' => [
                'title' => $note->getTitle()->toString(),
                'content' => $note->getContent()->toString(),
                'word_count' => $note->getContent()->getWordCount(),
                'is_archived' => $note->isArchived(),
                'created_at' => $note->getCreatedAt()->format(\DateTimeInterface::RFC3339),
                'updated_at' => $note->getUpdatedAt()->format(\DateTimeInterface::RFC3339),
            ],
            'links' => [
                'self' => "/api/v1/notes/{$note->getId()}",
            ],
        ];
    }
}
```

---

## Part 4: Additional Commands and Handlers

### Step 9: Create Delete and Archive Commands

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Command;

/**
 * DeleteNoteCommand - Request to permanently delete a note.
 */
final readonly class DeleteNoteCommand
{
    public function __construct(
        public string $noteId,
        public int $userId
    ) {}
}

/**
 * DeleteNoteHandler - Executes the delete use case.
 */
final readonly class DeleteNoteHandler
{
    public function __construct(
        private NoteRepositoryInterface $repository
    ) {}

    public function handle(DeleteNoteCommand $command): void
    {
        $noteId = NoteId::fromString($command->noteId);
        $note = $this->repository->findById($noteId);

        if (!$note->canBeEditedBy($command->userId)) {
            throw new \DomainException('You cannot delete this note');
        }

        $this->repository->delete($note);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Notes\Application\Command;

/**
 * ArchiveNoteCommand - Request to archive or restore a note.
 */
final readonly class ArchiveNoteCommand
{
    public function __construct(
        public string $noteId,
        public int $userId,
        public bool $archive = true
    ) {}
}

/**
 * ArchiveNoteHandler - Executes the archive/restore use case.
 */
final readonly class ArchiveNoteHandler
{
    public function __construct(
        private NoteRepositoryInterface $repository
    ) {}

    public function handle(ArchiveNoteCommand $command): Note
    {
        $noteId = NoteId::fromString($command->noteId);
        $note = $this->repository->findById($noteId);

        if (!$note->canBeEditedBy($command->userId)) {
            throw new \DomainException('You cannot modify this note');
        }

        if ($command->archive) {
            $note->archive();
        } else {
            $note->restore();
        }

        $this->repository->save($note);

        return $note;
    }
}
```

---

## Part 5: Wiring the API

### Step 10: Create the API Entry Point

Create `api/v1/index.php`:

```php
<?php

declare(strict_types=1);

/**
 * Notes Module - REST API v1 Entry Point
 *
 * All API requests are routed through this file.
 * URL: /modules/notes/api/v1/*
 */

use Notes\Infrastructure\Api\Router;
use Notes\Infrastructure\Api\Request;
use Notes\Infrastructure\Api\Response;
use Notes\Infrastructure\Api\HttpException;
use Notes\Infrastructure\Api\Auth\JwtAuth;
use Notes\Infrastructure\Api\Middleware\AuthMiddleware;
use Notes\Infrastructure\Api\Controller\NotesApiController;
use Notes\Infrastructure\Api\Controller\AuthController;
use Notes\Infrastructure\Xoops\Container;

// Bootstrap XOOPS (minimal, no template engine)
require_once dirname(__DIR__, 4) . '/mainfile.php';

// Set JSON content type early
header('Content-Type: application/json; charset=utf-8');

// CORS headers (adjust for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // Initialize services
    $container = new Container($GLOBALS['xoopsDB']);
    $jwt = new JwtAuth($GLOBALS['xoopsConfig']['secret_key'] ?? 'change-this-secret');
    $auth = new AuthMiddleware($jwt);

    // Create request
    $request = Request::fromGlobals();

    // Create router
    $router = new Router();

    // Public routes (no auth required)
    $router->post('/auth/login', fn() => (new AuthController($jwt))->login($request));
    $router->post('/auth/refresh', fn() => (new AuthController($jwt))->refresh($request));

    // Protected routes
    $notesController = $container->getNotesApiController();

    // Middleware wrapper for authenticated routes
    $authenticated = function (callable $handler) use ($auth, $request, $notesController) {
        $userId = $auth->authenticate($request);
        $notesController->setUserId($userId);
        return $handler();
    };

    // Notes CRUD
    $router->get('/notes', fn() => $authenticated(
        fn() => $notesController->index($request)
    ));

    $router->post('/notes', fn() => $authenticated(
        fn() => $notesController->store($request)
    ));

    $router->get('/notes/search', fn() => $authenticated(
        fn() => $notesController->search($request)
    ));

    $router->get('/notes/{id}', fn(array $params) => $authenticated(
        fn() => $notesController->show($params['id'])
    ));

    $router->put('/notes/{id}', fn(array $params) => $authenticated(
        fn() => $notesController->update($request, $params['id'])
    ));

    $router->patch('/notes/{id}', fn(array $params) => $authenticated(
        fn() => $notesController->patch($request, $params['id'])
    ));

    $router->delete('/notes/{id}', fn(array $params) => $authenticated(
        fn() => $notesController->destroy($params['id'])
    ));

    $router->post('/notes/{id}/archive', fn(array $params) => $authenticated(
        fn() => $notesController->archive($params['id'])
    ));

    $router->post('/notes/{id}/restore', fn(array $params) => $authenticated(
        fn() => $notesController->restore($params['id'])
    ));

    // Dispatch and send response
    $response = $router->dispatch($request->method, $request->path);
    $response->send();

} catch (HttpException $e) {
    Response::error($e)->send();
} catch (\Throwable $e) {
    // Log the error in production
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());

    // Return generic error (don't expose details in production)
    Response::error(new HttpException(500, 'Internal Server Error'))->send();
}
```

### Step 11: Create .htaccess for Clean URLs

Create `api/v1/.htaccess`:

```apache
# Enable rewriting
RewriteEngine On

# Set base path
RewriteBase /modules/notes/api/v1/

# Skip existing files and directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route everything to index.php
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
```

---

## Part 6: Rate Limiting

### Step 12: Add Rate Limiting Middleware

```php
<?php

declare(strict_types=1);

namespace Notes\Infrastructure\Api\Middleware;

use Notes\Infrastructure\Api\HttpException;

/**
 * RateLimitMiddleware - Prevents API abuse.
 *
 * Uses a simple sliding window algorithm with file-based storage.
 * In production, use Redis for distributed rate limiting.
 */
final class RateLimitMiddleware
{
    public function __construct(
        private readonly int $maxRequests = 60,
        private readonly int $windowSeconds = 60,
        private readonly string $storagePath = '/tmp/rate_limits'
    ) {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Check if request should be rate limited.
     *
     * @throws HttpException 429 Too Many Requests
     */
    public function check(string $identifier): void
    {
        $file = $this->getStorageFile($identifier);
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        // Read existing timestamps
        $timestamps = $this->readTimestamps($file);

        // Filter to current window
        $timestamps = array_filter($timestamps, fn($t) => $t > $windowStart);

        // Check limit
        if (count($timestamps) >= $this->maxRequests) {
            $retryAfter = min($timestamps) + $this->windowSeconds - $now;

            throw new HttpException(
                statusCode: 429,
                message: 'Too Many Requests',
                errors: [
                    'retry_after' => $retryAfter,
                    'limit' => $this->maxRequests,
                    'window' => $this->windowSeconds,
                ]
            );
        }

        // Add current timestamp
        $timestamps[] = $now;
        $this->writeTimestamps($file, $timestamps);
    }

    /**
     * Get remaining requests in current window.
     */
    public function getRemaining(string $identifier): int
    {
        $file = $this->getStorageFile($identifier);
        $windowStart = time() - $this->windowSeconds;

        $timestamps = $this->readTimestamps($file);
        $timestamps = array_filter($timestamps, fn($t) => $t > $windowStart);

        return max(0, $this->maxRequests - count($timestamps));
    }

    private function getStorageFile(string $identifier): string
    {
        return $this->storagePath . '/' . md5($identifier) . '.json';
    }

    private function readTimestamps(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function writeTimestamps(string $file, array $timestamps): void
    {
        file_put_contents($file, json_encode(array_values($timestamps)));
    }
}
```

---

## Part 7: OpenAPI Documentation

### Step 13: Create OpenAPI Specification

Create `api/v1/openapi.yaml`:

```yaml
openapi: 3.1.0
info:
  title: Notes Module API
  description: RESTful API for managing personal notes in XOOPS
  version: 1.0.0
  contact:
    name: XOOPS Development Team
    url: https://xoops.org

servers:
  - url: /modules/notes/api/v1
    description: Local development

security:
  - bearerAuth: []

tags:
  - name: Authentication
    description: User authentication endpoints
  - name: Notes
    description: Note management endpoints

paths:
  /auth/login:
    post:
      tags: [Authentication]
      summary: Authenticate user
      security: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [username, password]
              properties:
                username:
                  type: string
                  example: admin
                password:
                  type: string
                  format: password
      responses:
        '200':
          description: Authentication successful
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AuthResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'

  /notes:
    get:
      tags: [Notes]
      summary: List user's notes
      parameters:
        - name: page
          in: query
          schema:
            type: integer
            default: 1
        - name: per_page
          in: query
          schema:
            type: integer
            default: 20
            maximum: 100
        - name: archived
          in: query
          schema:
            type: boolean
            default: false
      responses:
        '200':
          description: List of notes
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteListResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'

    post:
      tags: [Notes]
      summary: Create a new note
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateNoteRequest'
      responses:
        '201':
          description: Note created
          headers:
            Location:
              schema:
                type: string
              description: URL of created note
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '422':
          $ref: '#/components/responses/ValidationError'

  /notes/{id}:
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          pattern: '^[0-9A-HJKMNP-TV-Z]{26}$'
        description: Note ULID

    get:
      tags: [Notes]
      summary: Get a note
      responses:
        '200':
          description: Note details
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '404':
          $ref: '#/components/responses/NotFound'

    put:
      tags: [Notes]
      summary: Update a note (full)
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/UpdateNoteRequest'
      responses:
        '200':
          description: Note updated
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '404':
          $ref: '#/components/responses/NotFound'
        '422':
          $ref: '#/components/responses/ValidationError'

    patch:
      tags: [Notes]
      summary: Update a note (partial)
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/PatchNoteRequest'
      responses:
        '200':
          description: Note updated
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteResponse'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '404':
          $ref: '#/components/responses/NotFound'

    delete:
      tags: [Notes]
      summary: Delete a note
      responses:
        '204':
          description: Note deleted
        '401':
          $ref: '#/components/responses/Unauthorized'
        '404':
          $ref: '#/components/responses/NotFound'

  /notes/{id}/archive:
    post:
      tags: [Notes]
      summary: Archive a note
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
      responses:
        '200':
          description: Note archived
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteResponse'

  /notes/{id}/restore:
    post:
      tags: [Notes]
      summary: Restore a note from archive
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
      responses:
        '200':
          description: Note restored
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteResponse'

  /notes/search:
    get:
      tags: [Notes]
      summary: Search notes
      parameters:
        - name: q
          in: query
          required: true
          schema:
            type: string
            minLength: 2
        - name: in
          in: query
          schema:
            type: string
            enum: [title, content, all]
            default: all
      responses:
        '200':
          description: Search results
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/NoteListResponse'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    Note:
      type: object
      properties:
        id:
          type: string
          pattern: '^[0-9A-HJKMNP-TV-Z]{26}$'
          example: 01HV8X5Z0KDMVR8SDPY62J9ACP
        type:
          type: string
          enum: [note]
        attributes:
          type: object
          properties:
            title:
              type: string
              maxLength: 200
            content:
              type: string
              maxLength: 50000
            word_count:
              type: integer
            is_archived:
              type: boolean
            created_at:
              type: string
              format: date-time
            updated_at:
              type: string
              format: date-time
        links:
          type: object
          properties:
            self:
              type: string

    CreateNoteRequest:
      type: object
      required: [title]
      properties:
        title:
          type: string
          minLength: 1
          maxLength: 200
        content:
          type: string
          maxLength: 50000
          default: ''

    UpdateNoteRequest:
      type: object
      required: [title, content]
      properties:
        title:
          type: string
          minLength: 1
          maxLength: 200
        content:
          type: string
          maxLength: 50000

    PatchNoteRequest:
      type: object
      properties:
        title:
          type: string
          minLength: 1
          maxLength: 200
        content:
          type: string
          maxLength: 50000

    NoteResponse:
      type: object
      properties:
        data:
          $ref: '#/components/schemas/Note'

    NoteListResponse:
      type: object
      properties:
        data:
          type: array
          items:
            $ref: '#/components/schemas/Note'
        meta:
          type: object
          properties:
            current_page:
              type: integer
            per_page:
              type: integer
            total:
              type: integer
            total_pages:
              type: integer

    AuthResponse:
      type: object
      properties:
        token:
          type: string
        expires_at:
          type: string
          format: date-time

    Error:
      type: object
      properties:
        error:
          type: object
          properties:
            code:
              type: integer
            message:
              type: string
            details:
              type: object

  responses:
    Unauthorized:
      description: Authentication required
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/Error'
          example:
            error:
              code: 401
              message: Unauthorized

    NotFound:
      description: Resource not found
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/Error'
          example:
            error:
              code: 404
              message: Note not found

    ValidationError:
      description: Validation failed
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/Error'
          example:
            error:
              code: 422
              message: Validation failed
              details:
                title: ["The title field is required"]
```

---

## Summary

You've now added a complete REST API to the Notes module:

### What You've Built

1. **API Infrastructure**
   - Router with pattern matching
   - Request/Response handlers
   - HTTP exception hierarchy

2. **Authentication**
   - JWT token generation and validation
   - Auth middleware for protected routes

3. **CRUD Endpoints**
   - Full RESTful resource operations
   - Pagination support
   - Search functionality

4. **Best Practices**
   - Input validation
   - Proper HTTP status codes
   - JSON:API-inspired response format
   - Rate limiting
   - OpenAPI documentation

### API Usage Example

```bash
# Login
curl -X POST /modules/notes/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"secret"}'

# Create note
curl -X POST /modules/notes/api/v1/notes \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"title":"My Note","content":"Hello world"}'

# List notes
curl /modules/notes/api/v1/notes \
  -H "Authorization: Bearer <token>"

# Search
curl "/modules/notes/api/v1/notes/search?q=hello" \
  -H "Authorization: Bearer <token>"
```

### Related Documentation

- [[Getting-Started-with-XOOPS-4.0-Module-Development]]
- [[../Implementation-Guides/Error-Handling-Validation-Guide]]
