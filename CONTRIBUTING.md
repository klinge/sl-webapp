# Contributing to SL Member System

## Development Setup

### Prerequisites
- PHP 8.1+ (PHP 8.2+ for testing)
- Composer
- SQLite

### Installation
```bash
git clone <repository>
cd sl-webapp
composer install
cp .env-EDITME .env
# Edit .env with your configuration
```

### Database Setup
```bash
sqlite3 db/sldb.sqlite < db/createTables.sql
sqlite3 db/sldb.sqlite < db/seedData.sql
```

## Coding Standards

### PHP Standards
- Use `declare(strict_types=1)` in all files
- Follow PSR-4 autoloading
- Use type hints everywhere
- Implement proper error handling

### Architecture Patterns
- **Controllers**: Handle HTTP requests, delegate to services
- **Models**: Data access and business logic
- **Services**: Business logic and external integrations
- **Repositories**: Data access abstraction

### Dependency Injection
- Use constructor injection
- Register services in `ContainerConfigurator`
- Avoid service locator pattern

### Testing
```bash
# Run tests
php vendor/bin/phpunit

# Code quality
php vendor/bin/phpstan analyse
php vendor/bin/phpcs
```

### Git Workflow
1. Create feature branch
2. Write tests first
3. Implement feature
4. Run quality checks
5. Submit PR