# Implementation Plan: Sailing Activity Management System

- [ ] 1. Create Segling model and repository
  - Implement Segling entity class with properties (id, startdatum, slutdatum, skeppslag, kommentar, timestamps)
  - Implement SeglingRepository with CRUD methods (findAll, findById, create, update, delete)
  - Implement crew management methods (getCrewMembers, addCrewMember, removeCrewMember)
  - Implement getMembersByRole method to filter members by role
  - Add database connection handling and parameterized queries
  - _Requirements: 1.1, 2.1, 3.6, 4.4, 5.2, 8.1, 8.2, 8.3, 10.1, 10.2, 11.1_

- [ ] 2. Implement SeglingService business logic
  - Create SeglingServiceResult class for standardized responses
  - Implement getAllSeglingar() method with date ordering
  - Implement getSeglingEditData() to fetch sailing with crew roster and available members
  - Implement createSegling() with data validation (required fields, date range)
  - Implement updateSegling() with data validation
  - Implement deleteSegling() method
  - Implement addMemberToSegling() for crew assignments
  - Implement removeMemberFromSegling() for crew removal
  - _Requirements: 1.1, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.2, 3.3, 3.4, 3.5, 4.2, 4.3, 5.1, 6.1, 6.2, 6.3, 7.2, 8.4, 8.5, 8.6, 8.7_

- [ ] 3. Build SeglingController request handlers
  - Implement list() to display sailing list view
  - Implement edit() to display sailing edit form with crew roster
  - Implement save() to process sailing updates
  - Implement delete() to remove sailings
  - Implement showCreate() to display new sailing form
  - Implement create() to process new sailing creation with redirect to edit page
  - Implement saveMedlem() to handle AJAX crew member addition
  - Implement deleteMedlemFromSegling() to handle AJAX crew member removal with JSON/form-encoded support
  - Add error handling with try-catch blocks
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.7, 2.8, 3.1, 3.7, 3.8, 4.1, 4.6, 4.7, 5.1, 5.4, 5.5, 6.6, 6.7, 7.1, 7.4, 7.5_

- [ ] 4. Configure routing and middleware
  - Add sailing management routes to RouteConfig
  - Apply RequireAdminMiddleware to all sailing routes
  - Configure route names (segling-list, segling-edit, segling-create, etc.)
  - Set up route groups for /segling paths
  - Configure POST routes for AJAX operations (saveMedlem, deleteMedlemFromSegling)
  - _Requirements: 1.3, 1.4, 9.1, 9.2, 9.3, 9.4_

- [ ] 5. Create view templates
  - Create viewSegling.php for sailing list display
  - Create viewSeglingEdit.php for sailing editing with crew roster management
  - Create viewSeglingNew.php for new sailing creation form
  - Add form fields for startdatum, slutdatum, skeppslag, kommentar
  - Add crew roster table with remove buttons (AJAX)
  - Add crew member assignment interface with role-filtered member dropdowns
  - Add JavaScript for AJAX crew operations
  - Add CSRF token fields to forms
  - _Requirements: 1.2, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 6.6, 7.4_

- [ ] 6. Implement crew assignment functionality
  - Create Segling_Medlem_Roll table with foreign keys
  - Implement addCrewMember method in repository
  - Implement removeCrewMember method in repository
  - Configure ON DELETE CASCADE for segling_id
  - Configure ON DELETE SET NULL for medlem_id and roll_id
  - Add created_at and updated_at timestamps to crew assignments
  - _Requirements: 5.3, 6.4, 6.5, 7.3, 10.4, 11.2, 11.3, 11.4, 11.5, 11.6_

- [ ] 7. Set up database schema and triggers
  - Create Segling table with all columns and constraints
  - Create Segling_Medlem_Roll junction table with foreign keys
  - Add NOT NULL constraints on startdatum, slutdatum, skeppslag
  - Create segling_after_update trigger for automatic updated_at
  - Create indexes on segling_id, medlem_id, and roll_id in Segling_Medlem_Roll
  - Configure foreign key constraints (CASCADE for segling_id, SET NULL for medlem_id and roll_id)
  - _Requirements: 8.1, 8.2, 8.3, 10.3, 11.7_

- [ ] 8. Register services in DI container
  - Register SeglingRepository in ContainerConfigurator
  - Register SeglingService with repository dependency
  - Register SeglingController with service, view, and URL generator dependencies
  - Configure RequireAdminMiddleware in container
  - _Requirements: 9.1_

- [ ]* 9. Write unit tests for sailing management
  - Write unit tests for SeglingService business logic
  - Write unit tests for Segling model methods
  - Write unit tests for data validation (required fields, date ranges)
  - Mock repository dependencies in service tests
  - Test result object creation and error handling
  - Test crew assignment logic
  - _Requirements: All_

- [ ]* 10. Write integration tests
  - Write integration tests for SeglingController endpoints
  - Test full request/response cycle with middleware
  - Test database operations with test database
  - Test foreign key constraints (CASCADE and SET NULL behavior)
  - Test trigger functionality for updated_at
  - Test many-to-many relationship integrity
  - Test AJAX operations with both JSON and form-encoded requests
  - Test index performance for crew queries
  - _Requirements: All_
