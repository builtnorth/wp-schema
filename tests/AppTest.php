<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests;

use BuiltNorth\WPSchema\App;
use WP_Mock;
use Mockery;

/**
 * Test the main App class
 */
class AppTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Reset singleton instance between tests
        $reflection = new \ReflectionClass(App::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testSingletonInstance(): void
    {
        $instance1 = App::instance();
        $instance2 = App::instance();
        
        $this->assertSame($instance1, $instance2, 'App should return the same instance');
    }

    public function testInitialization(): void
    {
        // Mock WordPress hooks that are called during initialization
        WP_Mock::userFunction('add_action')->andReturn(true);
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        
        $app = App::initialize();
        
        $this->assertInstanceOf(App::class, $app);
        $this->assertTrue($app->is_initialized());
    }

    public function testGetRegistry(): void
    {
        // Mock the WordPress functions called during initialization
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('add_action')->andReturn(true);
        
        $app = App::initialize();
        $registry = $app->get_registry();
        
        $this->assertInstanceOf('BuiltNorth\WPSchema\Services\ProviderRegistry', $registry);
    }

    public function testGetGraphBuilder(): void
    {
        // Mock the WordPress functions called during initialization
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('add_action')->andReturn(true);
        
        $app = App::initialize();
        $graph_builder = $app->get_graph_builder();
        
        $this->assertInstanceOf('BuiltNorth\WPSchema\Services\GraphBuilder', $graph_builder);
    }

    public function testGetTypeRegistry(): void
    {
        // Mock the WordPress functions called during initialization
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('add_action')->andReturn(true);
        
        $app = App::initialize();
        $type_registry = $app->get_type_registry();
        
        $this->assertInstanceOf('BuiltNorth\WPSchema\Services\SchemaTypeRegistry', $type_registry);
    }

    public function testRegisterProvider(): void
    {
        // Mock the WordPress functions called during initialization
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('add_action')->andReturn(true);
        
        $app = App::initialize();
        
        // Create a mock provider class
        $mockProviderClass = 'MockProvider_' . uniqid();
        eval("
            class {$mockProviderClass} implements \\BuiltNorth\\WPSchema\\Contracts\\SchemaProviderInterface {
                public function can_provide(string \$context): bool { return true; }
                public function get_pieces(string \$context): array { return []; }
                public function get_priority(): int { return 10; }
            }
        ");
        
        $result = App::register_provider('test_provider', $mockProviderClass);
        
        $this->assertTrue($result, 'Provider registration should succeed');
    }

    public function testRegisterProviderWithInvalidClass(): void
    {
        // Mock the WordPress functions called during initialization
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('add_action')->andReturn(true);
        
        App::initialize();
        
        $result = App::register_provider('test_provider', 'NonExistentClass');
        
        $this->assertFalse($result, 'Provider registration should fail for non-existent class');
    }

    public function testDoubleInitialization(): void
    {
        // Mock the WordPress functions called during initialization
        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('add_filter')->andReturn(true);
        WP_Mock::userFunction('add_action')->andReturn(true);
        
        $app = App::initialize();
        $app->init(); // Second initialization
        
        // Should not throw an error
        $this->assertTrue($app->is_initialized());
    }
}