# Implementation Plan: Member Management System

- [ ] 1. Create Medlem model and repository
  - Implement Medlem entity class with all properties (id, fornamn, efternamn, email, etc.)
  - Implement MedlemRepository with CRUD methods (findAll, findById, create, update, delete)
  - Implement relationship methods (getMemberRoles, assignRole, removeRole)
  - Add database connection handling and parameterized queries
  - _Requirements: 1.1, 2.1, 3.5, 4.4, 5.2, 6.1, 6.2, 7.3, 9.1, 9.2_

- [ ] 2. Implement MedlemService business logic
  - Create MedlemServiceResult class for standardized responses
  - Implement getAllMembers() method
  - Implement getMemberEditData() to fetch member with related data (roles, payments, sailings)
  - Implement createMember() with data validation and sanitization
  - Implement updateMember() with data validation and sanitization
  - Implement deleteMember() method
  - Implement getAllRoles() helper method
  - _Requirements: 1.1, 2.1, 2.2, 2.3, 2.4, 3.1, 3.3, 3.4, 3.5, 4.2, 4.3, 4.4, 5.1, 5.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8_

- [ ] 3. Build MedlemController request handlers
  - Implement listAll() to display member list view
  - Implement listJson() to return members as JSON
  - Implement edit() to display member edit form with related data
  - Implement update() to process member updates
  - Implement showNewForm() to display new member form
  - Implement create() to process new member creation
  - Implement delete() to remove members
  - Add error handling with try-catch blocks
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.5, 2.6, 3.2, 3.6, 3.7, 3.8, 4.1, 4.7, 4.8, 5.1, 5.5, 5.6_

- [ ] 4. Configure routing and middleware
  - Add member management routes to RouteConfig
  - Apply RequireAdminMiddleware to all member routes
  - Configure route names (medlem-list, medlem-edit, medlem-create, etc.)
  - Set up route groups for /medlem paths
  - _Requirements: 1.3, 1.4, 8.1, 8.2, 8.3, 8.4_

- [ ] 5. Create view templates
  - Create viewMedlem.php for member list display
  - Create viewMedlemEdit.php for member editing with roles, payments, and sailings
  - Create viewMedlemNew.php for new member creation form
  - Add form fields for all member attributes
  - Add role assignment interface
  - Add CSRF token fields to forms
  - _Requirements: 1.2, 2.2, 2.3, 2.4, 2.6, 3.2, 7.1, 7.2_

- [ ] 6. Implement role management functionality
  - Add methods to assign roles to members in repository
  - Add methods to remove roles from members in repository
  - Enforce unique constraint on medlem_id and roll_id combinations
  - Handle Medlem_Roll record creation and deletion
  - Display assigned and available roles in edit view
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [ ] 7. Set up database schema and triggers
  - Create Medlem table with all columns and constraints
  - Create Medlem_Roll junction table with foreign keys
  - Add unique constraint on email field
  - Add NOT NULL constraint on efternamn field
  - Create medlem_after_update trigger for automatic updated_at
  - Configure foreign key constraints (CASCADE and SET NULL)
  - _Requirements: 5.3, 5.4, 6.1, 6.2, 9.3, 9.4_

- [ ] 8. Register services in DI container
  - Register MedlemRepository in ContainerConfigurator
  - Register MedlemService with repository dependency
  - Register MedlemController with service, view, and URL generator dependencies
  - Configure RequireAdminMiddleware in container
  - _Requirements: 8.1_

- [ ]* 9. Write unit tests for member management
  - Write unit tests for MedlemService business logic
  - Write unit tests for Medlem model methods
  - Write unit tests for data validation
  - Mock repository dependencies in service tests
  - Test result object creation and error handling
  - _Requirements: All_

- [ ]* 10. Write integration tests
  - Write integration tests for MedlemController endpoints
  - Test full request/response cycle with middleware
  - Test database operations with test database
  - Test foreign key constraints and cascade behavior
  - Test trigger functionality for updated_at
  - Test unique constraint on email
  - _Requirements: All_
