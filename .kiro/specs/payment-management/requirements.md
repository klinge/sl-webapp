# Requirements Document: Payment Management System

## Introduction

The Payment Management System enables administrators to track membership fees and payments for sailing club members. It provides functionality to create, view, and delete payment records, associate payments with members, and automatically send welcome emails to new paying members.

## Glossary

- **System**: The SL Payment Management System
- **Admin User**: An authenticated user with administrative privileges
- **Payment**: A membership fee or payment record (Betalning entity)
- **Member**: A sailing club member (Medlem entity)
- **Payment Year**: The year for which a payment applies (avser_ar field)
- **Betalning Repository**: The data access layer for payment records
- **Betalning Service**: The business logic layer for payment operations
- **Welcome Email**: An automated email sent to members on their first payment

## Requirements

### Requirement 1: List All Payments

**User Story:** As an Admin User, I want to view a list of all payments, so that I can see an overview of membership fees received.

#### Acceptance Criteria

1. WHEN an Admin User navigates to the payment list page, THE System SHALL retrieve all payment records from the Betalning Repository
2. THE System SHALL retrieve member names (fornamn, efternamn) for each payment via LEFT JOIN
3. THE System SHALL order payments by date in descending order (most recent first)
4. THE System SHALL display each payment's amount, date, year, member name, and comment
5. THE System SHALL provide a link to view payments for each member

### Requirement 2: View Member Payments

**User Story:** As an Admin User, I want to view all payments for a specific member, so that I can see their payment history.

#### Acceptance Criteria

1. WHEN an Admin User requests payments for a member by ID, THE System SHALL retrieve the member record from the Medlem Repository
2. IF the member does not exist, THEN THE System SHALL throw an exception
3. THE System SHALL retrieve all payment records for the member from the Betalning Repository
4. THE System SHALL order payments by date in descending order
5. THE System SHALL display the member's name and their payment list
6. IF no payments exist for the member, THEN THE System SHALL display "Inga betalningar hittades"

### Requirement 3: Create Payment

**User Story:** As an Admin User, I want to create a new payment record, so that I can track membership fees received.

#### Acceptance Criteria

1. WHEN an Admin User submits payment data, THE System SHALL validate that belopp (amount) is provided
2. THE System SHALL validate that datum (date) is provided
3. THE System SHALL validate that avser_ar (payment year) is provided
4. IF required fields are missing, THEN THE System SHALL return error message "Belopp, datum, and avser_ar are required fields."
5. THE System SHALL sanitize all input data before processing
6. THE System SHALL validate that medlem_id is a valid integer
7. THE System SHALL validate that datum is in Y-m-d format
8. THE System SHALL validate that avser_ar is a valid year
9. THE System SHALL validate that belopp is a valid float
10. WHEN validation passes, THE System SHALL create a new payment record in the Betalning Repository
11. WHEN creation succeeds, THE System SHALL log the payment ID and user who created it
12. WHEN creation succeeds, THE System SHALL check if this is the member's first payment
13. WHEN this is the member's first payment, THE System SHALL send a welcome email
14. WHEN creation succeeds, THE System SHALL return success message with payment ID
15. IF creation fails, THEN THE System SHALL log the error and return error message

### Requirement 4: Delete Payment

**User Story:** As an Admin User, I want to delete a payment record, so that I can remove erroneous or duplicate entries.

#### Acceptance Criteria

1. WHEN an Admin User requests to delete a payment by ID, THE System SHALL validate the payment exists
2. THE System SHALL delete the payment record from the Betalning Repository
3. WHEN deletion succeeds, THE System SHALL return success message "Betalning deleted successfully"
4. IF the payment does not exist, THEN THE System SHALL return error message "Payment not found"
5. IF deletion fails, THEN THE System SHALL log the error and return error message

### Requirement 5: Payment Data Validation

**User Story:** As an Admin User, I want payment data to be validated, so that the database maintains data integrity.

#### Acceptance Criteria

1. THE System SHALL require medlem_id for all payment records
2. THE System SHALL require belopp (amount) for all payment records
3. THE System SHALL require datum (date) for all payment records
4. THE System SHALL require avser_ar (payment year) for all payment records
5. THE System SHALL validate that belopp is a DECIMAL type
6. THE System SHALL validate that datum is a DATE type
7. THE System SHALL validate that avser_ar is an INT type
8. THE System SHALL enforce maximum length of 200 characters for kommentar
9. THE System SHALL allow kommentar to be optional (empty string)

### Requirement 6: Check Payment Status

**User Story:** As the System, I want to check if a member has paid for a specific year, so that payment status can be determined.

#### Acceptance Criteria

1. WHEN checking if a member has paid for a year, THE System SHALL query the Betalning Repository
2. THE System SHALL search for payments matching both medlem_id and avser_ar
3. WHEN one or more matching payments exist, THE System SHALL return true
4. WHEN no matching payments exist, THE System SHALL return false

### Requirement 7: Welcome Email on First Payment

**User Story:** As an Admin User, I want new paying members to receive a welcome email automatically, so that they feel welcomed to the club.

#### Acceptance Criteria

1. WHEN a payment is created, THE System SHALL check if welcome email sending is enabled via WELCOME_MAIL_ENABLED config
2. IF welcome email is disabled, THEN THE System SHALL log "Sending mail is disabled" and skip email sending
3. THE System SHALL retrieve the member record by medlem_id
4. IF the member does not exist, THEN THE System SHALL log a warning and skip email sending
5. THE System SHALL check the member's skickat_valkomstbrev flag
6. IF skickat_valkomstbrev is true, THEN THE System SHALL skip email sending (already sent)
7. THE System SHALL check if the member has an email address
8. IF the member has no email address, THEN THE System SHALL log a warning and skip email sending
9. WHEN all conditions are met, THE System SHALL send a welcome email with member's fornamn and efternamn
10. WHEN email sending succeeds, THE System SHALL log the success
11. WHEN email sending succeeds, THE System SHALL set skickat_valkomstbrev to true
12. WHEN email sending succeeds, THE System SHALL save the updated member record
13. IF email sending fails, THEN THE System SHALL log the error and continue without failing the payment creation

### Requirement 8: Access Control

**User Story:** As the System, I want to restrict payment management operations to Admin Users only, so that unauthorized users cannot modify payment data.

#### Acceptance Criteria

1. THE System SHALL apply RequireAdminMiddleware to all payment management routes
2. WHEN a non-admin user attempts to access payment management, THE System SHALL deny access
3. WHEN an unauthenticated user attempts to access payment management, THE System SHALL redirect to the login page
4. THE System SHALL verify admin status from the session data

### Requirement 9: Payment Data Timestamps

**User Story:** As an Admin User, I want payment records to track creation and modification times, so that I can audit when changes were made.

#### Acceptance Criteria

1. WHEN a new payment is created, THE System SHALL set created_at to the current timestamp
2. WHEN a new payment is created, THE System SHALL set updated_at to the current timestamp
3. WHEN a payment record is updated, THE System SHALL automatically update the updated_at timestamp via database trigger
4. THE System SHALL use CURRENT_TIMESTAMP for all timestamp values

### Requirement 10: Foreign Key Relationships

**User Story:** As the System, I want to maintain referential integrity between payments and members, so that data consistency is preserved.

#### Acceptance Criteria

1. THE System SHALL enforce a foreign key relationship between Betalning.medlem_id and Medlem.id
2. WHEN a member is deleted, THE System SHALL cascade delete all associated payment records (ON DELETE CASCADE)
3. THE System SHALL allow payments to be created only for existing members
4. THE System SHALL prevent orphaned payment records (payments without valid member references)

### Requirement 11: Payment Reporting

**User Story:** As an Admin User, I want to retrieve payment data with member information, so that I can generate reports.

#### Acceptance Criteria

1. THE System SHALL provide a method to retrieve all payments with member names
2. THE System SHALL use LEFT JOIN to include member fornamn and efternamn
3. THE System SHALL return payment data as associative arrays for reporting
4. THE System SHALL order results by payment date in descending order
5. THE System SHALL handle cases where medlem_id is NULL (member deleted)
