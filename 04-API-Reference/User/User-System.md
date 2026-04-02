---
title: XOOPS User System
description: XoopsUser class, XoopsGroup management, user authentication, session handling, and access control
created: 2024-01-31
updated: 2024-01-31
version: 2.5.11
tags:
  - api
  - user
  - authentication
  - groups
  - sessions
  - permissions
aliases:
  - User System
  - XoopsUser
  - User Authentication
  - Group Management
---

# XOOPS User System

The XOOPS User System manages user accounts, authentication, authorization, group membership, and session management. It provides a robust framework for securing your application and controlling user access.

## User System Architecture

```mermaid
graph TD
    A[User System] -->|manages| B[XoopsUser]
    A -->|manages| C[XoopsGroup]
    A -->|handles| D[Authentication]
    A -->|handles| E[Sessions]

    D -->|validates| F[Username/Password]
    D -->|validates| G[Email/Token]
    D -->|triggers| H[Post-Login Hooks]

    E -->|manages| I[Session Data]
    E -->|manages| J[Session Cookies]

    B -->|belongs to| C
    B -->|has| K[Permissions]
    B -->|has| L[Profile Data]

    C -->|defines| M[Access Levels]
    C -->|contains| N[Multiple Users]
```

## XoopsUser Class

The main user object class representing a user account.

### Class Overview

```php
namespace Xoops\Core\User;

class XoopsUser extends XoopsObject
{
    protected int $uid = 0;
    protected string $uname = '';
    protected string $email = '';
    protected string $pass = '';
    protected int $uregdate = 0;
    protected int $ulevel = 0;
    protected array $groups = [];
    protected array $permissions = [];
}
```

### Constructor

```php
public function __construct(int $uid = null)
```

Creates a new user object, optionally loading from database by ID.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | int | User ID to load (optional) |

**Example:**
```php
// Create new user
$user = new XoopsUser();

// Load existing user
$user = new XoopsUser(123);
```

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `uid` | int | User ID |
| `uname` | string | Username |
| `email` | string | Email address |
| `pass` | string | Password hash |
| `uregdate` | int | Registration timestamp |
| `ulevel` | int | User level (9=admin, 1=user) |
| `groups` | array | Group IDs |
| `permissions` | array | Permission flags |

### Core Methods

#### getID / getUid

Gets the user's ID.

```php
public function getID(): int
public function getUid(): int  // Alias
```

**Returns:** `int` - User ID

**Example:**
```php
$user = new XoopsUser(1);
echo $user->getID(); // 1
echo $user->getUid(); // 1
```

#### getUnameReal

Gets the user's display name.

```php
public function getUnameReal(): string
```

**Returns:** `string` - User's real name

**Example:**
```php
$realName = $user->getUnameReal();
echo "Hello, $realName";
```

#### getEmail

Gets the user's email address.

```php
public function getEmail(): string
```

**Returns:** `string` - Email address

**Example:**
```php
$email = $user->getEmail();
mail($email, 'Welcome', 'Welcome to XOOPS');
```

#### getVar / setVar

Gets or sets a user variable.

```php
public function getVar(string $key, string $format = 's'): mixed
public function setVar(string $key, mixed $value, bool $notGpc = false): bool
```

**Example:**
```php
// Get values
$username = $user->getVar('uname');
$email = $user->getVar('email', 's'); // Formatted for display

// Set values
$user->setVar('uname', 'newusername');
$user->setVar('email', 'user@example.com');
```

#### getGroups

Gets the user's group memberships.

```php
public function getGroups(): array
```

**Returns:** `array` - Array of group IDs

**Example:**
```php
$groups = $user->getGroups();
echo "Member of " . count($groups) . " groups";
```

#### isInGroup

Checks if user belongs to a group.

```php
public function isInGroup(int $groupId): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$groupId` | int | Group ID to check |

**Returns:** `bool` - True if in group

**Example:**
```php
if ($user->isInGroup(1)) { // 1 = Webmasters
    echo 'User is a webmaster';
}
```

#### isAdmin

Checks if user is an administrator.

```php
public function isAdmin(): bool
```

**Returns:** `bool` - True if admin

**Example:**
```php
if ($user->isAdmin()) {
    // Show admin controls
    echo '<a href="admin/">Admin Panel</a>';
}
```

#### getProfile

Gets user profile information.

```php
public function getProfile(): array
```

**Returns:** `array` - Profile data

**Example:**
```php
$profile = $user->getProfile();
echo 'Bio: ' . $profile['bio'];
```

#### isActive

Checks if user account is active.

```php
public function isActive(): bool
```

**Returns:** `bool` - True if active

**Example:**
```php
if ($user->isActive()) {
    // Allow user access
} else {
    // Restrict access
}
```

#### updateLastLogin

Updates the user's last login timestamp.

```php
public function updateLastLogin(): bool
```

**Returns:** `bool` - True on success

**Example:**
```php
if ($user->updateLastLogin()) {
    echo 'Login recorded';
}
```

## XoopsGroup Class

Manages user groups and permissions.

### Class Overview

```php
namespace Xoops\Core\User;

class XoopsGroup extends XoopsObject
{
    protected int $groupid = 0;
    protected string $name = '';
    protected string $description = '';
    protected int $group_type = 0;
    protected array $users = [];
}
```

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `TYPE_NORMAL` | 0 | Normal user group |
| `TYPE_ADMIN` | 1 | Administrative group |
| `TYPE_SYSTEM` | 2 | System group |

### Methods

#### getName

Gets the group name.

```php
public function getName(): string
```

**Returns:** `string` - Group name

**Example:**
```php
$group = new XoopsGroup(1);
echo $group->getName(); // "Webmasters"
```

#### getDescription

Gets the group description.

```php
public function getDescription(): string
```

**Returns:** `string` - Description

**Example:**
```php
echo $group->getDescription();
```

#### getUsers

Gets group members.

```php
public function getUsers(): array
```

**Returns:** `array` - Array of user IDs

**Example:**
```php
$users = $group->getUsers();
echo "Group has " . count($users) . " members";
```

#### addUser

Adds a user to the group.

```php
public function addUser(int $uid): bool
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uid` | int | User ID |

**Returns:** `bool` - True on success

**Example:**
```php
$group = new XoopsGroup(2); // Editors
$group->addUser(123);
$groupHandler->insert($group);
```

#### removeUser

Removes a user from the group.

```php
public function removeUser(int $uid): bool
```

**Example:**
```php
$group->removeUser(123);
```

## User Authentication

### Login Process

```php
/**
 * User login
 */
function xoops_user_login(string $uname, string $pass, bool $rememberMe = false): ?XoopsUser
{
    global $xoopsDB;

    // Sanitize username
    $uname = trim($uname);

    // Get user from database
    $query = $xoopsDB->prepare(
        'SELECT * FROM ' . $xoopsDB->prefix('users') .
        ' WHERE uname = ? AND active = 1'
    );
    $query->bind_param('s', $uname);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        return null; // User not found
    }

    $row = $result->fetch_assoc();

    // Verify password
    if (!password_verify($pass, $row['pass'])) {
        return null; // Invalid password
    }

    // Load user object
    $user = new XoopsUser($row['uid']);

    // Update last login
    $user->updateLastLogin();

    // Handle "Remember Me"
    if ($rememberMe) {
        // Set persistent cookie
        setcookie(
            'xoops_user_remember',
            $user->uid(),
            time() + (30 * 24 * 60 * 60), // 30 days
            '/',
            $_SERVER['HTTP_HOST'] ?? ''
        );
    }

    return $user;
}
```

### Password Management

```php
/**
 * Hash password securely
 */
function xoops_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, [
        'cost' => 12
    ]);
}

/**
 * Verify password
 */
function xoops_verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function xoops_password_needs_rehash(string $hash): bool
{
    return password_needs_rehash($hash, PASSWORD_BCRYPT, [
        'cost' => 12
    ]);
}
```

## Session Management

### Session Class

```php
namespace Xoops\Core;

class SessionManager
{
    protected array $data = [];
    protected string $sessionId = '';

    public function start(): void {}
    public function get(string $key): mixed {}
    public function set(string $key, mixed $value): void {}
    public function destroy(): void {}
}
```

### Session Methods

#### Start Session

```php
<?php
session_start();

// Regenerate session ID for security
session_regenerate_id(true);

// Set session timeout
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Store user in session
if ($user) {
    $_SESSION['xoops_user'] = $user;
    $_SESSION['xoops_uid'] = $user->getID();
    $_SESSION['xoops_uname'] = $user->getVar('uname');
}
```

#### Check Session

```php
/**
 * Get current user from session
 */
function xoops_get_current_user(): ?XoopsUser
{
    if (isset($_SESSION['xoops_user']) && $_SESSION['xoops_user'] instanceof XoopsUser) {
        return $_SESSION['xoops_user'];
    }
    return null;
}

/**
 * Check if user is logged in
 */
function xoops_is_user_logged_in(): bool
{
    return isset($_SESSION['xoops_uid']) && $_SESSION['xoops_uid'] > 0;
}
```

#### Destroy Session

```php
/**
 * User logout
 */
function xoops_user_logout()
{
    global $xoopsUser;

    // Log the logout
    if ($xoopsUser) {
        error_log('User ' . $xoopsUser->getVar('uname') . ' logged out');
    }

    // Destroy session data
    $_SESSION = [];

    // Delete session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy session
    session_destroy();
}
```

## Permission System

### Permission Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `XOOPS_PERMISSION_NONE` | 0 | No permission |
| `XOOPS_PERMISSION_VIEW` | 1 | View content |
| `XOOPS_PERMISSION_SUBMIT` | 2 | Submit content |
| `XOOPS_PERMISSION_EDIT` | 4 | Edit content |
| `XOOPS_PERMISSION_DELETE` | 8 | Delete content |
| `XOOPS_PERMISSION_ADMIN` | 16 | Admin access |

### Permission Checking

```php
/**
 * Check if user has permission
 */
function xoops_check_permission($user, $resource, $permission)
{
    if (!$user) {
        return false;
    }

    // Admins have all permissions
    if ($user->isAdmin()) {
        return true;
    }

    // Check group permissions
    $groups = $user->getGroups();
    foreach ($groups as $groupId) {
        if (xoops_group_has_permission($groupId, $resource, $permission)) {
            return true;
        }
    }

    return false;
}
```

## User Handler

The UserHandler manages user persistence operations.

```php
/**
 * Get user handler
 */
$userHandler = xoops_getHandler('user');

/**
 * Create new user
 */
$user = new XoopsUser();
$user->setVar('uname', 'newuser');
$user->setVar('email', 'user@example.com');
$user->setVar('pass', xoops_hash_password('password123'));
$user->setVar('uregdate', time());
$user->setVar('uactive', 1);

if ($userHandler->insert($user)) {
    echo 'User created with ID: ' . $user->getID();
}

/**
 * Update user
 */
$user = $userHandler->get(123);
$user->setVar('email', 'newemail@example.com');
$userHandler->insert($user);

/**
 * Get user by name
 */
$user = $userHandler->findByUsername('john');

/**
 * Delete user
 */
$userHandler->delete($user);

/**
 * Search users
 */
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('uname', '%admin%', 'LIKE'));
$users = $userHandler->getObjects($criteria);
```

## Complete User Management Example

```php
<?php
/**
 * Complete user authentication and profile example
 */

require_once XOOPS_ROOT_PATH . '/include/common.inc.php';

$xoopsUser = $GLOBALS['xoopsUser'];

// Check if user is logged in
if (!$xoopsUser || !$xoopsUser->isActive()) {
    redirect_header(XOOPS_URL, 3, 'Please login');
}

// Get user handler
$userHandler = xoops_getHandler('user');

// Get current user with fresh data
$currentUser = $userHandler->get($xoopsUser->getID());

// User profile page
echo '<h1>Profile: ' . htmlspecialchars($currentUser->getVar('uname')) . '</h1>';

echo '<div class="user-profile">';
echo '<p><strong>Username:</strong> ' . htmlspecialchars($currentUser->getVar('uname')) . '</p>';
echo '<p><strong>Email:</strong> ' . htmlspecialchars($currentUser->getVar('email')) . '</p>';
echo '<p><strong>Registered:</strong> ' . date('Y-m-d H:i:s', $currentUser->getVar('uregdate')) . '</p>';
echo '<p><strong>Groups:</strong> ';

$groupHandler = xoops_getHandler('group');
$groups = $currentUser->getGroups();
$groupNames = [];
foreach ($groups as $groupId) {
    $group = $groupHandler->get($groupId);
    if ($group) {
        $groupNames[] = htmlspecialchars($group->getName());
    }
}
echo implode(', ', $groupNames);
echo '</p>';

// Admin status
if ($currentUser->isAdmin()) {
    echo '<p><strong>Status:</strong> Administrator</p>';
}

echo '</div>';

// Change password form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Verify old password
    if (!password_verify($oldPassword, $currentUser->getVar('pass'))) {
        echo '<div class="error">Current password is incorrect</div>';
    } elseif ($newPassword !== $confirmPassword) {
        echo '<div class="error">New passwords do not match</div>';
    } elseif (strlen($newPassword) < 6) {
        echo '<div class="error">Password must be at least 6 characters</div>';
    } else {
        // Update password
        $currentUser->setVar('pass', xoops_hash_password($newPassword));
        if ($userHandler->insert($currentUser)) {
            echo '<div class="success">Password changed successfully</div>';
        } else {
            echo '<div class="error">Failed to update password</div>';
        }
    }
}

// Password change form
echo '<form method="post">';
echo '<h3>Change Password</h3>';
echo '<div class="form-group">';
echo '<label>Current Password:</label>';
echo '<input type="password" name="old_password" required>';
echo '</div>';
echo '<div class="form-group">';
echo '<label>New Password:</label>';
echo '<input type="password" name="new_password" required>';
echo '</div>';
echo '<div class="form-group">';
echo '<label>Confirm Password:</label>';
echo '<input type="password" name="confirm_password" required>';
echo '</div>';
echo '<button type="submit" name="change_password">Change Password</button>';
echo '</form>';
```

## Best Practices

1. **Hash Passwords** - Always use bcrypt or argon2 for password hashing
2. **Validate Input** - Validate and sanitize all user input
3. **Check Permissions** - Always verify user permissions before actions
4. **Use Sessions Securely** - Regenerate session IDs on login
5. **Log Activities** - Log login, logout, and critical actions
6. **Rate Limiting** - Implement login attempt rate limiting
7. **HTTPS Only** - Always use HTTPS for authentication
8. **Group Management** - Use groups for permission organization

## Related Documentation

- [[../Kernel/Kernel-Classes]] - Kernel services and bootstrapping
- [[../Database/QueryBuilder]] - Database queries for user data
- [[../Core/XoopsObject]] - Base object class

---

*See also: [XOOPS User API](https://github.com/XOOPS/XoopsCore25/tree/master/htdocs/class) | [PHP Security](https://www.php.net/manual/en/book.password.php)*
