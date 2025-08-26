<?php

declare(strict_types=1);

/**
 * Test Conflict Detection
 * 
 * This file tests that schema providers properly detect and avoid conflicts
 * with existing plugin schema outputs.
 */

namespace BuiltNorth\WPSchema\Tests;

use BuiltNorth\WPSchema\Providers\ProductProvider;
use BuiltNorth\WPSchema\Providers\EventProvider;

class ConflictDetectionTest
{
    public function test_woocommerce_conflict_detection(): void
    {
        $provider = new ProductProvider();
        
        // Test 1: WooCommerce class exists and product post type
        add_filter('wp_schema_framework_test_mode', '__return_true');
        $_POST['post_type'] = 'product';
        
        // Simulate WooCommerce structured data being enabled
        add_filter('wp_schema_framework_woocommerce_schema_active', '__return_true');
        
        $can_provide = $provider->can_provide('singular');
        assert($can_provide === false, 'Should not provide when WooCommerce schema is active');
        
        // Simulate WooCommerce structured data being disabled
        add_filter('wp_schema_framework_woocommerce_schema_active', '__return_false');
        
        $can_provide = $provider->can_provide('singular');
        assert($can_provide === true, 'Should provide when WooCommerce schema is disabled');
        
        echo "✓ WooCommerce conflict detection working\n";
    }
    
    public function test_tribe_events_conflict_detection(): void
    {
        $provider = new EventProvider();
        
        // Test with The Events Calendar
        add_filter('wp_schema_framework_test_mode', '__return_true');
        $_POST['post_type'] = 'tribe_events';
        
        // Simulate Tribe Events schema being enabled
        add_filter('wp_schema_framework_tribe_events_schema_active', '__return_true');
        
        $can_provide = $provider->can_provide('singular');
        assert($can_provide === false, 'Should not provide when Tribe Events schema is active');
        
        // Simulate Tribe Events schema being disabled
        add_filter('wp_schema_framework_tribe_events_schema_active', '__return_false');
        
        $can_provide = $provider->can_provide('singular');
        assert($can_provide === true, 'Should provide when Tribe Events schema is disabled');
        
        echo "✓ Tribe Events conflict detection working\n";
    }
    
    public function test_custom_filter_override(): void
    {
        $product_provider = new ProductProvider();
        
        // Test that custom filter can force provision even when plugin schema exists
        add_filter('wp_schema_framework_is_product', function($is_product) {
            return true;
        });
        
        $can_provide = $product_provider->can_provide('singular');
        assert($can_provide === true, 'Custom filter should override detection');
        
        echo "✓ Custom filter override working\n";
    }
    
    public function run_all_tests(): void
    {
        echo "\n=== Running Conflict Detection Tests ===\n\n";
        
        try {
            $this->test_woocommerce_conflict_detection();
            $this->test_tribe_events_conflict_detection();
            $this->test_custom_filter_override();
            
            echo "\n✅ All conflict detection tests passed!\n\n";
        } catch (\Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n\n";
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $tester = new ConflictDetectionTest();
    $tester->run_all_tests();
}