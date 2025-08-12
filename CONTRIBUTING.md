# Contributing to WP Schema

Thank you for your interest in contributing to WP Schema!

## How to Contribute

1. **Fork the repository** and create your branch from `dev`.
2. **Make your changes** and ensure they follow WordPress coding standards.
3. **Test your changes** thoroughly.
4. **Submit a pull request** with a clear description of your changes.

## Development Setup

```bash
# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer lint

# Fix code style issues
composer lint:fix
```

## Coding Standards

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PHP 8.1+ features appropriately
- Write clear, self-documenting code
- Add PHPDoc comments for all functions and classes

## Testing

- Write tests for new features
- Ensure all existing tests pass
- Run `composer test` before submitting

## Pull Request Guidelines

- Keep changes focused and atomic
- Write clear commit messages
- Update documentation if needed
- Ensure CI checks pass

## Questions?

Open an issue on GitHub if you have questions or need help.