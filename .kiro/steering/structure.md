# Project Structure

## Architecture Pattern

The application follows a layered MVC-style architecture with service layer:

- **Controllers**: Handle HTTP requests/responses, coordinate between services and views
- **Services**: Contain business logic and orchestrate operations
- **Models**: Represent domain entities and data access (repositories)
- **Views**: PHP templates for rendering HTML
- **Middleware**: PSR-15 compliant request/response processing pipeline

## Directory Organization

```
App/
├── Application.php              # Main application bootstrap
├── Config/                      # Application configuration
│   ├── ContainerConfigurator.php   # DI container setup
│   └── RouteConfig.php             # Route definitions
├── Controllers/                 # Request handlers
│   ├── Auth/                       # Authentication controllers
│   ├── BaseController.php          # Base controller with common functionality
│   └── [Feature]Controller.php     # Feature-specific controllers
├── Middleware/                  # PSR-15 middleware
│   ├── Contracts/                  # Middleware interfaces
│   └── [Middleware].php            # Specific middleware implementations
├── Models/                      # Domain models and repositories
│   ├── [Entity].php                # Domain entity classes
│   └── [Entity]Repository.php      # Data access layer
├── Services/                    # Business logic layer
│   ├── Auth/                       # Authentication services
│   ├── Github/                     # GitHub integration services
│   └── [Feature]Service.php        # Feature-specific services
├── ServiceProviders/            # DI service registration
├── Traits/                      # Reusable traits
└── Utils/                       # Utility classes

public/
├── index.php                    # Application entry point
├── assets/                      # Static assets (CSS, JS, images)
└── views/                       # PHP view templates
    ├── _layouts/                   # Layout templates
    ├── emails/                     # Email templates
    ├── modals/                     # Modal components
    └── [feature]/                  # Feature-specific views

tests/
├── Unit/                        # Unit tests (mirror App/ structure)
├── Integration/                 # Integration tests
└── fixtures/                    # Test fixtures

db/
├── createTables.sql             # Database schema
├── seedData.sql                 # Seed data
├── queries/                     # Useful SQL queries
└── *.sqlite                     # SQLite database files
```

## Naming Conventions

- **Namespaces**: PSR-4 autoloading with `App\` namespace
- **Classes**: PascalCase (e.g., `MedlemController`, `BetalningService`)
- **Methods**: camelCase (e.g., `listAll()`, `createBetalning()`)
- **Files**: Match class names exactly
- **Routes**: Named routes using kebab-case (e.g., `medlem-list`, `betalning-create`)
- **Database tables**: Swedish names (Medlem, Betalning, Segling, Roll)

## Key Conventions

- All new code uses namespaces
- Type hints required everywhere
- `declare(strict_types=1)` at the top of every file
- Controllers extend `BaseController`
- Services return result objects (e.g., `MedlemServiceResult`)
- Repositories handle all database operations
- Middleware registered in `Application` class or route groups
- Routes centralized in `RouteConfig::createAppRoutes()`
- DI container configured in `ContainerConfigurator`
- Service providers register related services in the container

## Request Flow

1. Request hits `public/index.php`
2. `Application` bootstraps (loads env, config, sets up DI, router, middleware)
3. Request passes through middleware stack (CSRF, auth, etc.)
4. Router dispatches to appropriate controller method
5. Controller uses services to perform business logic
6. Services use repositories to access data
7. Controller returns PSR-7 response (view or JSON)
8. Response emitted to client

## Testing Structure

- Tests mirror the `App/` directory structure
- Unit tests in `tests/Unit/` test individual classes in isolation
- Integration tests in `tests/Integration/` test component interactions
- Fixtures in `tests/fixtures/` for test data
