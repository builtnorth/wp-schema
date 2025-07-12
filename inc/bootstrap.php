<?php

declare(strict_types=1);

/**
 * Bootstrap the new enterprise architecture
 * 
 * This file loads all the necessary files for the new schema system
 * and must be loaded before any integrations are initialized.
 */

// Base path for includes
$base_path = __DIR__;

// Load contracts first (ValidationResult is included in SchemaValidatorInterface.php)
require_once $base_path . '/Contracts/SchemaModelInterface.php';
require_once $base_path . '/Contracts/DataProviderInterface.php';
require_once $base_path . '/Contracts/SchemaManagerInterface.php';
require_once $base_path . '/Contracts/CacheInterface.php';
require_once $base_path . '/Contracts/SchemaValidatorInterface.php'; // Contains ValidationResult class
require_once $base_path . '/Contracts/HookManagerInterface.php';
require_once $base_path . '/Contracts/SchemaRegistryInterface.php';

// Load core services
require_once $base_path . '/Core/Container.php';
require_once $base_path . '/Core/SchemaRegistry.php';
require_once $base_path . '/Core/DataProviderManager.php';
require_once $base_path . '/Core/SchemaManager.php';
require_once $base_path . '/Core/HookManager.php';

// Load cache implementation
require_once $base_path . '/Cache/SchemaCache.php';

// Load validation
require_once $base_path . '/Validation/SchemaValidator.php';

// Load models
require_once $base_path . '/Models/Address.php';
require_once $base_path . '/Models/Organization.php';
require_once $base_path . '/Models/ImageObject.php';
require_once $base_path . '/Models/GeoCoordinates.php';

// Load API
require_once $base_path . '/API/PluginAPI.php';
require_once $base_path . '/API/WP_Schema.php';

// Initialize the container - this will register all services
\BuiltNorth\Schema\Core\Container::getInstance();