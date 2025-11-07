# Requirements Document: Member Management System

## Introduction

The Member Management System provides comprehensive CRUD operations for managing sailing club members (Medlem). It enables administrators to create, view, update, and delete member records, manage member roles, track member payments, and view member participation in sailing activities.

## Glossary

- **System**: The SL Member Management System
- **Admin User**: An authenticated user with administrative privileges (isAdmin = true)
- **Member**: A sailing club member record (Medlem entity)
- **Role**: A club role that can be assigned to members (Roll entity)
- **Payment**: A membership fee or payment record (Betalning entity)
- **Sailing Activity**: A sailing trip or event (Segling entity)
- **Member Repository**: The data access layer for member records
- **Member Service**: The business logic layer for member operations

## Requirements

### Requirement 1: List All Members

**User Story:** As an Admin User, I want to view a list of all members, so that I can see an overview of club membership.

#### Acceptance Criteria

1. WHEN an Admin User navigates to the member list page, THE System SHALL retrieve all member records from the Member Repository
2. WHEN member records are retrieved, THE System SHALL display each member's name, contact information, and key attributes
3. THE System SHALL provide a link to create a new member from the list page
4. THE System SHALL provide a link to edit each member from the list page
5. WHERE the request accepts JSON format, THE System SHALL return member data as JSON

### Requirement 2: View Member Details

**User Story:** As an Admin User, I want to view detailed information about a specific member, so that I can see their complete profile including roles, payments, and sailing history.

#### Acceptance Criteria

1. WHEN an Admin User requests a member by ID, THE System SHALL retrieve the member record from the Member Repository
2. THE System SHALL retrieve all roles assigned to the member
3. THE System SHALL retrieve all payments made by the member
4. THE System SHALL retrieve all sailing activities the member has participated in
5. IF the member ID does not exist, THEN THE System SHALL redirect to the member list with an error message
6. THE System SHALL display edit and delete actions for the member

### Requirement 3: Create New Member

**User Story:** As an Admin User, I want to create a new member record, so that I can add new members to the club database.

#### Acceptance Criteria

1. WHEN an Admin User requests the new member form, THE System SHALL retrieve all available roles from the database
2. THE System SHALL display a form with fields for all member attributes (name, contact info, preferences, etc.)
3. WHEN the Admin User submits the form, THE System SHALL validate the submitted data
4. THE System SHALL sanitize all input data before processing
5. WHEN validation passes, THE System SHALL create a new member record in the Member Repository
6. THE System SHALL assign selected roles to the new member
7. WHEN creation succeeds, THE System SHALL redirect to the member list with a success message
8. IF creation fails, THEN THE System SHALL redirect to the member list with an error message

### Requirement 4: Update Member Information

**User Story:** As an Admin User, I want to update a member's information, so that I can keep member records current and accurate.

#### Acceptance Criteria

1. WHEN an Admin User submits updated member data, THE System SHALL validate the member ID exists
2. THE System SHALL sanitize all input data before processing
3. THE System SHALL validate that required fields are present and properly formatted
4. WHEN validation passes, THE System SHALL update the member record in the Member Repository
5. THE System SHALL update the member's assigned roles if role changes are submitted
6. THE System SHALL set the updated_at timestamp to the current time
7. WHEN update succeeds, THE System SHALL redirect to the member edit page with a success message
8. IF update fails, THEN THE System SHALL redirect to the member edit page with an error message

### Requirement 5: Delete Member

**User Story:** As an Admin User, I want to delete a member record, so that I can remove members who are no longer part of the club.

#### Acceptance Criteria

1. WHEN an Admin User requests to delete a member, THE System SHALL validate the member ID exists
2. THE System SHALL delete the member record from the Member Repository
3. WHEN foreign key constraints are enabled, THE System SHALL cascade delete related records (Medlem_Roll entries)
4. WHEN foreign key constraints are enabled, THE System SHALL set NULL for related records with ON DELETE SET NULL (Segling_Medlem_Roll entries)
5. WHEN deletion succeeds, THE System SHALL redirect to the member list with a success message
6. IF deletion fails, THEN THE System SHALL redirect to the member list with an error message

### Requirement 6: Member Data Validation

**User Story:** As an Admin User, I want member data to be validated, so that the database maintains data integrity.

#### Acceptance Criteria

1. THE System SHALL require efternamn (last name) for all member records
2. THE System SHALL enforce unique email addresses across all members
3. THE System SHALL validate email format when an email is provided
4. THE System SHALL validate date format for fodelsedatum (birth date) as VARCHAR(10)
5. THE System SHALL validate phone number formats for mobil and telefon fields
6. THE System SHALL validate postal code format for postnummer field
7. THE System SHALL enforce maximum length constraints for all VARCHAR fields
8. THE System SHALL validate boolean fields (godkant_gdpr, pref_kommunikation, foretag, standig_medlem, skickat_valkomstbrev, isAdmin)

### Requirement 7: Member Role Management

**User Story:** As an Admin User, I want to assign and remove roles for members, so that I can manage member responsibilities and permissions.

#### Acceptance Criteria

1. WHEN viewing a member, THE System SHALL display all roles currently assigned to the member
2. WHEN viewing a member, THE System SHALL display all available roles that can be assigned
3. WHEN an Admin User assigns a role to a member, THE System SHALL create a Medlem_Roll record
4. THE System SHALL enforce unique constraint on medlem_id and roll_id combinations
5. WHEN an Admin User removes a role from a member, THE System SHALL delete the corresponding Medlem_Roll record
6. THE System SHALL set created_at timestamp when creating role assignments

### Requirement 8: Access Control

**User Story:** As the System, I want to restrict member management operations to Admin Users only, so that unauthorized users cannot modify member data.

#### Acceptance Criteria

1. THE System SHALL apply RequireAdminMiddleware to all member management routes
2. WHEN a non-admin user attempts to access member management, THE System SHALL deny access
3. WHEN an unauthenticated user attempts to access member management, THE System SHALL redirect to the login page
4. THE System SHALL verify admin status from the session data (isAdmin = true)

### Requirement 9: Member Data Timestamps

**User Story:** As an Admin User, I want member records to track creation and modification times, so that I can audit when changes were made.

#### Acceptance Criteria

1. WHEN a new member is created, THE System SHALL set created_at to the current timestamp
2. WHEN a new member is created, THE System SHALL set updated_at to the current timestamp
3. WHEN a member record is updated, THE System SHALL automatically update the updated_at timestamp via database trigger
4. THE System SHALL use CURRENT_TIMESTAMP for all timestamp values
5. THE System SHALL maintain timestamp accuracy to the second
