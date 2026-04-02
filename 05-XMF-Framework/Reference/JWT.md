---
title: JWT - JSON Web Tokens
description: XMF JWT implementation for secure token-based authentication and AJAX protection
sidebar_position: 1
---

# JWT - JSON Web Tokens

The `Xmf\Jwt` namespace provides JSON Web Token (JWT) support for XOOPS modules. JWTs enable secure, stateless authentication and are particularly useful for protecting AJAX requests.

## What are JSON Web Tokens?

JSON Web Tokens are a standard way to publish a set of *claims* (data) as a text string, with cryptographic verification that the claims have not been tampered with. For detailed specifications, see:

- [jwt.io](https://jwt.io/)
- [RFC 7519](https://tools.ietf.org/html/rfc7519)

### Key Characteristics

- **Signed**: Tokens are cryptographically signed to detect tampering
- **Self-contained**: All necessary information is in the token itself
- **Stateless**: No server-side session storage required
- **Expirable**: Tokens can include expiration times

> **Note:** JWTs are signed, not encrypted. The data is Base64 encoded and visible. Use JWTs for integrity verification, not for hiding sensitive data.

## Why Use JWT in XOOPS?

### The AJAX Token Problem

XOOPS forms use nonce tokens for CSRF protection. However, nonces work poorly with AJAX because:

1. **Single Use**: Nonces are typically valid for one submission
2. **Asynchronous Issues**: Multiple AJAX requests may arrive out of order
3. **Refresh Complexity**: No reliable way to refresh tokens asynchronously
4. **Context Binding**: Standard tokens don't verify which script issued them

### JWT Advantages

JWTs solve these problems by:

- Including an expiration time (`exp` claim) for time-limited validity
- Supporting custom claims to bind tokens to specific scripts
- Enabling multiple requests within the validity period
- Providing cryptographic verification of token origin

## Core Classes

### JsonWebToken

The `Xmf\Jwt\JsonWebToken` class handles token creation and decoding.

```php
use Xmf\Jwt\JsonWebToken;
use Xmf\Jwt\KeyFactory;

// Create a key
$key = KeyFactory::build('my_application_key');

// Create a JsonWebToken instance
$jwt = new JsonWebToken($key, 'HS256');

// Create a token
$payload = ['user_id' => 123, 'aud' => 'myaction'];
$token = $jwt->create($payload, 300); // Expires in 300 seconds

// Decode and verify a token
$assertClaims = ['aud' => 'myaction'];
$decoded = $jwt->decode($tokenString, $assertClaims);
```

#### Methods

**`new JsonWebToken($key, $algorithm)`**

Creates a new JWT handler.
- `$key`: A `Xmf\Key\KeyAbstract` object
- `$algorithm`: Signing algorithm (default: 'HS256')

**`create($payload, $expirationOffset)`**

Creates a signed token string.
- `$payload`: Array of claims
- `$expirationOffset`: Seconds until expiration (optional)

**`decode($jwtString, $assertClaims)`**

Decodes and validates a token.
- `$jwtString`: The token to decode
- `$assertClaims`: Claims to verify (empty array for none)
- Returns: stdClass payload or false if invalid

**`setAlgorithm($algorithm)`**

Changes the signing/verification algorithm.

### TokenFactory

The `Xmf\Jwt\TokenFactory` provides a convenient way to create tokens.

```php
use Xmf\Jwt\TokenFactory;

// Create a token with automatic key handling
$claims = [
    'aud' => 'myaction.php',
    'user_id' => $userId,
    'item_id' => $itemId
];

$token = TokenFactory::build('my_key', $claims, 120);
// Token expires in 120 seconds
```

**`TokenFactory::build($key, $payload, $expirationOffset)`**

- `$key`: Key name string or KeyAbstract object
- `$payload`: Array of claims
- `$expirationOffset`: Expiration in seconds

Throws exceptions on failure: `DomainException`, `InvalidArgumentException`, `UnexpectedValueException`

### TokenReader

The `Xmf\Jwt\TokenReader` class simplifies reading tokens from various sources.

```php
use Xmf\Jwt\TokenReader;

$assertClaims = ['aud' => 'myaction.php'];

// From a string
$payload = TokenReader::fromString('my_key', $tokenString, $assertClaims);

// From a cookie
$payload = TokenReader::fromCookie('my_key', 'token_cookie', $assertClaims);

// From a request parameter
$payload = TokenReader::fromRequest('my_key', 'token', $assertClaims);

// From Authorization header (Bearer token)
$payload = TokenReader::fromHeader('my_key', $assertClaims);
```

All methods return the payload as `stdClass` or `false` if invalid.

### KeyFactory

The `Xmf\Jwt\KeyFactory` creates and manages cryptographic keys.

```php
use Xmf\Jwt\KeyFactory;

// Build a key (creates if it doesn't exist)
$key = KeyFactory::build('my_application_key');

// With custom storage
$storage = new \Xmf\Key\FileStorage('/custom/path');
$key = KeyFactory::build('my_key', $storage);
```

Keys are stored persistently. The default storage uses the file system.

## AJAX Protection Example

Here is a complete example demonstrating JWT-protected AJAX.

### Page Script (Generates Token)

```php
<?php
use Xmf\Jwt\TokenFactory;
use Xmf\Jwt\TokenReader;
use Xmf\Module\Helper;
use Xmf\Request;

require_once dirname(dirname(__DIR__)) . '/mainfile.php';

// Claims to include and verify
$assertClaims = ['aud' => basename(__FILE__)];

// Check if this is an AJAX request
$isAjax = (0 === strcasecmp(Request::getHeader('X-Requested-With', ''), 'XMLHttpRequest'));

if ($isAjax) {
    // Handle AJAX request
    $GLOBALS['xoopsLogger']->activated = false;

    // Verify the token from the Authorization header
    $token = TokenReader::fromHeader('ajax_key', $assertClaims);

    if (false === $token) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }

    // Token is valid - process the request
    $action = Request::getCmd('action', '');
    $itemId = isset($token->item_id) ? $token->item_id : 0;

    // Your AJAX logic here
    $response = ['success' => true, 'item_id' => $itemId];

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Regular page request - generate token and display page
require_once XOOPS_ROOT_PATH . '/header.php';

$helper = Helper::getHelper(basename(__DIR__));

// Create token with claims
$claims = array_merge($assertClaims, [
    'item_id' => 42,
    'user_id' => $GLOBALS['xoopsUser']->getVar('uid')
]);

// Token valid for 2 minutes
$token = TokenFactory::build('ajax_key', $claims, 120);

// JavaScript for AJAX calls
$script = <<<JS
<script>
function performAction(action) {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { action: action },
        dataType: 'json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer {$token}');
        },
        success: function(data) {
            if (data.success) {
                console.log('Action completed:', data);
                // Update UI
            }
        },
        error: function(xhr, status, error) {
            if (xhr.status === 401) {
                alert('Session expired. Please refresh the page.');
            } else {
                alert('An error occurred: ' + error);
            }
        }
    });
}
</script>
JS;

echo $script;
echo '<button onclick="performAction(\'save\')">Save Item</button>';
echo '<button onclick="performAction(\'delete\')">Delete Item</button>';

require_once XOOPS_ROOT_PATH . '/footer.php';
```

## Best Practices

### Token Expiration

Set appropriate expiration times based on use case:

```php
// Short-lived for sensitive operations (2 minutes)
$token = TokenFactory::build('key', $claims, 120);

// Longer for general page interactions (30 minutes)
$token = TokenFactory::build('key', $claims, 1800);
```

### Claim Verification

Always verify the `aud` (audience) claim to ensure tokens are used with the intended script:

```php
// When creating
$claims = ['aud' => 'process_order.php', 'order_id' => 123];

// When verifying
$assertClaims = ['aud' => 'process_order.php'];
$token = TokenReader::fromHeader('key', $assertClaims);
```

### Key Naming

Use descriptive key names for different purposes:

```php
// Separate keys for different features
$orderToken = TokenFactory::build('order_processing', $orderClaims, 300);
$commentToken = TokenFactory::build('comment_system', $commentClaims, 600);
```

### Error Handling

```php
use Xmf\Jwt\TokenFactory;
use Xmf\Jwt\TokenReader;

try {
    $token = TokenFactory::build('my_key', $claims, 300);
} catch (\DomainException $e) {
    // Invalid algorithm
    error_log('JWT Error: ' . $e->getMessage());
} catch (\InvalidArgumentException $e) {
    // Invalid argument
    error_log('JWT Error: ' . $e->getMessage());
} catch (\UnexpectedValueException $e) {
    // Unexpected value
    error_log('JWT Error: ' . $e->getMessage());
}

// Reading tokens returns false on failure (no exception)
$payload = TokenReader::fromHeader('my_key', $assertClaims);
if ($payload === false) {
    // Token invalid, expired, or tampered
}
```

## Token Transport Methods

### Authorization Header (Recommended)

```javascript
xhr.setRequestHeader('Authorization', 'Bearer ' + token);
```

```php
$payload = TokenReader::fromHeader('key', $assertClaims);
```

### Cookie

```php
// Set cookie with token
setcookie('api_token', $token, time() + 300, '/', '', true, true);

// Read from cookie
$payload = TokenReader::fromCookie('key', 'api_token', $assertClaims);
```

### Request Parameter

```javascript
$.ajax({
    url: 'handler.php',
    data: { token: token, action: 'save' }
});
```

```php
$payload = TokenReader::fromRequest('key', 'token', $assertClaims);
```

## Security Considerations

1. **Use HTTPS**: Always use HTTPS to prevent token interception
2. **Short Expiration**: Use the shortest practical expiration time
3. **Specific Claims**: Include claims that tie tokens to specific contexts
4. **Server-Side Validation**: Always validate tokens server-side
5. **Don't Store Sensitive Data**: Remember tokens are readable (not encrypted)

## API Reference

### Xmf\Jwt\JsonWebToken

| Method | Description |
|--------|-------------|
| `__construct($key, $algorithm)` | Create JWT handler |
| `setAlgorithm($algorithm)` | Set signing algorithm |
| `create($payload, $expiration)` | Create signed token |
| `decode($token, $assertClaims)` | Decode and verify token |

### Xmf\Jwt\TokenFactory

| Method | Description |
|--------|-------------|
| `build($key, $payload, $expiration)` | Create token string |

### Xmf\Jwt\TokenReader

| Method | Description |
|--------|-------------|
| `fromString($key, $token, $claims)` | Decode from string |
| `fromCookie($key, $name, $claims)` | Decode from cookie |
| `fromRequest($key, $name, $claims)` | Decode from request |
| `fromHeader($key, $claims, $header)` | Decode from header |

### Xmf\Jwt\KeyFactory

| Method | Description |
|--------|-------------|
| `build($name, $storage)` | Get or create key |

## See Also

- [[../Basics/XMF-Request]] - Request handling
- [[../XMF-Framework]] - Framework overview
- [[Database]] - Database utilities

---

#xmf #jwt #security #ajax #authentication #tokens
