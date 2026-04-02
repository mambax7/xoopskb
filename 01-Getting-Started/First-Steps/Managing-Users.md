---
title: Managing Users
description: Comprehensive guide to user administration in XOOPS including creating users, user groups, permissions, and user roles
created: 2025-01-28
updated: 2025-01-28
version: 2.5.8
category: First Steps
---

# Managing Users in XOOPS

Learn how to create user accounts, organize users into groups, and manage permissions in XOOPS.

## User Management Overview

XOOPS provides comprehensive user management with:

```
Users > Accounts
├── Individual users
├── User profiles
├── Registration requests
└── Online users

Users > Groups
├── User groups/roles
├── Group permissions
└── Group membership

System > Permissions
├── Module access
├── Content access
├── Function permissions
└── Group capabilities
```

## Accessing User Management

### Admin Panel Navigation

1. Log in to admin: `http://your-domain.com/xoops/admin/`
2. Click **Users** in left sidebar
3. Select from options:
   - **Users:** Manage individual accounts
   - **Groups:** Manage user groups
   - **Online Users:** See currently active users
   - **User Requests:** Process registration requests

## Understanding User Roles

XOOPS comes with predefined user roles:

| Group | Role | Capabilities | Use Case |
|---|---|---|---|
| **Webmasters** | Administrator | Full site control | Main admins |
| **Admins** | Administrator | Limited admin access | Trusted users |
| **Moderators** | Content control | Approve content | Community managers |
| **Editors** | Content creation | Create/edit content | Content staff |
| **Registered** | Member | Post, comment, profile | Regular users |
| **Anonymous** | Visitor | Read only | Non-logged-in users |

## Creating User Accounts

### Method 1: Admin Creates User

**Step 1: Access User Creation**

1. Go to **Users > Users**
2. Click **"Add New User"** or **"Create User"**

**Step 2: Enter User Information**

Fill in user details:

```
Username: [4+ characters, letters/numbers/underscore only]
Example: john_smith

Email Address: [Valid email address]
Example: john@example.com

Password: [Strong password]
Example: MyStr0ng!Pass2025

Confirm Password: [Repeat password]
Example: MyStr0ng!Pass2025

Real Name: [User's full name]
Example: John Smith

URL: [Optional user website]
Example: https://johnsmith.com

Signature: [Optional forum signature]
Example: "Happy XOOPS user!"
```

**Step 3: Configure User Settings**

```
User Status: ☑ Active
             ☐ Inactive
             ☐ Pending Approval

User Groups:
☑ Registered Users
☐ Webmasters
☐ Admins
☐ Moderators
```

**Step 4: Additional Options**

```
Notify User: ☑ Send welcome email
Allow Avatar: ☑ Yes
User Theme: [Default theme]
Show Email: ☐ Public / ☑ Private
```

**Step 5: Create Account**

Click **"Add User"** or **"Create"**

Confirmation:
```
User created successfully!
Username: john_smith
Email: john@example.com
Groups: Registered Users
```

### Method 2: User Self-Registration

Allow users to register themselves:

**Admin Panel > System > Preferences > User Settings**

```
Allow User Registration: ☑ Yes

Registration Type:
☐ Instant (Approve automatically)
☑ Email Verification (Email confirmation)
☐ Admin Approval (You approve each)

Send Verification Email: ☑ Yes
```

Then:
1. Users visit registration page
2. Fill in basic information
3. Verify email or wait for approval
4. Account activated

## Managing User Accounts

### View All Users

**Location:** Users > Users

Shows user list with:
- Username
- Email address
- Registration date
- Last login
- User status (Active/Inactive)
- Group membership

### Edit User Account

1. In user list, click username
2. Modify any field:
   - Email address
   - Password
   - Real name
   - User groups
   - Status

3. Click **"Save"** or **"Update"**

### Change User Password

1. Click user in list
2. Scroll to "Change Password" section
3. Enter new password
4. Confirm password
5. Click **"Change Password"**

User will use new password on next login.

### Deactivate/Suspend User

Temporarily disable account without deletion:

1. Click user in list
2. Set **User Status** to "Inactive"
3. Click **"Save"**

User cannot log in while inactive.

### Reactivate User

1. Click user in list
2. Set **User Status** to "Active"
3. Click **"Save"**

User can log in again.

### Delete User Account

Remove user permanently:

1. Click user in list
2. Scroll to bottom
3. Click **"Delete User"**
4. Confirm: "Delete user and all data?"
5. Click **"Yes"**

**Warning:** Deletion is permanent!

### View User Profile

See user profile details:

1. Click username in user list
2. Review profile information:
   - Real name
   - Email
   - Website
   - Join date
   - Last login
   - User bio
   - Avatar
   - Posts/contributions

## Understanding User Groups

### Default User Groups

XOOPS includes default groups:

| Group | Purpose | Special | Edit |
|---|---|---|---|
| **Anonymous** | Non-logged-in users | Fixed | No |
| **Registered Users** | Regular members | Default | Yes |
| **Webmasters** | Site administrators | Admin | Yes |
| **Admins** | Limited admins | Admin | Yes |
| **Moderators** | Content moderators | Custom | Yes |

### Create Custom Group

Create group for specific role:

**Location:** Users > Groups

1. Click **"Add New Group"**
2. Enter group details:

```
Group Name: Content Editors
Group Description: Users who can create and edit content

Display Group: ☑ Yes (Show in member profiles)
Group Type: ☑ Regular / ☐ Admin
```

3. Click **"Create Group"**

### Manage Group Membership

Assign users to groups:

**Option A: From Users List**

1. Go to **Users > Users**
2. Click user
3. Check/uncheck groups in "User Groups" section
4. Click **"Save"**

**Option B: From Groups**

1. Go to **Users > Groups**
2. Click group name
3. View/edit member list
4. Add or remove users
5. Click **"Save"**

### Edit Group Properties

Customize group settings:

1. Go to **Users > Groups**
2. Click group name
3. Modify:
   - Group name
   - Group description
   - Display group (show/hide)
   - Group type
4. Click **"Save"**

## User Permissions

### Understanding Permissions

Three permission levels:

| Level | Scope | Example |
|---|---|---|
| **Module Access** | Can see/use module | Can access Forum module |
| **Content Permissions** | Can view specific content | Can read published news |
| **Function Permissions** | Can perform actions | Can post comments |

### Configure Module Access

**Location:** System > Permissions

Restrict which groups can access each module:

```
Module: News

Admin Access:
☑ Webmasters
☑ Admins
☐ Moderators
☐ Registered Users
☐ Anonymous

User Access:
☐ Webmasters
☐ Admins
☑ Moderators
☑ Registered Users
☑ Anonymous
```

Click **"Save"** to apply.

### Set Content Permissions

Control access to specific content:

Example - News article:
```
View Permission:
☑ All groups can read

Post Permission:
☑ Registered Users
☑ Content Editors
☐ Anonymous

Moderate Comments:
☑ Moderators required
```

### Permission Best Practices

```
Public Content (News, Pages):
├── View: All groups
├── Post: Registered Users + Editors
└── Moderate: Admins + Moderators

Community (Forum, Comments):
├── View: All groups
├── Post: Registered Users
└── Moderate: Moderators + Admins

Admin Tools:
├── View: Webmasters + Admins only
├── Configure: Webmasters only
└── Delete: Webmasters only
```

## User Registration Management

### Handle Registration Requests

If "Admin Approval" enabled:

1. Go to **Users > User Requests**
2. View pending registrations:
   - Username
   - Email
   - Registration date
   - Request status

3. For each request:
   - Click to review
   - Click **"Approve"** to activate
   - Click **"Reject"** to deny

### Send Registration Email

Resend welcome/verification email:

1. Go to **Users > Users**
2. Click user
3. Click **"Send Email"** or **"Resend Verification"**
4. Email sent to user

## Online Users Monitoring

### View Currently Online Users

Track active site visitors:

**Location:** Users > Online Users

Shows:
- Current online users
- Guest visitors count
- Last activity time
- IP address
- Browsing location

### Monitor User Activity

Understand user behavior:

```
Active Users: 12
Registered: 8
Anonymous: 4

Recent Activity:
- User1 - Forum post (2 min ago)
- User2 - Comment (5 min ago)
- User3 - Page view (8 min ago)
```

## User Profile Customization

### Enable User Profiles

Configure user profile options:

**Admin > System > Preferences > User Settings**

```
Allow User Profiles: ☑ Yes
Show Member List: ☑ Yes
Users Can Edit Profile: ☑ Yes
Show User Avatar: ☑ Yes
Show Last Online: ☑ Yes
Show Email Address: ☐ Yes / ☑ No
```

### Profile Fields

Configure what users can add to profiles:

Example profile fields:
- Real name
- Website URL
- Biography
- Location
- Avatar (picture)
- Signature
- Interests
- Social media links

Customize in module settings.

## User Authentication

### Enable Two-Factor Authentication

Enhanced security option (if available):

**Admin > Users > Settings**

```
Two-Factor Authentication: ☑ Enabled

Methods:
☑ Email
☑ SMS
☑ Authenticator App
```

Users must verify with second method.

### Password Policy

Enforce strong passwords:

**Admin > System > Preferences > User Settings**

```
Minimum Password Length: 8 characters
Require Uppercase: ☑ Yes
Require Numbers: ☑ Yes
Require Special Chars: ☑ Yes

Password Expiration: 90 days
Force Change on First Login: ☑ Yes
```

### Login Attempts

Prevent brute force attacks:

```
Lock After Failed Attempts: 5
Lock Duration: 15 minutes
Log All Attempts: ☑ Yes
Notify Admin: ☑ Yes
```

## User Email Management

### Send Bulk Email to Group

Message multiple users:

1. Go to **Users > Users**
2. Select multiple users (checkboxes)
3. Click **"Send Email"**
4. Compose message:
   - Subject
   - Message body
   - Include signature
5. Click **"Send"**

### Email Notification Settings

Configure what emails users receive:

**Admin > System > Preferences > Email Settings**

```
New Registration: ☑ Send welcome email
Password Reset: ☑ Send reset link
Comments: ☑ Notify on replies
Messages: ☑ Notify new messages
Notifications: ☑ Site announcements
Frequency: ☐ Immediate / ☑ Daily / ☐ Weekly
```

## User Statistics

### View User Reports

Monitor user metrics:

**Admin > System > Dashboard**

```
User Statistics:
├── Total Users: 256
├── Active Users: 189
├── New This Month: 24
├── Registration Requests: 3
├── Currently Online: 12
└── Last 24h Posts: 45
```

### User Growth Tracking

Monitor registration trends:

```
Registrations Last 7 Days: 12 users
Registrations Last 30 Days: 48 users
Active Users (30 days): 156
Inactive Users (30+ days): 100
```

## Common User Management Tasks

### Create Admin User

1. Create new user (steps above)
2. Assign to **Webmasters** or **Admins** group
3. Grant permissions in System > Permissions
4. Verify admin access works

### Create Moderator

1. Create new user
2. Assign to **Moderators** group
3. Configure permissions to moderate specific modules
4. User can approve content, manage comments

### Setup Content Editors

1. Create **Content Editors** group
2. Create users, assign to group
3. Grant permissions to:
   - Create/edit pages
   - Create/edit posts
   - Moderate comments
4. Restrict admin panel access

### Reset Forgotten Password

User forgot their password:

1. Go to **Users > Users**
2. Find user
3. Click username
4. Click **"Reset Password"** or edit password field
5. Set temporary password
6. Notify user (send email)
7. User logs in, changes password

### Bulk Import Users

Import user list (advanced):

Many hosting panels provide tools to:
1. Prepare CSV file with user data
2. Upload via admin panel
3. Mass create accounts

Or use custom script/plugin for imports.

## User Privacy

### Respect User Privacy

Privacy best practices:

```
Do:
✓ Hide emails by default
✓ Let users choose visibility
✓ Protect against spam

Don't:
✗ Share private data
✗ Display without permission
✗ Use for marketing without consent
```

### GDPR Compliance

If serving EU users:

1. Get consent for data collection
2. Allow users to download their data
3. Provide delete account option
4. Maintain privacy policy
5. Log data processing activities

## Troubleshooting User Issues

### User Can't Login

**Problem:** User forgot password or can't access account

**Solution:**
1. Verify user account is "Active"
2. Reset password:
   - Admin > Users > Find user
   - Set new temporary password
   - Send to user via email
3. Clear user cookies/cache
4. Check if account is not locked

### User Registration Stuck

**Problem:** User can't complete registration

**Solution:**
1. Check registration is allowed:
   - Admin > System > Preferences > User Settings
   - Enable registration
2. Check email settings work
3. If email verification required:
   - Resend verification email
   - Check spam folder
4. Lower password requirements if too strict

### Duplicate Accounts

**Problem:** User has multiple accounts

**Solution:**
1. Identify duplicate accounts in Users list
2. Keep primary account
3. Merge data if possible
4. Delete duplicate accounts
5. Enable "Prevent Duplicate Email" in settings

## User Management Checklist

For initial setup:

- [ ] Set user registration type (instant/email/admin)
- [ ] Create required user groups
- [ ] Configure group permissions
- [ ] Set password policy
- [ ] Enable user profiles
- [ ] Configure email notifications
- [ ] Set user avatar options
- [ ] Test registration process
- [ ] Create test accounts
- [ ] Verify permissions working
- [ ] Document group structure
- [ ] Plan user onboarding

## Next Steps

After setting up users:

1. [[Installing-Modules|Install modules]] users need
2. [[Creating-Your-First-Page|Create content]] for users
3. [[../Configuration/Security-Configuration|Secure user accounts]]
4. [[Admin-Panel-Overview|Explore more admin features]]
5. [[../Configuration/System-Settings|Configure system-wide settings]]

---

**Tags:** #users #groups #permissions #administration #access-control

**Related Articles:**
- [[Admin-Panel-Overview]]
- [[Installing-Modules]]
- [[../Configuration/Security-Configuration]]
- [[../Configuration/System-Settings]]
