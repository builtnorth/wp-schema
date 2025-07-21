# WP Schema Framework Tests

This directory contains the test suite for the WP Schema Framework package.

## Running Tests

First, install dependencies:
```bash
composer install
```

Then run the tests:
```bash
# Run all tests
composer test

# Run tests with coverage report
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/AppTest.php

# Run specific test method
./vendor/bin/phpunit --filter testSingletonInstance
```

## Test Structure

- `AppTest.php` - Tests for the main App class
- `Graph/` - Tests for schema graph components
  - `SchemaPieceTest.php` - Tests for individual schema pieces
- `Providers/` - Tests for schema providers
  - `OrganizationProviderTest.php` - Example provider test

## Writing Tests

1. Create test files with the suffix `Test.php`
2. Extend `PHPUnit\Framework\TestCase`
3. Follow the existing test patterns
4. Mock WordPress functions as needed in `bootstrap.php`

## Coverage

Generate a coverage report:
```bash
composer test:coverage
```

View the report by opening `coverage/index.html` in your browser.