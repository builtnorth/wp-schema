<?php

namespace BuiltNorth\Schema;

/**
 * Main App class for WP Schema package
 * 
 * Handles initialization and provides easy access to schema functionality
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
     * Schema generator instance
     *
     * @var SchemaGenerator|null
     */
    private $schema_generator = null;

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
        // Initialize the schema generator with default integrations
        SchemaGenerator::init();
        
        $this->schema_generator = new SchemaGenerator();
        
        // Add WordPress hooks
        add_action('wp_head', [$this, 'output_schema']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Add admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_admin']);
    }

    /**
     * Get schema generator
     *
     * @return SchemaGenerator
     */
    public function get_schema_generator()
    {
        return $this->schema_generator;
    }

    /**
     * Generate schema for current page
     *
     * @param string $type Schema type
     * @param array $options Generation options
     * @return string JSON-LD schema markup
     */
    public function generate_schema($type = 'auto', $options = [])
    {
        if ($type === 'auto') {
            $type = $this->detect_schema_type();
        }

        $content = $this->get_current_content();
        return SchemaGenerator::render($content, $type, $options);
    }

    /**
     * Auto-detect schema type for current page
     *
     * @return string Schema type
     */
    private function detect_schema_type()
    {
        if (is_front_page()) {
            return 'website';
        }

        if (is_single() || is_page()) {
            $post_type = get_post_type();
            
            switch ($post_type) {
                case 'post':
                    return 'article';
                case 'product':
                    return 'product';
                case 'faq':
                    return 'faq';
                case 'organization':
                    return 'organization';
                case 'person':
                    return 'person';
                default:
                    return 'article';
            }
        }

        if (is_archive()) {
            return 'website';
        }

        return 'website';
    }

    /**
     * Get current page content
     *
     * @return mixed Content for schema generation
     */
    private function get_current_content()
    {
        if (is_single() || is_page()) {
            return get_the_ID();
        }

        if (is_front_page()) {
            return [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => get_site_url(),
                'search_enabled' => true
            ];
        }

        return '';
    }

    /**
     * Output schema in wp_head
     *
     * @return void
     */
    public function output_schema()
    {
        $schema = $this->generate_schema();
        
        if (!empty($schema)) {
            SchemaGenerator::output_schema_script($schema);
        }
    }

    /**
     * Enqueue assets
     *
     * @return void
     */
    public function enqueue_assets()
    {
        // Add any CSS/JS assets if needed
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
     * Generate schema for specific data
     *
     * @param mixed $content Content to generate schema from
     * @param string $type Schema type
     * @param array $options Generation options
     * @return string JSON-LD schema markup
     */
    public static function generate($content, $type = 'auto', $options = [])
    {
        return self::instance()->generate_schema($type, $options);
    }

    /**
     * Quick organization schema
     *
     * @param array $data Organization data
     * @return string JSON-LD schema markup
     */
    public static function organization($data)
    {
        return SchemaGenerator::render($data, 'organization');
    }

    /**
     * Quick local business schema
     *
     * @param array $data Business data
     * @return string JSON-LD schema markup
     */
    public static function local_business($data)
    {
        return SchemaGenerator::render($data, 'local_business');
    }

    /**
     * Quick website schema
     *
     * @param array $data Website data
     * @return string JSON-LD schema markup
     */
    public static function website($data)
    {
        return SchemaGenerator::render($data, 'website');
    }

    /**
     * Quick article schema
     *
     * @param mixed $content Article content
     * @return string JSON-LD schema markup
     */
    public static function article($content)
    {
        return SchemaGenerator::render($content, 'article');
    }

    /**
     * Quick FAQ schema
     *
     * @param mixed $content FAQ content
     * @return string JSON-LD schema markup
     */
    public static function faq($content)
    {
        return SchemaGenerator::render($content, 'faq');
    }

    /**
     * Quick product schema
     *
     * @param array $data Product data
     * @return string JSON-LD schema markup
     */
    public static function product($data)
    {
        return SchemaGenerator::render($data, 'product');
    }

    /**
     * Quick person schema
     *
     * @param array $data Person data
     * @return string JSON-LD schema markup
     */
    public static function person($data)
    {
        return SchemaGenerator::render($data, 'person');
    }
} 