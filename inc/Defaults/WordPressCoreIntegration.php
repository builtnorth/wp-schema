<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Defaults;

use BuiltNorth\Schema\API\WP_Schema;

// Include the provider classes
require_once __DIR__ . '/WordPressCoreProviders.php';

/**
 * WordPress Core Integration
 * 
 * Clean integration using multiple focused data providers instead of monolithic class.
 * Registers separate providers for WebSite, WebPage, Article, and Navigation schemas.
 * 
 * @since 2.0.0
 */
class WordPressCoreIntegration
{
    /**
     * Initialize the integration by registering multiple focused providers
     */
    public static function init(): void
    {
        // Register focused providers for each schema type
        WP_Schema::registerProvider(new WebSiteProvider());
        WP_Schema::registerProvider(new WebPageProvider());
        WP_Schema::registerProvider(new ArticleProvider());
        WP_Schema::registerProvider(new NavigationProvider());
    }
    
    /**
     * Check if integration is available
     */
    public static function isAvailable(): bool
    {
        return true; // Always available since it uses WordPress core
    }
    
    /**
     * Get integration description
     */
    public static function getDescription(): string
    {
        return 'Provides basic schema generation using WordPress core functionality (WebSite, WebPage, Navigation).';
    }
}