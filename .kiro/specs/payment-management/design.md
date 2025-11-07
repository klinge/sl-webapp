# Design Document: Payment Management System

## Overview

The Payment Management System implements a layered architecture to track membership fees and payments. It integrates with the member management system and includes automated welcome email functionality for new paying members.

## Architecture

### Layer Responsibilities

- **Controller Layer** (`BetalningController`): Handles HTTP requests, coordinates service calls, returns PSR-7 responses
- **Service Layer** (`BetalningService`): Contains business logic, orchestrates repository operations, manages welcome emails
- **Repository Layer** (`BetalningRepository`): Manages database operations for Betalning table
- **Model Layer** (`Betalning`): Represents payment entities

### Request Flow

```
HTTP Request → Middleware Stack → Router → BetalningController
    ↓
BetalningService (business logic)
    ↓
BetalningRepository (data access)
    ↓
SQLite Database (Betalning table)
    ↓
Betalning Model (domain entity)
    ↓
JSON Response or View
```

### Welcome Email Flow

```
Payment Created → Check if first payment → 
Check config enabled → Check member exists → 
Check not already sent → Check email exists → 
Send welcome email → Update skickat_valkomstbrev flag
```

## Components and Interfaces

### BetalningController

**Responsibilities:**
- Handle HTTP requests for payment operations
- Validate route parameters
- Coordinate with BetalningService
- Return appropriate responses (views or JSON)

**Key Methods:**
- `list()`: Display all payments with member names
- `getBetalning(ServerRequestInterface $request, array $params)`: Get specific payment (not yet implemented)
- `getMedlemBetalning(ServerRequestInterface $request, array $params)`: Get all payments for a member
- `createBetalning(ServerRequestInterface $request)`: Create new payment
- `deleteBetalning(ServerRequestInterface $request, array $params)`: Delete payment by ID

**Dependencies:**
- `BetalningService`: Business logic operations
- `View`: Template rendering
- `UrlGeneratorService`: URL generation for routes

**Response Types:**
- View responses for list and member payment views
- JSON responses for create and delete operations

### BetalningService

**Responsibilities:**
- Implement business logic for payment operations
- Validate and sanitize input data
- Orchestrate repository calls
- Manage welcome email sending
- Return standardized result objects

**Key Methods:**
- `getAllPayments()`: Retrieve all payments with member names
- `getPaymentsForMember(int $memberId)`: Get member and their payments
- `createPayment(array $postData)`: Validate, create payment, send welcome email if needed
- `deletePayment(int $id)`: Delete payment by ID
- `sendWelcomeEmailOnFirstPayment(int $memberId)`: Private method to handle welcome email logic

**Business Logic:**
- Validate required fields (belopp, datum, avser_ar)
- Sanitize input data with type-specific rules
- Check if payment is member's first payment
- Send welcome email only if conditions are met
- Update member's skickat_valkomstbrev flag after email sent

**Return Type:**
- `BetalningServiceResult`: Standardized result object with success flag, message, optional paymentId

### BetalningRepository

**Responsibilities:**
- Execute SQL queries against Betalning table
- Map database rows to Betalning models
- Handle payment CRUD operations
- Provide payment status checking

**Key Methods:**
- `getAll()`: Get all payments as Betalning objects
- `findAllWithMemberNames()`: Get all payments with member names (LEFT JOIN)
- `getBetalningForMedlem(int $medlemId)`: Get payments for specific member
- `memberHasPayed(int $medlemId, int $year)`: Check if member paid for year
- `create(Betalning $betalning)`: Insert new payment
- `deleteById(int $id)`: Delete payment by ID
- `getById(int $id)`: Get payment by ID
- `createBetalningFromData(array $data)`: Map database row to Betalning object

### Betalning Model

**Properties:**
- `id`: Integer (primary key)
- `medlem_id`: Integer (foreign key to Medlem, required)
- `belopp`: Float (DECIMAL, required)
- `datum`: String (DATE, required)
- `avser_ar`: Integer (payment year, required)
- `kommentar`: String (VARCHAR 200, optional)
- `created_at`: String (timestamp)
- `updated_at`: String (timestamp)

**Characteristics:**
- Pure data object with no dependencies
- All properties are public for direct access
- No business logic in model

## Data Models

### Database Schema

```sql
CREATE TABLE Betalning (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    medlem_id INTEGER, 
    belopp DECIMAL NOT NULL,
    datum DATE, 
    avser_ar INT NOT NULL,
    kommentar VARCHAR(200),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medlem_id) REFERENCES Medlem(id) ON DELETE CASCADE
);

CREATE TRIGGER betalning_after_update 
AFTER UPDATE ON Betalning
FOR EACH ROW
BEGIN
    UPDATE Betalning SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;
```

### Relationships

- **Betalning → Medlem**: Many-to-one (many payments can belong to one member)
- **Foreign Key Behavior**: ON DELETE CASCADE (when member deleted, payments are deleted)

### BetalningServiceResult

```php
class BetalningServiceResult {
    public bool $success;
    public string $message;
    public ?int $paymentId;
    
    public function __construct(bool $success, string $message, ?int $paymentId = null) {
        $this->success = $success;
        $this->message = $message;
        $this->paymentId = $paymentId;
    }
}
```

## Data Sanitization

### Sanitization Rules

```php
$rules = [
    'medlem_id' => 'string',           // Sanitize as string, then cast to int
    'datum' => ['date', 'Y-m-d'],      // Validate and format as Y-m-d
    'avser_ar' => ['date', 'Y'],       // Validate and format as year
    'belopp' => 'float',               // Sanitize and cast to float
    'kommentar' => 'string',           // Sanitize as string
];
```

### Validation Flow

1. Check required fields are present
2. Sanitize input using Sanitizer utility
3. Cast to appropriate types
4. Validate data integrity
5. Create Betalning object
6. Persist to database

## Welcome Email Feature

### Configuration

**Environment Variable:**
```
WELCOME_MAIL_ENABLED=1  # 1 to enable, 0 to disable
```

### Email Template

**Template:** `emails/viewWelcomeEmail.php`

**Data:**
- `fornamn`: Member first name
- `efternamn`: Member last name

**Subject:** "Välkommen till föreningen Sofia Linnea"

### Welcome Email Logic

```php
private function sendWelcomeEmailOnFirstPayment(int $memberId): bool
{
    // 1. Check if feature is enabled
    if (config not enabled) return false;
    
    // 2. Get member record
    if (member not found) log warning, return false;
    
    // 3. Check if already sent
    if (skickat_valkomstbrev == true) return false;
    
    // 4. Check email exists
    if (email empty) log warning, return false;
    
    // 5. Send email
    try {
        send email with fornamn and efternamn
        log success
        set skickat_valkomstbrev = true
        save member
        return true
    } catch (exception) {
        log error
        return false
    }
}
```

### Error Handling

- Welcome email failures do NOT fail payment creation
- All failures are logged for monitoring
- Member flag only updated on successful send

## Error Handling

### Validation Errors

- Missing required fields return specific error messages
- Invalid data types caught during sanitization
- Database constraint violations caught and logged

### Database Errors

- Repository methods throw exceptions on database failures
- Service layer catches exceptions and returns error results
- Controller layer handles service errors with JSON error responses

### Not Found Errors

- Member not found throws exception in getPaymentsForMember
- Payment not found returns error message in deletePayment

### Error Response Pattern

**JSON Response:**
```php
return $this->jsonResponse([
    'success' => false,
    'message' => 'Error description'
]);
```

**Service Result:**
```php
return new BetalningServiceResult(
    false,
    'Error message'
);
```

## User Interface Design

### Payment List View

- Table displaying all payments
- Columns: Date, Amount, Year, Member Name, Comment
- Link to view member's payment history
- Ordered by date (most recent first)

### Member Payment View

- Member name as title
- Table of member's payments
- Columns: Date, Amount, Year, Comment
- Message if no payments found

### Payment Creation

- Typically done via AJAX from member edit page
- Form fields: medlem_id, belopp, datum, avser_ar, kommentar
- JSON response with success/error message

### Payment Deletion

- Typically done via AJAX
- Confirmation before deletion
- JSON response with success/error message

## AJAX Operations

### Create Payment

**Request:**
```
POST /betalning/create
Content-Type: application/x-www-form-urlencoded

medlem_id=5&belopp=500.00&datum=2024-01-15&avser_ar=2024&kommentar=Medlemsavgift
```

**Response:**
```json
{
    "success": true,
    "message": "Betalning created successfully. Id: 123"
}
```

### Delete Payment

**Request:**
```
POST /betalning/delete/123
```

**Response:**
```json
{
    "success": true,
    "message": "Betalning deleted successfully"
}
```

## Testing Strategy

### Unit Tests

**BetalningService Tests:**
- Test business logic in isolation
- Mock repository and email dependencies
- Verify data validation
- Test welcome email logic (enabled/disabled, already sent, no email)
- Test result object creation

**BetalningRepository Tests:**
- Test SQL query generation
- Test model mapping
- Test payment status checking
- Use in-memory SQLite database

**Betalning Model Tests:**
- Test property assignment
- Test data integrity

### Integration Tests

**Controller Integration Tests:**
- Test full request/response cycle
- Test middleware application (RequireAdminMiddleware)
- Test view rendering
- Test JSON responses
- Test error handling

**Database Integration Tests:**
- Test CRUD operations against test database
- Test foreign key constraints (CASCADE delete)
- Test trigger functionality (updated_at)
- Test LEFT JOIN for member names
- Test payment status queries

**Email Integration Tests:**
- Test welcome email sending with mock SMTP
- Test email template rendering
- Test member flag update after email sent
- Test error handling when email fails

### Test Scenarios

- Create payment with valid data
- Create payment with missing required fields
- Create payment for non-existent member
- Delete existing payment
- Delete non-existent payment
- Get payments for member with payments
- Get payments for member without payments
- Get payments for non-existent member
- Check payment status for paid year
- Check payment status for unpaid year
- Send welcome email on first payment
- Skip welcome email when already sent
- Skip welcome email when disabled
- Handle email sending failure gracefully

## Security Considerations

### Access Control

- All payment management routes protected by `RequireAdminMiddleware`
- Session-based authentication required
- Admin status verified from session

### Input Sanitization

- All POST data sanitized before processing
- Type-specific sanitization rules applied
- SQL injection prevented by parameterized queries
- XSS prevention in view templates

### Data Validation

- Required fields validated before database operations
- Data types validated and cast appropriately
- Foreign key constraints enforced at database level

### Logging

- Payment creation logged with payment ID and user ID
- Payment deletion logged
- Welcome email sending logged (success and failure)
- Errors logged with context for debugging

## Configuration

### Environment Variables

```
WELCOME_MAIL_ENABLED=1  # Enable/disable welcome emails
```

### Email Configuration

Configured via Email utility class with SMTP settings from environment variables.
