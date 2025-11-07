# Implementation Plan: Authentication System

- [ ] 1. Create PasswordService for password operations
  - Implement hashPassword() using password_hash with PASSWORD_DEFAULT
  - Implement verifyPassword() using password_verify
  - Implement validatePassword() with complexity rules (length, uppercase, lowercase, digit, email-based checks)
  - Implement passwordsMatch() for password confirmation
  - Implement formatPasswordErrors() to format validation errors as HTML list
  - _Requirements: 1.8, 3.7, 6.7, 6.8, 9.1, 9.2, 9.3, 9.5, 9.6_

- [ ] 2. Create TokenHandler utility for auth token management
  - Implement generateToken() using secure random generation
  - Implement saveToken() to store tokens in AuthToken table with email, type, and optional password hash
  - Implement isValidToken() to validate token existence and type
  - Implement deleteToken() to remove used tokens
  - Implement deleteExpiredTokens() for cleanup
  - Support token types: 'activation' and 'password_reset'
  - _Requirements: 3.9, 3.10, 4.2, 4.6, 5.6, 5.7, 6.2, 6.10, 12.1, 12.2, 12.3, 12.4, 12.7, 12.8_

- [ ] 3. Implement UserAuthenticationService business logic
  - Implement registerUser() with email validation, password validation, token generation, and email sending
  - Implement activateAccount() to validate token and set member password
  - Implement requestPasswordReset() to generate token and send reset email
  - Implement validateResetToken() to check token validity
  - Implement resetPassword() to validate and update password
  - Implement sendActivationEmail() and sendPasswordResetEmail() helper methods
  - Implement saveMembersPassword() to update member password in database
  - _Requirements: 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.11, 3.12, 3.13, 4.1, 4.3, 4.4, 4.5, 4.6, 4.7, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 6.1, 6.2, 6.7, 6.8, 6.9, 6.10, 6.11_

- [ ] 4. Build LoginController for authentication
  - Implement showLogin() to display login form with CSRF token
  - Implement login() with CAPTCHA validation, credential verification, and session creation
  - Implement logout() to destroy session and redirect
  - Add logic to regenerate session ID on successful login
  - Add logic to redirect admins to home and users to user-home
  - Add logic to handle stored redirect_url from session
  - Add logging for successful and failed login attempts with IP addresses
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 1.12, 1.13, 1.14, 2.1, 2.2, 2.3, 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 5. Build RegistrationController for account creation
  - Implement showRegister() to display registration form with CSRF token
  - Implement register() with CAPTCHA validation and UserAuthenticationService coordination
  - Implement activate() to handle account activation via token
  - Add error handling and flash messages
  - _Requirements: 3.1, 3.2, 3.3, 3.12, 3.13, 4.1, 4.7, 4.8_

- [ ] 6. Build PasswordController for password reset
  - Implement showRequestPwd() to display password reset request form
  - Implement sendPwdRequestToken() with CAPTCHA validation
  - Implement showResetPassword() to validate token and display reset form
  - Implement resetAndSavePassword() to process new password
  - Add error handling and flash messages
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10, 6.1, 6.3, 6.4, 6.5, 6.6, 6.11, 6.12_

- [ ] 7. Implement RequireAuthenticationMiddleware
  - Check session for user_id to determine authentication
  - Store current path in session as redirect_url for unauthenticated users
  - Redirect unauthenticated users to /login with flash error message
  - Log unauthenticated access attempts with path and IP
  - _Requirements: 11.1, 11.3, 11.5, 11.6_

- [ ] 8. Implement RequireAdminMiddleware
  - Check session for user_id to determine authentication
  - Check session for is_admin flag to determine admin status
  - Store current path in session as redirect_url for unauthenticated users
  - Redirect unauthenticated users to /login with flash error message
  - Redirect non-admin users to /user
  - Log access control violations with user_id, path, and IP
  - _Requirements: 11.2, 11.4, 11.5, 11.6, 11.7, 11.8_

- [ ] 9. Configure session management
  - Set session cookie parameters (lifetime: 3600, httponly: true, samesite: Strict)
  - Set secure flag based on environment (PROD only)
  - Implement session regeneration every 30 minutes
  - Store session_regeneration_time in session
  - Regenerate session ID on login
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8_

- [ ] 10. Implement CAPTCHA validation
  - Integrate Cloudflare Turnstile in AuthBaseController
  - Implement validateRecaptcha() method with IP address validation
  - Add CAPTCHA validation to login, registration, and password reset forms
  - Log CAPTCHA validation failures
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

- [ ] 11. Set up database schema for auth tokens
  - Create AuthToken table with id, email, token, token_type, password_hash, created_at
  - Add indexes for token and email lookups
  - _Requirements: 12.1, 12.2, 12.5_

- [ ] 12. Configure routing for authentication
  - Add routes for /login (GET, POST)
  - Add routes for /logout (GET)
  - Add routes for /auth/register (GET, POST)
  - Add routes for /auth/register/{token} (GET) for activation
  - Add routes for /auth/bytlosenord (GET, POST) for password reset request
  - Add routes for /auth/bytlosenord/{token} (GET) for reset form
  - Add routes for /auth/sparalosenord (POST) for password update
  - Configure route names for URL generation
  - _Requirements: All_

- [ ] 13. Create view templates for authentication
  - Create login/viewLogin.php for login form
  - Create login/viewRegisterAccount.php for registration form
  - Create login/viewReqPassword.php for password reset request form
  - Create login/viewSetNewPassword.php for password reset form
  - Create emails/viewVerificationEmail.php for activation email
  - Create emails/viewPasswordResetEmail.php for reset email
  - Add CSRF token fields to all forms
  - Add CAPTCHA widgets to forms
  - _Requirements: 1.1, 3.1, 5.1, 6.4_

- [ ] 14. Register services in DI container
  - Register PasswordService in ContainerConfigurator
  - Register UserAuthenticationService with dependencies (PDO, Logger, Router, Email, config)
  - Register LoginController with dependencies
  - Register RegistrationController with dependencies
  - Register PasswordController with dependencies
  - Register RequireAuthenticationMiddleware with Logger
  - Register RequireAdminMiddleware with Logger
  - _Requirements: All_

- [ ]* 15. Write unit tests for authentication
  - Write unit tests for PasswordService (hashing, verification, validation)
  - Write unit tests for UserAuthenticationService (registration, activation, reset)
  - Write unit tests for TokenHandler (generation, validation, cleanup)
  - Mock dependencies (email, database, logger)
  - Test password validation rules
  - Test error handling and result objects
  - _Requirements: All_

- [ ]* 16. Write integration tests
  - Write integration tests for login flow (success and failure cases)
  - Write integration tests for registration and activation flow
  - Write integration tests for password reset flow
  - Test middleware behavior (authentication and admin checks)
  - Test session management (creation, regeneration, destruction)
  - Test CAPTCHA validation
  - Test CSRF protection
  - Test database operations (token storage, member updates)
  - Test email sending (with mock SMTP)
  - Test redirect URL storage and restoration
  - _Requirements: All_
