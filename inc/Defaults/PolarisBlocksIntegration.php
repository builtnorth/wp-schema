<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\API\WP_Schema;

// Include the provider class
require_once __DIR__ . '/PolarisBlocksBreadcrumbProvider.php';

/**
 * Polaris Blocks Integration
 * 
 * Provides schema data generation for Polaris framework blocks using the data provider pattern.
 * Clean implementation that avoids recursion issues by using focused data providers.
 * 
 * @since 2.0.0
 */
class PolarisBlocksIntegration
{
    /**
     * Initialize the integration by registering data providers
     */
    public static function init(): void
    {
        if (!self::isAvailable()) {
            return;
        }
        
        // Register data provider for breadcrumb blocks
        WP_Schema::registerProvider(new PolarisBlocksBreadcrumbProvider());
    }
    
    /**
     * Check if integration is available
     */
    public static function isAvailable(): bool
    {
        return class_exists('PolarisBlocks\\App') || function_exists('polaris_blocks_init');
    }
    
    /**
     * Get integration description
     */
    public static function getDescription(): string
    {
        return 'Provides breadcrumb schema from Polaris Blocks using data provider pattern.';
    }
}