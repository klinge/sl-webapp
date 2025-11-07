# Technology Stack

## Language & Runtime

- PHP 8.1+ (production)
- PHP 8.2+ (required for testing)
- Strict types enabled (`declare(strict_types=1)`) in all files

## Core Framework & Libraries

- **Routing**: League Route (v6.2)
- **HTTP**: PSR-7 implementation via Laminas Diactoros (v3.5)
- **Dependency Injection**: League Container (v5.1)
- **Middleware**: PSR-15 compliant middleware stack
- **Logging**: Monolog (v3.7)
- **Email**: PHPMailer (v7.0)
- **HTTP Client**: Guzzle (v7.0)
- **Environment**: vlucas/phpdotenv
- **Turnstile**: andkab/php-turnstile (v1.0) for CAPTCHA

## Development Tools

- **Testing**: PHPUnit (v11)
- **Static Analysis**: PHPStan (level 6, PHP 8.1 target)
- **Code Style**: PHP_CodeSniffer (v4.0)
- **Code Quality**: SonarCloud integration
- **Coverage**: Coveralls integration

## Database

- SQLite (sldb.sqlite for dev, sldb-prod.sqlite for prod)

## Common Commands

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run unit tests only
composer test-unit

# Run integration tests only
composer test-integration

# Generate coverage report (outputs to tests/coverage/)
composer test-coverage

# Run static analysis
vendor/bin/phpstan analyse

# Run code style checks
vendor/bin/phpcs
```

## Configuration

- Environment variables stored in `.env` file (use `.env-EDITME` as template)
- Configuration loaded via Application class
- Session management with secure cookie settings
- CSRF protection enabled globally
