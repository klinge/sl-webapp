# Requirements Document: Sailing Activity Management System

## Introduction

The Sailing Activity Management System enables administrators to create, manage, and track sailing trips (seglingar). It manages crew assignments by linking members to sailing activities with specific roles (skipper, crew, cook), and maintains a complete history of all sailing activities.

## Glossary

- **System**: The SL Sailing Activity Management System
- **Admin User**: An authenticated user with administrative privileges
- **Sailing Activity**: A sailing trip or event (Segling entity)
- **Crew Member**: A member assigned to a sailing activity with a specific role
- **Role**: A position on a sailing trip (e.g., Skeppare/Skipper, Båtsman/Crew, Kock/Cook)
- **Segling Repository**: The data access layer for sailing activity records
- **Segling Service**: The business logic layer for sailing operations
- **Crew Assignment**: A Segling_Medlem_Roll record linking a member to a sailing with a role

## Requirements

### Requirement 1: List All Sailing Activities

**User Story:** As an Admin User, I want to view a list of all sailing activities, so that I can see an overview of past and upcoming trips.

#### Acceptance Criteria

1. WHEN an Admin User navigates to the sailing list page, THE System SHALL retrieve all sailing activity records from the Segling Repository
2. THE System SHALL display each sailing's dates, crew name (skeppslag), and comment
3. THE System SHALL provide a link to create a new sailing activity
4. THE System SHALL provide a link to edit each sailing activity
5. THE System SHALL order sailing activities by date (most recent first)

### Requirement 2: View Sailing Activity Details

**User Story:** As an Admin User, I want to view detailed information about a specific sailing activity, so that I can see the complete crew roster and trip details.

#### Acceptance Criteria

1. WHEN an Admin User requests a sailing activity by ID, THE System SHALL retrieve the sailing record from the Segling Repository
2. THE System SHALL retrieve all crew members assigned to the sailing with their roles
3. THE System SHALL retrieve all available roles from the database
4. THE System SHALL retrieve lists of all members who can be assigned as Skeppare (skippers)
5. THE System SHALL retrieve lists of all members who can be assigned as Båtsman (crew)
6. THE System SHALL retrieve lists of all members who can be assigned as Kock (cooks)
7. IF the sailing ID does not exist, THEN THE System SHALL return a 404 not found response
8. THE System SHALL display the form action URL for saving changes

### Requirement 3: Create New Sailing Activity

**User Story:** As an Admin User, I want to create a new sailing activity, so that I can schedule and track upcoming trips.

#### Acceptance Criteria

1. WHEN an Admin User requests the new sailing form, THE System SHALL display a form with fields for startdatum, slutdatum, skeppslag, and kommentar
2. WHEN the Admin User submits the form, THE System SHALL validate that startdatum is provided
3. THE System SHALL validate that slutdatum is provided
4. THE System SHALL validate that skeppslag (crew name) is provided
5. THE System SHALL sanitize all input data before processing
6. WHEN validation passes, THE System SHALL create a new sailing record in the Segling Repository
7. WHEN creation succeeds, THE System SHALL redirect to the sailing edit page with the new sailing ID
8. IF creation fails, THEN THE System SHALL redirect to the sailing list with an error message

### Requirement 4: Update Sailing Activity

**User Story:** As an Admin User, I want to update a sailing activity's information, so that I can correct details or update trip information.

#### Acceptance Criteria

1. WHEN an Admin User submits updated sailing data, THE System SHALL validate the sailing ID exists
2. THE System SHALL sanitize all input data before processing
3. THE System SHALL validate that required fields (startdatum, slutdatum, skeppslag) are present
4. WHEN validation passes, THE System SHALL update the sailing record in the Segling Repository
5. THE System SHALL set the updated_at timestamp to the current time via database trigger
6. WHEN update succeeds, THE System SHALL redirect to the sailing list with a success message
7. IF update fails, THEN THE System SHALL return a JSON error response

### Requirement 5: Delete Sailing Activity

**User Story:** As an Admin User, I want to delete a sailing activity, so that I can remove cancelled or erroneous trips.

#### Acceptance Criteria

1. WHEN an Admin User requests to delete a sailing, THE System SHALL validate the sailing ID exists
2. THE System SHALL delete the sailing record from the Segling Repository
3. WHEN foreign key constraints are enabled, THE System SHALL cascade delete all crew assignments (Segling_Medlem_Roll records)
4. WHEN deletion succeeds, THE System SHALL redirect to the sailing list with a success message
5. IF deletion fails, THEN THE System SHALL redirect to the sailing list with an error message

### Requirement 6: Assign Member to Sailing

**User Story:** As an Admin User, I want to assign a member to a sailing activity with a specific role, so that I can build the crew roster.

#### Acceptance Criteria

1. WHEN an Admin User assigns a member to a sailing, THE System SHALL validate the sailing ID exists
2. THE System SHALL validate the member ID exists
3. THE System SHALL validate the role ID exists
4. THE System SHALL create a Segling_Medlem_Roll record with the sailing, member, and role IDs
5. THE System SHALL set the created_at timestamp to the current time
6. WHEN assignment succeeds, THE System SHALL return a JSON success response
7. IF assignment fails, THEN THE System SHALL return a JSON error response with a descriptive message

### Requirement 7: Remove Member from Sailing

**User Story:** As an Admin User, I want to remove a member from a sailing activity, so that I can adjust the crew roster when plans change.

#### Acceptance Criteria

1. WHEN an Admin User removes a member from a sailing, THE System SHALL accept both JSON and form-encoded request bodies
2. THE System SHALL validate the Segling_Medlem_Roll record ID exists
3. THE System SHALL delete the Segling_Medlem_Roll record
4. WHEN removal succeeds, THE System SHALL return a JSON response with status "ok"
5. IF removal fails, THEN THE System SHALL return a JSON response with status "fail" and error message

### Requirement 8: Sailing Data Validation

**User Story:** As an Admin User, I want sailing data to be validated, so that the database maintains data integrity.

#### Acceptance Criteria

1. THE System SHALL require startdatum (start date) for all sailing records
2. THE System SHALL require slutdatum (end date) for all sailing records
3. THE System SHALL require skeppslag (crew name) for all sailing records
4. THE System SHALL validate date format for startdatum and slutdatum as DATE type
5. THE System SHALL enforce maximum length of 100 characters for skeppslag
6. THE System SHALL enforce maximum length of 500 characters for kommentar
7. THE System SHALL validate that slutdatum is not before startdatum

### Requirement 9: Access Control

**User Story:** As the System, I want to restrict sailing management operations to Admin Users only, so that unauthorized users cannot modify sailing data.

#### Acceptance Criteria

1. THE System SHALL apply RequireAdminMiddleware to all sailing management routes
2. WHEN a non-admin user attempts to access sailing management, THE System SHALL deny access
3. WHEN an unauthenticated user attempts to access sailing management, THE System SHALL redirect to the login page
4. THE System SHALL verify admin status from the session data

### Requirement 10: Sailing Data Timestamps

**User Story:** As an Admin User, I want sailing records to track creation and modification times, so that I can audit when changes were made.

#### Acceptance Criteria

1. WHEN a new sailing is created, THE System SHALL set created_at to the current timestamp
2. WHEN a new sailing is created, THE System SHALL set updated_at to the current timestamp
3. WHEN a sailing record is updated, THE System SHALL automatically update the updated_at timestamp via database trigger
4. WHEN a crew assignment is created, THE System SHALL set created_at to the current timestamp
5. THE System SHALL use CURRENT_TIMESTAMP for all timestamp values

### Requirement 11: Crew Assignment Tracking

**User Story:** As an Admin User, I want to track which members participated in each sailing with their roles, so that I can maintain accurate sailing history.

#### Acceptance Criteria

1. THE System SHALL maintain a many-to-many relationship between Segling and Medlem through Segling_Medlem_Roll
2. THE System SHALL allow multiple members to be assigned to a single sailing
3. THE System SHALL allow a single member to be assigned to multiple sailings
4. THE System SHALL associate each crew assignment with a specific role
5. WHEN a member is deleted, THE System SHALL set the medlem_id to NULL in crew assignments (ON DELETE SET NULL)
6. WHEN a role is deleted, THE System SHALL set the roll_id to NULL in crew assignments (ON DELETE SET NULL)
7. THE System SHALL create indexes on segling_id, medlem_id, and roll_id for query performance
