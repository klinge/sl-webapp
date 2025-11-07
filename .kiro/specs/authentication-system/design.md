# Design Document: Authentication System

## Overview

The Authentication System implements secure session-based authentication with email verification, password reset functionality, and CAPTCHA protection. It uses a layered architecture with controllers, services, middleware, and utilities to manage user authentication flows.

## Architecture

### Layer Responsibilities

- **Controller Layer** (`LoginController`, `RegistrationController`, `PasswordController`): Handle HTTP requests for auth operations
- **Service Layer** (`UserAuthenticationService`, `PasswordService`): Implement business logic for authentication flows
- **Middleware Layer** (`RequireAuthenticationMiddleware`, `RequireAdminMiddleware`): Enforce access control
- **Utility Layer** (`Session`, `TokenHandler`, `Email`): Provide supporting functionality
- **Repository Layer** (`MedlemRepository`): Data access for member records

### Authentication Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Authentication Flows                      │
└─────────────────────────────────────────────────────────────┘

Login Flow:
User → Login Form → CAPTCHA → Validate Credentials → 
Create Session → Redirect (Admin: home, User: user-home)

Registration Flow:
User → Register Form → CAPTCHA → Validate Data → 
Create Member → Generate Token → Send Email → 
User Clicks Link → Activate Account → Set Password

Password Reset Flow:
User → Request Reset → CAPTCHA → Generate Token → 
Send Email → User Clicks Link → Validate Token → 
Set New Password → Update Database
```

## Components and Interfaces

### LoginController

**Responsibilities:**
- Display login form
- Process login attempts
- Validate CAPTCHA
- Authenticate credentials
- Manage session creation
- Handle logout

**Key Methods:**
- `showLogin()`: Display login form with CSRF token
- `login()`: Process login with CAPTCHA validation, credential verification, session creation
- `logout()`: Destroy session and redirect to login

**Dependencies:**
- `MedlemRepository`: Retrieve member by email
- `PasswordService`: Verify password hashes
- `View`: Render login template
- `UrlGeneratorService`: Generate redirect URLs

**Authentication Logic:**
1. Validate CAPTCHA response
2. Check email and password not empty
3. Retrieve member by email
4. Verify password hash
5. Regenerate session ID
6. Store user_id, fornamn, is_admin in session
7. Redirect based on admin status

### RegistrationController

**Responsibilities:**
- Display registration form
- Process registration requests
- Validate CAPTCHA
- Coordinate with UserAuthenticationService
- Handle account activation

**Key Methods:**
- `showRegister()`: Display registration form with CSRF token
- `register()`: Process registration with CAPTCHA validation
- `activate(ServerRequestInterface $request, array $params)`: Activate account via token

**Dependencies:**
- `UserAuthenticationService`: Handle registration and activation logic
- `View`: Render registration templates

### PasswordController

**Responsibilities:**
- Display password reset request form
- Process reset requests
- Display password reset form
- Process password updates

**Key Methods:**
- `showRequestPwd()`: Display password reset request form
- `sendPwdRequestToken()`: Process reset request with CAPTCHA validation
- `showResetPassword(ServerRequestInterface $request, array $params)`: Display reset form after token validation
- `resetAndSavePassword()`: Process new password submission

**Dependencies:**
- `UserAuthenticationService`: Handle password reset logic
- `View`: Render password reset templates

### UserAuthenticationService

**Responsibilities:**
- Implement registration business logic
- Implement account activation logic
- Implement password reset logic
- Coordinate email sending
- Manage authentication tokens

**Key Methods:**
- `registerUser(array $formData)`: Validate and process registration
- `activateAccount(string $token)`: Activate account and set password
- `requestPasswordReset(string $email)`: Generate token and send reset email
- `validateResetToken(string $token)`: Validate reset token
- `resetPassword(array $formData)`: Update password after validation

**Business Logic:**
- Check if member email exists in database
- Validate member doesn't already have password
- Validate password strength
- Generate secure tokens
- Send activation/reset emails
- Update member passwords
- Clean up expired tokens

**Return Type:**
```php
array [
    'success' => bool,
    'message' => string (optional),
    'email' => string (optional, for token validation),
    'hashedPassword' => string (optional, for activation)
]
```

### PasswordService

**Responsibilities:**
- Hash passwords securely
- Verify password hashes
- Validate password strength
- Check password matching

**Key Methods:**
- `hashPassword(string $password)`: Hash using PASSWORD_DEFAULT
- `verifyPassword(string $password, string $hash)`: Verify using password_verify
- `validatePassword(string $password, string $email)`: Check complexity requirements
- `passwordsMatch(string $password, string $confirmPassword)`: Compare passwords
- `formatPasswordErrors(array $errors)`: Format errors as HTML list

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one digit
- Cannot contain username from email
- Cannot contain first or last name (if email format is firstname.lastname@domain)

### RequireAuthenticationMiddleware

**Responsibilities:**
- Check if user is authenticated
- Store redirect URL for post-login redirect
- Redirect unauthenticated users to login

**Process Logic:**
1. Check session for user_id
2. If not authenticated:
   - Log access attempt with path and IP
   - Store current path in session as redirect_url
   - Set flash error message
   - Redirect to /login
3. If authenticated, pass to next handler

### RequireAdminMiddleware

**Responsibilities:**
- Check if user is authenticated
- Check if user is admin
- Redirect non-admin users appropriately

**Process Logic:**
1. Check session for user_id
2. If not authenticated:
   - Log warning with path and IP
   - Store current path in session as redirect_url
   - Set flash error message
   - Redirect to /login
3. Check session for is_admin
4. If not admin:
   - Log warning with user_id, path, and IP
   - Redirect to /user
5. If admin, pass to next handler

## Data Models

### AuthToken Table

```sql
CREATE TABLE AuthToken (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    token_type VARCHAR(16) NOT NULL,
    password_hash VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Fields:**
- `id`: Primary key
- `email`: Member email address
- `token`: Unique token string (generated securely)
- `token_type`: Either 'activation' or 'password_reset'
- `password_hash`: Hashed password (only for activation tokens)
- `created_at`: Token creation timestamp

**Token Types:**
- `activation`: For email verification during registration
- `password_reset`: For password reset requests

### Session Data Structure

```php
$_SESSION = [
    'user_id' => int,              // Member ID
    'fornamn' => string,           // First name
    'is_admin' => bool,            // Admin status
    'csrf_token' => string,        // CSRF protection token
    'redirect_url' => string,      // Post-login redirect URL
    'session_regeneration_time' => int,  // Last regeneration timestamp
    'flash_messages' => [          // Flash messages
        'success' => string,
        'error' => string
    ]
];
```

## Email Templates

### Activation Email

**Template:** `emails/viewVerificationEmail.php`

**Data:**
- `fornamn`: Member first name
- `url`: Activation URL with token
- `token`: Activation token (for display)

**Subject:** Account Verification

### Password Reset Email

**Template:** `emails/viewPasswordResetEmail.php`

**Data:**
- `fornamn`: Member first name
- `url`: Reset URL with token
- `token`: Reset token (for display)

**Subject:** Password Reset Request

## Error Handling

### Validation Errors

**Registration:**
- Email not found in member database
- Account already registered (password exists)
- Passwords don't match
- Password doesn't meet complexity requirements

**Login:**
- Empty email or password
- Email not found
- Incorrect password
- CAPTCHA validation failure

**Password Reset:**
- Invalid or expired token
- Passwords don't match
- Password doesn't meet complexity requirements
- Email sending failure

### Error Response Patterns

**View Rendering with Error:**
```php
return $this->renderWithError(self::LOGIN_VIEW, 'Error message');
```

**Redirect with Error:**
```php
return $this->redirectWithError('route-name', 'Error message');
```

**Service Result:**
```php
return [
    'success' => false,
    'message' => 'Error description'
];
```

## Security Considerations

### Password Security

- Passwords hashed using `password_hash()` with PASSWORD_DEFAULT
- Password verification using `password_verify()` (timing-safe)
- Minimum complexity requirements enforced
- Passwords never logged or displayed in plain text
- Password hashes stored in VARCHAR(50) field (note: may need expansion for future algorithms)

### Session Security

- Session ID regenerated on login
- Session ID regenerated every 30 minutes
- HttpOnly flag prevents JavaScript access
- SameSite=Strict prevents CSRF
- Secure flag in production (HTTPS only)
- Session lifetime: 3600 seconds (1 hour)

### Token Security

- Tokens generated using secure random generation
- Tokens stored in database with email and type
- Tokens deleted after successful use
- Expired tokens cleaned up periodically
- Token validation checks type and existence

### CAPTCHA Protection

- Cloudflare Turnstile integration
- Required on login, registration, password reset
- Validates with user IP address
- Failures logged for monitoring

### CSRF Protection

- CSRF tokens generated for all forms
- Tokens stored in session
- Tokens validated using `hash_equals()` (timing-safe)
- Tokens regenerated after use

### Access Control

- Middleware enforces authentication requirements
- Admin routes protected by RequireAdminMiddleware
- User routes protected by RequireAuthenticationMiddleware
- Unauthorized access logged with IP and path

### Logging

- Successful logins logged with email and IP
- Failed logins logged with email and IP
- Authentication errors logged
- Access control violations logged
- CAPTCHA failures logged

## Testing Strategy

### Unit Tests

**PasswordService Tests:**
- Test password hashing and verification
- Test password validation rules (length, complexity, email-based)
- Test password matching
- Test error formatting

**UserAuthenticationService Tests:**
- Test registration logic with mocked dependencies
- Test activation logic
- Test password reset request logic
- Test password reset completion logic
- Mock email sending and token generation

### Integration Tests

**Controller Integration Tests:**
- Test login flow with valid credentials
- Test login flow with invalid credentials
- Test registration flow with valid data
- Test registration flow with invalid data
- Test account activation with valid token
- Test account activation with invalid token
- Test password reset request
- Test password reset completion
- Test CAPTCHA validation
- Test CSRF protection

**Middleware Integration Tests:**
- Test RequireAuthenticationMiddleware with authenticated user
- Test RequireAuthenticationMiddleware with unauthenticated user
- Test RequireAdminMiddleware with admin user
- Test RequireAdminMiddleware with non-admin user
- Test redirect URL storage and restoration

**Database Integration Tests:**
- Test token creation and retrieval
- Test token deletion after use
- Test expired token cleanup
- Test password updates
- Test member retrieval by email

### Test Scenarios

- Register new account with valid email
- Register with non-existent email
- Register with already registered email
- Activate account with valid token
- Activate account with expired token
- Login with correct credentials
- Login with incorrect password
- Login with non-existent email
- Request password reset for existing email
- Request password reset for non-existent email (should not leak info)
- Reset password with valid token
- Reset password with expired token
- Session regeneration after 30 minutes
- Redirect to stored URL after login
- Admin access to admin routes
- Non-admin denied access to admin routes

## Configuration

### Environment Variables

```
TURNSTILE_SECRET_KEY=<cloudflare_turnstile_secret>
SITE_ADDRESS=<base_url_for_email_links>
APP_ENV=DEV|PROD
```

### Session Configuration

```php
session_set_cookie_params([
    'lifetime' => 3600,
    'secure' => $isProduction,
    'httponly' => true,
    'samesite' => 'Strict',
]);
```

### Email Configuration

Configured via Email utility class with SMTP settings from environment variables.
