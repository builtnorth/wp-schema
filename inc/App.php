<?php

declare(strict_types=1);

namespace BuiltNorth\Schema;

use BuiltNorth\Schema\Core\SchemaService;
use BuiltNorth\Schema\Core\OutputService;

/**
 * Main App class for WP Schema package
 * 
 * Updated to use the new simplified architecture.
 * Provides both new architecture and legacy compatibility methods.
 * 
 * @since 3.0.0
 */
class App
{
    /**
     * Singleton instance
     *
     * @var App|null
     */
    private static $instance = null;

    /**
     * Schema service instance
     *
     * @var SchemaService
     */
    private SchemaService $schemaService;

    /**
     * Output service instance
     *
     * @var OutputService
     */
    private OutputService $outputService;

    /**
     * Initialization flag
     *
     * @var bool
     */
    private bool $initialized = false;

    /**
     * Get singleton instance
     *
     * @return App
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Initialize the application
     *
     * @return void
     */
    private function init()
    {
        if ($this->initialized) {
            return;
        }

        // Initialize services
        $this->schemaService = new SchemaService();
        $this->outputService = new OutputService();
        
        // Initialize output service
        $this->outputService->init();
        
        // Add admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_admin']);
        
        // Plugin is ready
        do_action('wp_schema_ready');
        
        $this->initialized = true;
    }

    /**
     * Get schema service
     *
     * @return SchemaService
     */
    public function get_schema_service(): SchemaService
    {
        return $this->schemaService;
    }

    /**
     * Get output service
     *
     * @return OutputService
     */
    public function get_output_service(): OutputService
    {
        return $this->outputService;
    }

    /**
     * Generate schema for current page
     *
     * @param array $options Generation options
     * @return array Schema pieces
     */
    public function generate_schema(array $options = []): array
    {
        return $this->schemaService->render_for_context(null, $options);
    }

    /**
     * Check if app is initialized
     *
     * @return bool
     */
    public function is_initialized(): bool
    {
        return $this->initialized;
    }


    /**
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu()
    {
        add_options_page(
            'WP Schema',
            'WP Schema',
            'manage_options',
            'wp-schema',
            [$this, 'admin_page']
        );
    }

    /**
     * Initialize admin
     *
     * @return void
     */
    public function init_admin()
    {
        // Admin initialization
    }

    /**
     * Admin page callback
     *
     * @return void
     */
    public function admin_page()
    {
        include __DIR__ . '/admin/page.php';
    }

    /**
     * Generate schema for current context (static helper)
     *
     * @param array $options Generation options
     * @return array Schema pieces
     */
    public static function generate(array $options = []): array
    {
        return self::instance()->generate_schema($options);
    }

    /**
     * Get schema service (static helper)
     *
     * @return SchemaService
     */
    public static function schema_service(): SchemaService
    {
        return self::instance()->get_schema_service();
    }

    /**
     * Get output service (static helper)
     *
     * @return OutputService
     */
    public static function output_service(): OutputService
    {
        return self::instance()->get_output_service();
    }
} 