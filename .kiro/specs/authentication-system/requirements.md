# Requirements Document: Authentication System

## Introduction

The Authentication System provides secure user login, registration with email verification, and password reset functionality for the sailing club member system. It implements session-based authentication with CAPTCHA protection and role-based access control.

## Glossary

- **System**: The SL Authentication System
- **User**: A person attempting to authenticate or register
- **Member**: An authenticated user with a member record in the database
- **Admin User**: A member with isAdmin flag set to true
- **Regular User**: A member with isAdmin flag set to false
- **Auth Token**: A time-limited token for email verification or password reset
- **Session**: Server-side storage of authenticated user state
- **CAPTCHA**: Cloudflare Turnstile challenge to prevent automated abuse
- **Password Service**: Service for hashing and verifying passwords
- **User Authentication Service**: Service for registration, activation, and password reset operations

## Requirements

### Requirement 1: User Login

**User Story:** As a User, I want to log in with my email and password, so that I can access the system.

#### Acceptance Criteria

1. WHEN a User navigates to the login page, THE System SHALL display a login form with email and password fields
2. THE System SHALL generate and set a CSRF token for the login form
3. WHEN a User submits the login form, THE System SHALL validate the CAPTCHA response
4. IF CAPTCHA validation fails, THEN THE System SHALL render the login view with an error message
5. THE System SHALL validate that email and password fields are not empty
6. THE System SHALL retrieve the member record by email from the Member Repository
7. IF the email does not exist, THEN THE System SHALL log the attempt and render the login view with "Felaktig e-postadress eller lösenord"
8. THE System SHALL verify the provided password against the stored password hash using Password Service
9. IF password verification fails, THEN THE System SHALL log the attempt and render the login view with "Felaktig e-postadress eller lösenord"
10. WHEN authentication succeeds, THE System SHALL regenerate the session ID
11. THE System SHALL store user_id, fornamn, and is_admin in the session
12. WHEN the member is an admin, THE System SHALL redirect to the home page or stored redirect URL
13. WHEN the member is not an admin, THE System SHALL redirect to the user home page
14. THE System SHALL log successful login attempts with member email and IP address

### Requirement 2: User Logout

**User Story:** As a Member, I want to log out of the system, so that my session is terminated.

#### Acceptance Criteria

1. WHEN a Member requests to log out, THE System SHALL remove user_id and fornamn from the session
2. THE System SHALL destroy the session
3. THE System SHALL redirect to the login page with a success message

### Requirement 3: User Registration

**User Story:** As a User, I want to register for an account, so that I can become a member and access the system.

#### Acceptance Criteria

1. WHEN a User navigates to the registration page, THE System SHALL display a registration form
2. THE System SHALL generate and set a CSRF token for the registration form
3. WHEN a User submits the registration form, THE System SHALL validate the CAPTCHA response
4. IF CAPTCHA validation fails, THEN THE System SHALL render the registration view with an error message
5. THE System SHALL validate the submitted registration data via User Authentication Service
6. THE System SHALL check that the email is not already registered
7. THE System SHALL validate password strength requirements
8. THE System SHALL create a new member record with a hashed password
9. THE System SHALL generate a unique activation token
10. THE System SHALL store the activation token in the AuthToken table with token_type 'activation'
11. THE System SHALL send an activation email with the verification link
12. WHEN registration succeeds, THE System SHALL display the login view with message "E-post med verifieringslänk har skickats till din e-postadress"
13. IF registration fails, THEN THE System SHALL redirect to the registration page with an error message

### Requirement 4: Account Activation

**User Story:** As a User, I want to activate my account via email link, so that I can complete registration and log in.

#### Acceptance Criteria

1. WHEN a User clicks the activation link with a token, THE System SHALL validate the token via User Authentication Service
2. THE System SHALL retrieve the AuthToken record by token and token_type 'activation'
3. IF the token does not exist or is expired, THEN THE System SHALL redirect to login with an error message
4. THE System SHALL retrieve the member record by email from the token
5. THE System SHALL update the member's password with the hashed password from the token
6. THE System SHALL delete the AuthToken record after successful activation
7. WHEN activation succeeds, THE System SHALL redirect to login with message "Ditt konto är nu aktiverat. Du kan nu logga in."
8. IF activation fails, THEN THE System SHALL redirect to login with an error message

### Requirement 5: Password Reset Request

**User Story:** As a User, I want to request a password reset, so that I can regain access if I forget my password.

#### Acceptance Criteria

1. WHEN a User navigates to the password reset request page, THE System SHALL display a form with an email field
2. WHEN a User submits the form, THE System SHALL validate the CAPTCHA response
3. IF CAPTCHA validation fails, THEN THE System SHALL render the view with an error message
4. THE System SHALL validate the email via User Authentication Service
5. THE System SHALL check if a member with the email exists
6. THE System SHALL generate a unique password reset token
7. THE System SHALL store the token in the AuthToken table with token_type 'password_reset'
8. THE System SHALL send a password reset email with the reset link
9. THE System SHALL display message "Om du har ett konto får du strax ett mail med en återställningslänk till din e-postadress" regardless of whether the email exists (security best practice)
10. IF email sending fails, THEN THE System SHALL display error message "Kunde inte skicka mail för lösenordsåterställning. Försök igen."

### Requirement 6: Password Reset Completion

**User Story:** As a User, I want to set a new password via the reset link, so that I can regain access to my account.

#### Acceptance Criteria

1. WHEN a User clicks the password reset link with a token, THE System SHALL validate the token via User Authentication Service
2. THE System SHALL retrieve the AuthToken record by token and token_type 'password_reset'
3. IF the token does not exist or is expired, THEN THE System SHALL redirect to password request page with an error message
4. WHEN the token is valid, THE System SHALL display a form to set a new password
5. THE System SHALL pre-fill the email field from the token
6. THE System SHALL generate and set a CSRF token for the form
7. WHEN the User submits the new password, THE System SHALL validate password strength requirements
8. THE System SHALL hash the new password using Password Service
9. THE System SHALL update the member's password in the database
10. THE System SHALL delete the AuthToken record after successful reset
11. WHEN reset succeeds, THE System SHALL redirect to login with message "Ditt lösenord är uppdaterat. Du kan nu logga in med ditt nya lösenord."
12. IF reset fails, THEN THE System SHALL render the reset form with an error message

### Requirement 7: Session Management

**User Story:** As the System, I want to manage user sessions securely, so that authenticated state is maintained properly.

#### Acceptance Criteria

1. THE System SHALL configure session cookies with httponly flag set to true
2. THE System SHALL configure session cookies with samesite set to 'Strict'
3. WHEN the application environment is PROD, THE System SHALL configure session cookies with secure flag set to true
4. THE System SHALL set session lifetime to 3600 seconds (1 hour)
5. THE System SHALL regenerate session ID every 30 minutes
6. THE System SHALL regenerate session ID after successful login
7. THE System SHALL store session_regeneration_time in the session
8. THE System SHALL check session_regeneration_time and regenerate if more than 1800 seconds have passed

### Requirement 8: CAPTCHA Validation

**User Story:** As the System, I want to validate CAPTCHA on authentication forms, so that automated abuse is prevented.

#### Acceptance Criteria

1. THE System SHALL require CAPTCHA validation on login form submission
2. THE System SHALL require CAPTCHA validation on registration form submission
3. THE System SHALL require CAPTCHA validation on password reset request form submission
4. THE System SHALL use Cloudflare Turnstile for CAPTCHA validation
5. THE System SHALL validate the CAPTCHA response token with Cloudflare API
6. THE System SHALL include the user's IP address in CAPTCHA validation
7. IF CAPTCHA validation fails, THEN THE System SHALL display error message and not process the form
8. THE System SHALL log CAPTCHA validation failures

### Requirement 9: Password Security

**User Story:** As the System, I want to handle passwords securely, so that user credentials are protected.

#### Acceptance Criteria

1. THE System SHALL hash all passwords before storing in the database
2. THE System SHALL use Password Service for password hashing
3. THE System SHALL use Password Service for password verification
4. THE System SHALL never log or display plain text passwords
5. THE System SHALL validate password strength during registration and reset
6. THE System SHALL use timing-safe comparison for password verification
7. THE System SHALL store password hashes in VARCHAR(50) field (note: may need to be increased for modern hashing algorithms)

### Requirement 10: Authentication Logging

**User Story:** As an Admin User, I want authentication events to be logged, so that I can audit security events.

#### Acceptance Criteria

1. THE System SHALL log successful login attempts with member email and IP address
2. THE System SHALL log failed login attempts with provided email and IP address
3. THE System SHALL log failed login attempts due to non-existent email
4. THE System SHALL log failed login attempts due to incorrect password
5. THE System SHALL log technical errors during authentication
6. THE System SHALL use Monolog Logger for all authentication logging
7. THE System SHALL log at appropriate levels (info for normal events, error for failures)

### Requirement 11: Access Control

**User Story:** As the System, I want to enforce role-based access control, so that users only access authorized resources.

#### Acceptance Criteria

1. THE System SHALL check session for user_id to determine if user is authenticated
2. THE System SHALL check session for is_admin flag to determine admin status
3. THE System SHALL provide RequireAuthenticationMiddleware to protect authenticated routes
4. THE System SHALL provide RequireAdminMiddleware to protect admin-only routes
5. WHEN an unauthenticated user accesses a protected route, THE System SHALL redirect to login page
6. WHEN an unauthenticated user accesses a protected route, THE System SHALL store the requested URL in session as redirect_url
7. WHEN a non-admin user accesses an admin route, THE System SHALL deny access
8. WHEN a user successfully logs in and redirect_url exists, THE System SHALL redirect to the stored URL

### Requirement 12: Auth Token Management

**User Story:** As the System, I want to manage authentication tokens, so that email verification and password reset are secure.

#### Acceptance Criteria

1. THE System SHALL store auth tokens in the AuthToken table
2. THE System SHALL include email, token, token_type, and created_at for each token
3. THE System SHALL support token_type values 'activation' and 'password_reset'
4. THE System SHALL generate unique tokens using secure random generation
5. THE System SHALL store password_hash in AuthToken for activation tokens
6. THE System SHALL validate tokens before use
7. THE System SHALL delete tokens after successful use
8. THE System SHALL implement token expiration (tokens should have limited lifetime)
