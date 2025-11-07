# Implementation Plan: Payment Management System

- [ ] 1. Create Betalning model and repository
  - Implement Betalning entity class with properties (id, medlem_id, belopp, datum, avser_ar, kommentar, timestamps)
  - Implement BetalningRepository with CRUD methods (getAll, getById, create, deleteById)
  - Implement findAllWithMemberNames() with LEFT JOIN to Medlem table
  - Implement getBetalningForMedlem() to get payments for specific member
  - Implement memberHasPayed() to check payment status for a year
  - Implement createBetalningFromData() to map database rows to Betalning objects
  - Add database connection handling and parameterized queries
  - _Requirements: 1.1, 1.2, 2.1, 2.3, 3.10, 4.2, 5.1, 5.2, 5.3, 5.4, 6.1, 6.2, 6.3, 6.4, 11.1, 11.2, 11.3_

- [ ] 2. Implement BetalningService business logic
  - Create BetalningServiceResult class for standardized responses
  - Implement getAllPayments() to retrieve payments with member names
  - Implement getPaymentsForMember() to get member and their payments
  - Implement createPayment() with validation, sanitization, and welcome email logic
  - Implement deletePayment() method
  - Implement sendWelcomeEmailOnFirstPayment() private method with all conditional checks
  - Add input sanitization with type-specific rules (string, float, date formats)
  - Add validation for required fields (belopp, datum, avser_ar)
  - Add logging for payment creation, deletion, and email sending
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.11, 3.12, 3.13, 3.14, 3.15, 4.1, 4.3, 4.4, 4.5, 5.5, 5.6, 5.7, 5.8, 5.9, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 7.11, 7.12, 7.13_

- [ ] 3. Build BetalningController request handlers
  - Implement list() to display all payments with member names
  - Implement getMedlemBetalning() to display payments for specific member
  - Implement createBetalning() to handle payment creation via AJAX
  - Implement deleteBetalning() to handle payment deletion via AJAX
  - Add error handling with try-catch blocks
  - Return JSON responses for AJAX operations
  - Return view responses for list pages
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.4, 2.5, 2.6, 3.14, 3.15, 4.3, 4.4, 4.5_

- [ ] 4. Configure routing and middleware
  - Add payment management routes to RouteConfig
  - Apply RequireAdminMiddleware to all payment routes
  - Configure route names (betalning-list, betalning-create, betalning-delete, etc.)
  - Set up route groups for /betalning paths
  - Configure POST routes for AJAX operations
  - _Requirements: 1.5, 8.1, 8.2, 8.3, 8.4_

- [ ] 5. Create view templates
  - Create viewBetalning.php for payment list display
  - Add table with columns for date, amount, year, member name, comment
  - Add link to view member payment history
  - Handle display when no payments found for member
  - Add JavaScript for AJAX payment operations (if needed)
  - _Requirements: 1.4, 2.5, 2.6_

- [ ] 6. Set up database schema and triggers
  - Create Betalning table with all columns and constraints
  - Add NOT NULL constraints on belopp and avser_ar
  - Add foreign key constraint to Medlem table with ON DELETE CASCADE
  - Create betalning_after_update trigger for automatic updated_at
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 9.1, 9.2, 9.3, 9.4, 10.1, 10.2, 10.3, 10.4_

- [ ] 7. Implement welcome email functionality
  - Create welcome email template (emails/viewWelcomeEmail.php)
  - Add WELCOME_MAIL_ENABLED configuration check
  - Implement logic to check if member already received welcome email (skickat_valkomstbrev flag)
  - Implement logic to update skickat_valkomstbrev flag after successful email send
  - Add error handling for email sending failures (log but don't fail payment creation)
  - Add logging for all welcome email operations
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 7.11, 7.12, 7.13_

- [ ] 8. Register services in DI container
  - Register BetalningRepository in ContainerConfigurator
  - Register BetalningService with dependencies (BetalningRepository, MedlemRepository, Email, Application, Logger)
  - Register BetalningController with service, view, and URL generator dependencies
  - Configure RequireAdminMiddleware in container
  - _Requirements: 8.1_

- [ ]* 9. Write unit tests for payment management
  - Write unit tests for BetalningService business logic
  - Write unit tests for Betalning model
  - Write unit tests for data validation (required fields, types)
  - Write unit tests for welcome email logic (all conditional branches)
  - Mock repository, email, and logger dependencies in service tests
  - Test result object creation and error handling
  - Test sanitization rules
  - _Requirements: All_

- [ ]* 10. Write integration tests
  - Write integration tests for BetalningController endpoints
  - Test full request/response cycle with middleware
  - Test database operations with test database
  - Test foreign key constraints (CASCADE delete)
  - Test trigger functionality for updated_at
  - Test LEFT JOIN queries for member names
  - Test payment status checking (memberHasPayed)
  - Test welcome email sending with mock SMTP
  - Test member flag update after email sent
  - Test AJAX operations (create and delete)
  - _Requirements: All_
