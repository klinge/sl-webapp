# Design Document: Sailing Activity Management System

## Overview

The Sailing Activity Management System implements a layered architecture to manage sailing trips and crew assignments. It uses a many-to-many relationship pattern to link members to sailing activities with specific roles, enabling flexible crew roster management.

## Architecture

### Layer Responsibilities

- **Controller Layer** (`SeglingController`): Handles HTTP requests, coordinates service calls, returns PSR-7 responses (views and JSON)
- **Service Layer** (`SeglingService`): Contains business logic, orchestrates repository operations, returns result objects
- **Repository Layer** (`SeglingRepository`): Manages database operations for Segling and Segling_Medlem_Roll tables
- **Model Layer** (`Segling`): Represents sailing activity entities

### Request Flow

```
HTTP Request → Middleware Stack → Router → SeglingController
    ↓
SeglingService (business logic)
    ↓
SeglingRepository (data access)
    ↓
SQLite Database (Segling, Segling_Medlem_Roll tables)
    ↓
Segling Model (domain entity)
    ↓
View Template or JSON Response
```

## Components and Interfaces

### SeglingController

**Responsibilities:**
- Handle HTTP requests for sailing operations
- Validate route parameters
- Coordinate with SeglingService
- Return appropriate responses (views or JSON)
- Handle both form-encoded and JSON request bodies

**Key Methods:**
- `list()`: Display all sailing activities
- `edit(ServerRequestInterface $request, array $params)`: Display sailing edit form with crew roster
- `save(ServerRequestInterface $request, array $params)`: Process sailing updates
- `delete(ServerRequestInterface $request, array $params)`: Delete a sailing
- `showCreate()`: Display new sailing creation form
- `create()`: Process new sailing creation
- `saveMedlem()`: Add a member to a sailing with a role
- `deleteMedlemFromSegling()`: Remove a member from a sailing

**Dependencies:**
- `SeglingService`: Business logic operations
- `View`: Template rendering
- `UrlGeneratorService`: URL generation for routes

**Response Types:**
- View responses for list, edit, and create forms
- JSON responses for AJAX operations (add/remove crew members)
- Redirect responses after successful operations

### SeglingService

**Responsibilities:**
- Implement business logic for sailing operations
- Validate and sanitize input data
- Orchestrate repository calls for sailing and crew management
- Return standardized result objects

**Key Methods:**
- `getAllSeglingar()`: Retrieve all sailing activities
- `getSeglingEditData(int $id)`: Get sailing with crew roster and available members by role
- `updateSegling(int $id, array $data)`: Update sailing record
- `createSegling(array $data)`: Create new sailing
- `deleteSegling(int $id)`: Delete sailing
- `addMemberToSegling(array $data)`: Assign member to sailing with role
- `removeMemberFromSegling(array $data)`: Remove member from sailing

**Return Type:**
- `SeglingServiceResult`: Standardized result object with success flag, message, redirect route, optional seglingId

### SeglingRepository

**Responsibilities:**
- Execute SQL queries against Segling and Segling_Medlem_Roll tables
- Map database rows to Segling models
- Handle crew assignment operations
- Manage relationships between sailings, members, and roles

**Key Methods:**
- `findAll()`: Get all sailing activities
- `findById(int $id)`: Get sailing by ID
- `create(Segling $segling)`: Insert new sailing
- `update(Segling $segling)`: Update existing sailing
- `delete(int $id)`: Delete sailing by ID
- `getCrewMembers(int $seglingId)`: Get all crew members for a sailing
- `addCrewMember(int $seglingId, int $medlemId, int $rollId)`: Add crew assignment
- `removeCrewMember(int $id)`: Remove crew assignment by Segling_Medlem_Roll ID
- `getMembersByRole(int $rollId)`: Get members who can fill a specific role

### Segling Model

**Properties:**
- `id`: Integer (primary key)
- `startdatum`: Date (required)
- `slutdatum`: Date (required)
- `skeppslag`: String (VARCHAR 100, required) - crew name
- `kommentar`: String (VARCHAR 500)
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Key Methods:**
- Getters and setters for all properties
- `getDateRange()`: Get formatted date range string

## Data Models

### Database Schema

```sql
CREATE TABLE Segling (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    startdatum DATE NOT NULL,
    slutdatum DATE NOT NULL, 
    skeppslag VARCHAR(100) NOT NULL,
    kommentar VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Segling_Medlem_Roll (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    segling_id INTEGER REFERENCES Segling(id) ON DELETE CASCADE,
    medlem_id INTEGER REFERENCES Medlem(id) ON DELETE SET NULL,
    roll_id INTEGER REFERENCES Roll(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_smr_segling_id ON Segling_Medlem_Roll(segling_id);
CREATE INDEX idx_smr_medlem_id ON Segling_Medlem_Roll(medlem_id);
CREATE INDEX idx_smr_roll_id ON Segling_Medlem_Roll(roll_id);

CREATE TRIGGER segling_after_update 
AFTER UPDATE ON Segling
FOR EACH ROW
BEGIN
    UPDATE Segling SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;
```

### Relationships

- **Segling → Segling_Medlem_Roll**: One-to-many (a sailing can have multiple crew members)
- **Medlem → Segling_Medlem_Roll**: One-to-many (a member can participate in multiple sailings)
- **Roll → Segling_Medlem_Roll**: One-to-many (a role can be used in multiple crew assignments)

### Foreign Key Behavior

- **ON DELETE CASCADE** for segling_id: When a sailing is deleted, all crew assignments are deleted
- **ON DELETE SET NULL** for medlem_id: When a member is deleted, crew assignments remain but medlem_id becomes NULL
- **ON DELETE SET NULL** for roll_id: When a role is deleted, crew assignments remain but roll_id becomes NULL

### SeglingServiceResult

```php
class SeglingServiceResult {
    public bool $success;
    public string $message;
    public string $redirectRoute;
    public ?int $seglingId;
}
```

## Error Handling

### Validation Errors

- Missing required fields (startdatum, slutdatum, skeppslag) return error messages
- Invalid date formats caught and reported
- Date range validation (slutdatum not before startdatum)

### Database Errors

- Repository methods throw exceptions on database failures
- Service layer catches exceptions and returns error results
- Controller layer handles service errors with flash messages or JSON error responses

### Not Found Errors

- Sailing not found by ID returns 404 response
- Member or role not found when assigning crew returns error message

### Error Response Patterns

**For View Responses:**
```php
Session::setFlashMessage($result->success ? 'success' : 'error', $result->message);
$redirectUrl = $this->createUrl($result->redirectRoute);
return new RedirectResponse($redirectUrl);
```

**For JSON Responses:**
```php
return $this->jsonResponse([
    'success' => $result->success,
    'message' => $result->message
]);
```

## User Interface Design

### Sailing List View

- Table displaying all sailing activities
- Columns: Start Date, End Date, Crew Name, Comment
- Edit button for each sailing
- "Create New Sailing" button

### Sailing Edit View

- Form fields for startdatum, slutdatum, skeppslag, kommentar
- Crew roster section showing assigned members with roles
- Remove button for each crew member (AJAX)
- Add crew member section with dropdowns:
  - Select member (filtered by role)
  - Select role
  - Add button (AJAX)
- Save and Delete buttons

### Sailing Create View

- Form fields for startdatum, slutdatum, skeppslag, kommentar
- Create button
- Note: Crew members added after creation via edit view

## AJAX Operations

### Add Crew Member

**Request:**
```
POST /segling/medlem
Content-Type: application/x-www-form-urlencoded

segling_id=1&medlem_id=5&roll_id=2
```

**Response:**
```json
{
    "success": true,
    "message": "Medlem tillagd"
}
```

### Remove Crew Member

**Request:**
```
POST /segling/medlem/delete
Content-Type: application/json

{
    "id": 123
}
```

**Response:**
```json
{
    "status": "ok",
    "error": null
}
```

## Testing Strategy

### Unit Tests

**SeglingService Tests:**
- Test business logic in isolation
- Mock repository dependencies
- Verify data validation (required fields, date ranges)
- Test result object creation

**SeglingRepository Tests:**
- Test SQL query generation
- Test model mapping
- Test crew assignment operations
- Use in-memory SQLite database

**Segling Model Tests:**
- Test property getters/setters
- Test date range formatting

### Integration Tests

**Controller Integration Tests:**
- Test full request/response cycle
- Test middleware application (RequireAdminMiddleware)
- Test view rendering
- Test JSON responses for AJAX operations
- Test redirect behavior
- Test both form-encoded and JSON request bodies

**Database Integration Tests:**
- Test CRUD operations against test database
- Test foreign key constraints (CASCADE and SET NULL)
- Test trigger functionality (updated_at)
- Test indexes for query performance
- Test many-to-many relationship integrity

### Test Scenarios

- Create sailing with valid data
- Create sailing with missing required fields
- Update sailing dates and crew name
- Delete sailing and verify cascade delete of crew assignments
- Add multiple crew members to a sailing
- Remove crew member from sailing
- Delete member and verify crew assignments set medlem_id to NULL
- Assign same member to multiple sailings
- Query sailings by date range

## Security Considerations

### Access Control

- All sailing management routes protected by `RequireAdminMiddleware`
- Session-based authentication required
- Admin status verified from session

### Input Sanitization

- All POST data sanitized before processing
- SQL injection prevented by parameterized queries
- XSS prevention in view templates

### CSRF Protection

- CSRF token validation on all POST requests (except AJAX with JSON)
- Token generated and stored in session

### Content Type Handling

- Controller handles both form-encoded and JSON request bodies
- Content-Type header checked for JSON requests
- Proper parsing for each content type
