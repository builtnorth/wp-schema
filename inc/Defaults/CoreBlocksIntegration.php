<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\API\WP_Schema;

// Include the provider class
require_once __DIR__ . '/CoreBlocksNavigationProvider.php';

/**
 * Core Blocks Integration
 * 
 * Provides schema data generation for WordPress core blocks using the data provider pattern.
 * Clean implementation that avoids recursion issues by using focused data providers.
 * 
 * @since 2.0.0
 */
class CoreBlocksIntegration
{
    /**
     * Initialize the integration by registering data providers
     */
    public static function init(): void
    {
        // Register data provider for navigation blocks
        WP_Schema::registerProvider(new CoreBlocksNavigationProvider());
    }
    
    /**
     * Check if integration is available
     */
    public static function isAvailable(): bool
    {
        return true; // Always available since core blocks are always present
    }
    
    /**
     * Get integration description
     */
    public static function getDescription(): string
    {
        return 'Provides navigation schema from WordPress core blocks using data provider pattern.';
    }
} 