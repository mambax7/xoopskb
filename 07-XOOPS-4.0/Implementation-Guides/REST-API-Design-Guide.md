---
title: REST API Design Guide
description: Complete guide to building RESTful APIs in XOOPS 4.0
created: 2026-01-29
updated: 2026-01-29
version: 1.0.0
author: XOOPS Team
category: implementation-guide
xoops_version: "2026"
php_version: "8.4+"
tags:
  - rest
  - api
  - http
  - json
status: stable
---

# 🌐 REST API Design Guide

> **Build robust, well-documented RESTful APIs for XOOPS 4.0 modules.**

REST APIs enable external applications to interact with your XOOPS modules. This guide covers best practices for designing, implementing, and documenting APIs.

---

## API Architecture Overview

```mermaid
flowchart TB
    subgraph Client["Client Applications"]
        WEB[Web App]
        MOB[Mobile App]
        CLI[CLI Tool]
        EXT[Third Party]
    end

    subgraph API["XOOPS API Layer"]
        GW[API Gateway]
        AUTH[Authentication]
        RATE[Rate Limiting]
        ROUTE[Router]
    end

    subgraph Handlers["Request Handlers"]
        CTRL[API Controllers]
        VAL[Validators]
        TRANS[Transformers]
    end

    subgraph Core["Business Logic"]
        SVC[Services]
        REPO[Repositories]
        ENT[Entities]
    end

    Client --> GW
    GW --> AUTH --> RATE --> ROUTE
    ROUTE --> Handlers
    Handlers --> Core
```

---

## Configuration

### Enable API in module.json

```json
{
    "api": {
        "enabled": true,
        "version": "1.0",
        "prefix": "/api/v1/articles",
        "authentication": ["bearer", "api_key"],
        "rate_limit": {
            "requests": 100,
            "window": 60
        },
        "cors": {
            "origins": ["*"],
            "methods": ["GET", "POST", "PUT", "DELETE"],
            "headers": ["Authorization", "Content-Type"]
        }
    }
}
```

### Route Definition

```php
<?php
// config/api-routes.php

declare(strict_types=1);

use Xoops\Routing\Router;

return static function (Router $router): void {
    $router->group([
        'prefix' => '/api/v1/articles',
        'middleware' => ['api', 'throttle:100,60'],
    ], function (Router $router) {

        // Collection routes
        $router->get('/', [ArticleApiController::class, 'index']);
        $router->post('/', [ArticleApiController::class, 'store']);

        // Resource routes
        $router->get('/{id}', [ArticleApiController::class, 'show']);
        $router->put('/{id}', [ArticleApiController::class, 'update']);
        $router->delete('/{id}', [ArticleApiController::class, 'destroy']);

        // Nested resources
        $router->get('/{id}/comments', [CommentApiController::class, 'index']);
        $router->post('/{id}/comments', [CommentApiController::class, 'store']);

        // Actions
        $router->post('/{id}/publish', [ArticleApiController::class, 'publish']);
        $router->post('/{id}/archive', [ArticleApiController::class, 'archive']);
    });
};
```

---

## RESTful URL Design

### Resource Naming

```mermaid
flowchart LR
    subgraph Good["✅ Good URLs"]
        G1["/api/v1/articles"]
        G2["/api/v1/articles/123"]
        G3["/api/v1/articles/123/comments"]
        G4["/api/v1/users/456/articles"]
    end

    subgraph Bad["❌ Bad URLs"]
        B1["/api/v1/getArticles"]
        B2["/api/v1/article/get/123"]
        B3["/api/v1/articleComments/123"]
        B4["/api/v1/get-user-articles"]
    end
```

### HTTP Methods

| Method | URL | Action | Description |
|--------|-----|--------|-------------|
| GET | `/articles` | index | List all articles |
| POST | `/articles` | store | Create new article |
| GET | `/articles/{id}` | show | Get single article |
| PUT | `/articles/{id}` | update | Update entire article |
| PATCH | `/articles/{id}` | update | Partial update |
| DELETE | `/articles/{id}` | destroy | Delete article |

### URL Patterns

```
# Collection
GET    /api/v1/articles              # List articles
POST   /api/v1/articles              # Create article

# Single resource
GET    /api/v1/articles/123          # Get article 123
PUT    /api/v1/articles/123          # Update article 123
DELETE /api/v1/articles/123          # Delete article 123

# Nested resources
GET    /api/v1/articles/123/comments # List comments for article 123
POST   /api/v1/articles/123/comments # Add comment to article 123

# Filtering & searching
GET    /api/v1/articles?status=published
GET    /api/v1/articles?category=5&author=10
GET    /api/v1/articles?search=xoops

# Pagination
GET    /api/v1/articles?page=2&per_page=20

# Sorting
GET    /api/v1/articles?sort=created_at&order=desc

# Including relations
GET    /api/v1/articles?include=author,comments,tags
```

---

## API Controllers

### Basic Controller

```php
<?php

declare(strict_types=1);

namespace MyModule\Controller\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xoops\Http\Controller\ApiController;

final class ArticleApiController extends ApiController
{
    public function __construct(
        private readonly ArticleService $service,
        private readonly ArticleTransformer $transformer,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $articles = $this->service->paginate(
            page: (int) ($params['page'] ?? 1),
            perPage: (int) ($params['per_page'] ?? 15),
            filters: $this->extractFilters($params),
        );

        return $this->json([
            'data' => $this->transformer->collection($articles->items()),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'last_page' => $articles->lastPage(),
            ],
            'links' => [
                'first' => $this->buildPageUrl(1),
                'last' => $this->buildPageUrl($articles->lastPage()),
                'prev' => $articles->currentPage() > 1
                    ? $this->buildPageUrl($articles->currentPage() - 1)
                    : null,
                'next' => $articles->hasMorePages()
                    ? $this->buildPageUrl($articles->currentPage() + 1)
                    : null,
            ],
        ]);
    }

    public function show(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $article = $this->service->find($id);

        if ($article === null) {
            return $this->notFound('Article not found');
        }

        return $this->json([
            'data' => $this->transformer->transform($article),
        ]);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $validator = $this->validate($data, [
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $article = $this->service->create(
            new CreateArticleCommand(
                title: $data['title'],
                content: $data['content'],
                categoryId: $data['category_id'],
                authorId: $request->getAttribute('user')->id,
            )
        );

        return $this->created([
            'data' => $this->transformer->transform($article),
        ]);
    }

    public function update(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $article = $this->service->find($id);

        if ($article === null) {
            return $this->notFound('Article not found');
        }

        $data = $request->getParsedBody();

        $validator = $this->validate($data, [
            'title' => 'string|min:3|max:255',
            'content' => 'string',
            'category_id' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $article = $this->service->update($id, $data);

        return $this->json([
            'data' => $this->transformer->transform($article),
        ]);
    }

    public function destroy(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $article = $this->service->find($id);

        if ($article === null) {
            return $this->notFound('Article not found');
        }

        $this->service->delete($id);

        return $this->noContent();
    }
}
```

---

## Response Formats

### Success Response

```json
{
    "data": {
        "id": 123,
        "type": "article",
        "attributes": {
            "title": "Getting Started with XOOPS 4.0",
            "slug": "getting-started-xoops-4.0",
            "content": "Article content here...",
            "status": "published",
            "created_at": "2026-01-29T10:30:00Z",
            "updated_at": "2026-01-29T14:45:00Z",
            "published_at": "2026-01-29T12:00:00Z"
        },
        "relationships": {
            "author": {
                "data": {"type": "user", "id": 456}
            },
            "category": {
                "data": {"type": "category", "id": 5}
            }
        }
    },
    "included": [
        {
            "id": 456,
            "type": "user",
            "attributes": {
                "name": "John Doe",
                "email": "john@example.com"
            }
        }
    ]
}
```

### Collection Response

```json
{
    "data": [
        {"id": 1, "type": "article", "attributes": {...}},
        {"id": 2, "type": "article", "attributes": {...}},
        {"id": 3, "type": "article", "attributes": {...}}
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 47,
        "last_page": 4
    },
    "links": {
        "first": "/api/v1/articles?page=1",
        "last": "/api/v1/articles?page=4",
        "prev": null,
        "next": "/api/v1/articles?page=2"
    }
}
```

### Error Response

```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": [
            {
                "field": "title",
                "message": "The title field is required."
            },
            {
                "field": "category_id",
                "message": "The selected category is invalid."
            }
        ]
    }
}
```

---

## HTTP Status Codes

```mermaid
flowchart TB
    subgraph Success["2xx Success"]
        S200[200 OK]
        S201[201 Created]
        S204[204 No Content]
    end

    subgraph ClientError["4xx Client Error"]
        E400[400 Bad Request]
        E401[401 Unauthorized]
        E403[403 Forbidden]
        E404[404 Not Found]
        E422[422 Validation Error]
        E429[429 Too Many Requests]
    end

    subgraph ServerError["5xx Server Error"]
        E500[500 Internal Error]
        E503[503 Service Unavailable]
    end
```

| Code | Meaning | Use Case |
|------|---------|----------|
| 200 | OK | Successful GET, PUT, PATCH |
| 201 | Created | Successful POST (resource created) |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Malformed request |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

---

## Authentication

### Bearer Token

```php
<?php
// Middleware checks Authorization header
// Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

final class BearerAuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $header = $request->getHeaderLine('Authorization');

        if (!str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('Missing bearer token');
        }

        $token = substr($header, 7);
        $user = $this->tokenService->validateAndGetUser($token);

        if ($user === null) {
            return $this->unauthorized('Invalid or expired token');
        }

        return $handler->handle(
            $request->withAttribute('user', $user)
        );
    }
}
```

### API Key

```php
<?php
// Header: X-API-Key: your-api-key-here

final class ApiKeyMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $apiKey = $request->getHeaderLine('X-API-Key');

        if (empty($apiKey)) {
            return $this->unauthorized('Missing API key');
        }

        $client = $this->apiKeyService->validate($apiKey);

        if ($client === null) {
            return $this->unauthorized('Invalid API key');
        }

        return $handler->handle(
            $request
                ->withAttribute('api_client', $client)
                ->withAttribute('user', $client->getUser())
        );
    }
}
```

### Authentication Flow

```mermaid
sequenceDiagram
    participant C as Client
    participant API as API Gateway
    participant AUTH as Auth Service
    participant H as Handler

    C->>API: POST /api/auth/login
    API->>AUTH: Validate credentials
    AUTH-->>API: JWT Token
    API-->>C: 200 {token: "eyJ..."}

    C->>API: GET /api/articles (Bearer token)
    API->>AUTH: Validate token
    AUTH-->>API: User context
    API->>H: Forward request
    H-->>API: Response
    API-->>C: 200 {data: [...]}
```

---

## Data Transformers

### Transformer Class

```php
<?php

declare(strict_types=1);

namespace MyModule\Transformer;

use Xoops\Api\Transformer;

final class ArticleTransformer extends Transformer
{
    protected array $availableIncludes = [
        'author',
        'category',
        'comments',
        'tags',
    ];

    protected array $defaultIncludes = [
        'author',
    ];

    public function transform(Article $article): array
    {
        return [
            'id' => $article->getId(),
            'type' => 'article',
            'attributes' => [
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'excerpt' => $article->getExcerpt(),
                'content' => $article->getContent(),
                'status' => $article->getStatus()->value,
                'views' => $article->getViews(),
                'created_at' => $article->getCreatedAt()->format('c'),
                'updated_at' => $article->getUpdatedAt()?->format('c'),
                'published_at' => $article->getPublishedAt()?->format('c'),
            ],
            'links' => [
                'self' => "/api/v1/articles/{$article->getId()}",
                'web' => "/articles/{$article->getSlug()}",
            ],
        ];
    }

    public function includeAuthor(Article $article): array
    {
        $author = $article->getAuthor();

        return [
            'data' => [
                'id' => $author->getId(),
                'type' => 'user',
                'attributes' => [
                    'name' => $author->getName(),
                    'avatar' => $author->getAvatarUrl(),
                ],
            ],
        ];
    }

    public function includeComments(Article $article): array
    {
        $comments = $article->getComments();
        $transformer = new CommentTransformer();

        return [
            'data' => array_map(
                fn($c) => $transformer->transform($c),
                $comments->toArray()
            ),
            'meta' => [
                'count' => $comments->count(),
            ],
        ];
    }
}
```

---

## Validation

### Request Validation

```php
<?php

declare(strict_types=1);

namespace MyModule\Request;

use Xoops\Http\Request\FormRequest;

final class CreateArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
                'min:50',
            ],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            'tags' => [
                'array',
                'max:10',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
            'publish_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please provide an article title.',
            'content.min' => 'Article content must be at least 50 characters.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
```

---

## Rate Limiting

### Configuration

```php
<?php

// Apply to routes
$router->group([
    'middleware' => ['throttle:100,60'], // 100 requests per 60 seconds
], function ($router) {
    // ...
});

// Different limits for different endpoints
$router->get('/articles', 'index')->middleware('throttle:200,60');
$router->post('/articles', 'store')->middleware('throttle:10,60');
```

### Rate Limit Headers

```http
HTTP/1.1 200 OK
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1706529600
```

```mermaid
flowchart LR
    subgraph Limiting["Rate Limit Flow"]
        REQ[Request] --> CHECK{Under Limit?}
        CHECK -->|Yes| PROCESS[Process Request]
        CHECK -->|No| REJECT[429 Too Many Requests]
        PROCESS --> DEC[Decrement Counter]
        REJECT --> RETRY[Retry-After Header]
    end
```

---

## API Versioning

### URL Versioning

```
/api/v1/articles
/api/v2/articles
```

### Header Versioning

```http
GET /api/articles HTTP/1.1
Accept: application/vnd.xoops.v2+json
```

### Version Handling

```php
<?php

$router->group(['prefix' => '/api/v1'], function ($router) {
    $router->get('/articles', [ArticleV1Controller::class, 'index']);
});

$router->group(['prefix' => '/api/v2'], function ($router) {
    $router->get('/articles', [ArticleV2Controller::class, 'index']);
});
```

---

## API Documentation

### OpenAPI Specification

```yaml
openapi: 3.0.3
info:
  title: Articles API
  version: 1.0.0
  description: API for managing articles

paths:
  /api/v1/articles:
    get:
      summary: List articles
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
            default: 15
            maximum: 100
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ArticleCollection'

    post:
      summary: Create article
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateArticle'
      responses:
        '201':
          description: Article created
        '422':
          description: Validation error

components:
  schemas:
    Article:
      type: object
      properties:
        id:
          type: integer
        title:
          type: string
        content:
          type: string
        status:
          type: string
          enum: [draft, published, archived]

  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

---

## 🔗 Related Documentation

- [[PSR-15-Middleware-Guide|PSR-15 Middleware]]
- [[PSR-11-Dependency-Injection-Guide|Dependency Injection]]
- [[../Roadmap/Architecture-Vision|Architecture Vision]]

---

#rest #api #http #json #xoops-4.0
