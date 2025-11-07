# Design Document: Member Management System

## Overview

The Member Management System implements a layered architecture with Controllers, Services, Repositories, and Models to manage sailing club member records. It follows PSR-7 for HTTP messages, uses dependency injection via League Container, and implements middleware-based access control.

## Architecture

### Layer Responsibilities

- **Controller Layer** (`MedlemController`): Handles HTTP requests, coordinates service calls, returns PSR-7 responses
- **Service Layer** (`MedlemService`): Contains business logic, orchestrates repository operations, returns result objects
- **Repository Layer** (`MedlemRepository`): Manages database operations, executes SQL queries, returns domain models
- **Model Layer** (`Medlem`): Represents member entities with properties and methods

### Request Flow

```
HTTP Request → Middleware Stack → Router → MedlemController
    ↓
MedlemService (business logic)
    ↓
MedlemRepository (data access)
    ↓
SQLite Database
    ↓
Medlem Model (domain entity)
    ↓
View Template or JSON Response
```

## Components and Interfaces

### MedlemController

**Responsibilities:**
- Handle HTTP requests for member operations
- Validate route parameters
- Coordinate with MedlemService
- Return appropriate responses (views or JSON)
- Handle redirects with flash messages

**Key Methods:**
- `listAll()`: Display all members in a view
- `listJson()`: Return all members as JSON
- `edit(ServerRequestInterface $request, array $params)`: Display member edit form
- `update(ServerRequestInterface $request, array $params)`: Process member updates
- `showNewForm()`: Display new member creation form
- `create()`: Process new member creation
- `delete()`: Delete a member by ID

**Dependencies:**
- `MedlemService`: Business logic operations
- `View`: Template rendering
- `UrlGeneratorService`: URL generation for routes

### MedlemService

**Responsibilities:**
- Implement business logic for member operations
- Validate and sanitize input data
- Orchestrate repository calls
- Return standardized result objects

**Key Methods:**
- `getAllMembers()`: Retrieve all members
- `getMemberEditData(int $id)`: Get member with related data (roles, payments, sailings)
- `updateMember(int $id, array $data)`: Update member record
- `createMember(array $data)`: Create new member
- `deleteMember(int $id)`: Delete member
- `getAllRoles()`: Retrieve all available roles

**Return Type:**
- `MedlemServiceResult`: Standardized result object with success flag, message, redirect route

### MedlemRepository

**Responsibilities:**
- Execute SQL queries against Medlem table
- Map database rows to Medlem models
- Handle database transactions
- Manage relationships (Medlem_Roll, payments, sailings)

**Key Methods:**
- `findAll()`: Get all members
- `findById(int $id)`: Get member by ID
- `create(Medlem $medlem)`: Insert new member
- `update(Medlem $medlem)`: Update existing member
- `delete(int $id)`: Delete member by ID
- `getMemberRoles(int $id)`: Get roles for a member
- `assignRole(int $medlemId, int $rollId)`: Assign role to member
- `removeRole(int $medlemId, int $rollId)`: Remove role from member

### Medlem Model

**Properties:**
- `id`: Integer (primary key)
- `fodelsedatum`: String (VARCHAR 10)
- `fornamn`: String (VARCHAR 50)
- `efternamn`: String (VARCHAR 100, required)
- `gatuadress`: String (VARCHAR 100)
- `postnummer`: String (VARCHAR 10)
- `postort`: String (VARCHAR 50)
- `mobil`: String (VARCHAR 20)
- `telefon`: String (VARCHAR 20)
- `email`: String (VARCHAR 50, unique)
- `kommentar`: String (VARCHAR 500)
- `godkant_gdpr`: Boolean
- `pref_kommunikation`: Boolean
- `foretag`: Boolean
- `standig_medlem`: Boolean
- `skickat_valkomstbrev`: Boolean
- `password`: String (VARCHAR 50)
- `isAdmin`: Boolean
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Key Methods:**
- `getNamn()`: Get full name (fornamn + efternamn)
- Getters and setters for all properties

## Data Models

### Database Schema

```sql
CREATE TABLE Medlem (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fodelsedatum VARCHAR(10), 
    fornamn VARCHAR(50),
    efternamn VARCHAR(100) NOT NULL,
    gatuadress VARCHAR(100),
    postnummer VARCHAR(10),
    postort VARCHAR(50),
    mobil VARCHAR(20),
    telefon VARCHAR(20),
    email VARCHAR(50) UNIQUE,
    kommentar VARCHAR(500),
    godkant_gdpr BOOLEAN DEFAULT 0,
    pref_kommunikation BOOLEAN DEFAULT 1,
    foretag BOOLEAN DEFAULT 0, 
    standig_medlem BOOLEAN DEFAULT 0,
    skickat_valkomstbrev BOOLEAN DEFAULT 1,
    password VARCHAR(50),
    isAdmin BOOLEAN DEFAULT 0, 
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Medlem_Roll (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    medlem_id INTEGER REFERENCES Medlem(id) ON DELETE CASCADE,
    roll_id INTEGER REFERENCES Roll(id) ON DELETE CASCADE,  
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(medlem_id, roll_id)
);

CREATE TRIGGER medlem_after_update 
AFTER UPDATE ON Medlem
FOR EACH ROW
BEGIN
    UPDATE Medlem SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;
```

### Relationships

- **Medlem → Medlem_Roll**: One-to-many (a member can have multiple roles)
- **Medlem → Betalning**: One-to-many (a member can have multiple payments)
- **Medlem → Segling_Medlem_Roll**: One-to-many (a member can participate in multiple sailings)

### MedlemServiceResult

```php
class MedlemServiceResult {
    public bool $success;
    public string $message;
    public string $redirectRoute;
    public ?int $medlemId;
}
```

## Error Handling

### Validation Errors

- Invalid or missing required fields return error messages
- Email uniqueness violations caught and reported
- Data type mismatches prevented by type hints and validation

### Database Errors

- Repository methods throw exceptions on database failures
- Service layer catches exceptions and returns error results
- Controller layer handles service errors with flash messages and redirects

### Not Found Errors

- Member not found by ID returns 404 or redirect with error message
- Related data not found handled gracefully (empty arrays)

### Error Response Pattern

```php
try {
    $result = $this->medlemService->operation();
    if ($result->success) {
        return $this->redirectWithSuccess($result->redirectRoute, $result->message);
    } else {
        return $this->redirectWithError($result->redirectRoute, $result->message);
    }
} catch (Exception $e) {
    return $this->redirectWithError('medlem-list', 'Operation failed');
}
```

## Testing Strategy

### Unit Tests

**MedlemService Tests:**
- Test business logic in isolation
- Mock repository dependencies
- Verify data validation and sanitization
- Test result object creation

**MedlemRepository Tests:**
- Test SQL query generation
- Test model mapping
- Test relationship handling
- Use in-memory SQLite database

**Medlem Model Tests:**
- Test property getters/setters
- Test getNamn() method
- Test data validation

### Integration Tests

**Controller Integration Tests:**
- Test full request/response cycle
- Test middleware application (RequireAdminMiddleware)
- Test view rendering
- Test JSON responses
- Test redirect behavior

**Database Integration Tests:**
- Test CRUD operations against test database
- Test foreign key constraints
- Test cascade deletes
- Test trigger behavior (updated_at)
- Test unique constraints (email)

### Test Data

- Use fixtures for consistent test data
- Create test members with various attribute combinations
- Test edge cases (missing optional fields, maximum lengths)
- Test Swedish characters in names and addresses

## Security Considerations

### Access Control

- All member management routes protected by `RequireAdminMiddleware`
- Session-based authentication required
- Admin status verified from session (isAdmin = true)

### Input Sanitization

- All POST data sanitized before processing
- SQL injection prevented by parameterized queries
- XSS prevention in view templates

### CSRF Protection

- CSRF token validation on all POST requests
- Token generated and stored in session
- Token validated using hash_equals for timing attack prevention

### Password Handling

- Passwords hashed before storage (implementation in auth system)
- Plain text passwords never logged or displayed
