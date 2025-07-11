# Schema Package Separation

## Overview

The schema generation functionality has been separated from `wp-utility` into its own dedicated package `wp-schema`. This provides better organization, reusability, and maintainability.

## Why Separate?

### 1. **Focused Responsibility**

- `wp-utility`: General utility functions and helpers
- `wp-schema`: Dedicated schema generation and SEO optimization

### 2. **Better Organization**

- Clear separation of concerns
- Easier to find and maintain schema-related code
- Dedicated documentation and examples

### 3. **Reusability**

- Can be used independently in other projects
- Other packages can depend on it without pulling in utility functions
- Cleaner dependency management

### 4. **Enhanced Features**

- Can add schema-specific utilities
- Better testing and validation
- More comprehensive documentation

## Package Structure

### wp-schema Package

```
composer_packages/wp-schema/
├── composer.json
├── README.md
├── inc/
│   ├── App.php                    # Main application class
│   ├── SchemaGenerator.php        # Core schema generator
│   ├── Extractors/               # Content extraction logic
│   ├── Generators/               # Schema generation logic
│   ├── Detectors/                # Auto-detection logic
│   └── Utilities/                # Schema-specific utilities
└── tests/                        # Test suite
```

### wp-utility Package (Updated)

```
composer_packages/wp-utility/
├── composer.json                 # Now includes wp-schema dependency
├── inc/
│   ├── Utilities/
│   │   └── SchemaGenerator.php  # Thin wrapper to wp-schema
│   └── ...                      # Other utility functions
```

## Migration Process

### 1. **Created wp-schema Package**

- New composer.json with proper autoloading
- Comprehensive README with examples
- Main App.php class for initialization

### 2. **Updated wp-utility**

- Modified to use wp-schema as dependency
- SchemaGenerator.php now acts as a thin wrapper
- Maintains backward compatibility

### 3. **Namespace Updates**

- `BuiltNorth\Utility\Utilities\SchemaGenerator` → `BuiltNorth\Schema`
- All internal classes updated to new namespace
- Use statements updated throughout

## Usage Examples

### Before (wp-utility only)

```php
use BuiltNorth\Utility\Utilities\SchemaGenerator;

$schema = SchemaGenerator::render($data, 'organization');
```

### After (wp-schema package)

```php
// Option 1: Use wp-schema directly
use BuiltNorth\Schema\SchemaGenerator;

$schema = SchemaGenerator::render($data, 'organization');

// Option 2: Use wp-utility wrapper (backward compatible)
use BuiltNorth\Utility\Utilities\SchemaGenerator;

$schema = SchemaGenerator::render($data, 'organization');

// Option 3: Use the App class for WordPress integration
use BuiltNorth\Schema\App;

$schema = App::organization($data);
```

## Benefits

### 1. **Modularity**

- Schema functionality is now a standalone package
- Can be used in any WordPress project
- Easy to version and maintain

### 2. **Enhanced Features**

- Dedicated schema utilities
- Better logo handling
- Comprehensive business schemas
- WordPress integration helpers

### 3. **Better Documentation**

- Dedicated README with examples
- Clear usage instructions
- Comprehensive feature documentation

### 4. **Testing**

- Dedicated test suite for schema functionality
- Better coverage and validation
- Easier to maintain quality

## Backward Compatibility

The separation maintains full backward compatibility:

```php
// This still works exactly the same
use BuiltNorth\Utility\Utilities\SchemaGenerator;

$schema = SchemaGenerator::render($data, 'organization');
SchemaGenerator::output_schema_script($schema);
```

## Future Enhancements

### 1. **Schema-Specific Features**

- Logo processing utilities
- Contact information validation
- Address formatting helpers
- Social media integration

### 2. **WordPress Integration**

- Custom post type support
- Meta field integration
- Block editor support
- Theme customizer integration

### 3. **Advanced Features**

- Schema validation
- Performance optimization
- Caching mechanisms
- Debug tools

## Installation

### For New Projects

```bash
composer require builtnorth/wp-schema
```

### For Existing Projects

```bash
composer require builtnorth/wp-utility
# wp-schema will be installed automatically as a dependency
```

## Dependencies

### wp-schema

- PHP 8.1+
- WordPress 6.0+

### wp-utility

- PHP 8.1+
- WordPress 6.0+
- wp-schema (^1.0)

## Migration Script

A migration script is provided to help move existing code:

```bash
cd composer_packages/wp-schema
php migrate.php
```

This script:

1. Copies schema files from wp-utility
2. Updates namespaces
3. Creates proper directory structure
4. Maintains all functionality

## Conclusion

The separation of schema functionality into its own package provides:

- **Better organization** and maintainability
- **Enhanced features** and capabilities
- **Improved reusability** across projects
- **Backward compatibility** for existing code
- **Future extensibility** for new features

This follows the same pattern as other composer packages in the ecosystem and provides a solid foundation for schema generation in WordPress projects.
