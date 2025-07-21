<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests;

use PHPUnit\Framework\TestCase;
use BuiltNorth\WPSchema\App;

/**
 * Test the main App class
 */
class AppTest extends TestCase
{
    protected function setUp(): void
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
        $app = App::initialize();
        
        $this->assertInstanceOf(App::class, $app);
        $this->assertTrue($app->is_initialized());
    }

    public function testGetRegistry(): void
    {
        $app = App::initialize();
        $registry = $app->get_registry();
        
        $this->assertInstanceOf('BuiltNorth\WPSchema\Services\ProviderRegistry', $registry);
    }

    public function testGetGraphBuilder(): void
    {
        $app = App::initialize();
        $graph_builder = $app->get_graph_builder();
        
        $this->assertInstanceOf('BuiltNorth\WPSchema\Services\GraphBuilder', $graph_builder);
    }

    public function testGetTypeRegistry(): void
    {
        $app = App::initialize();
        $type_registry = $app->get_type_registry();
        
        $this->assertInstanceOf('BuiltNorth\WPSchema\Services\SchemaTypeRegistry', $type_registry);
    }

    public function testRegisterProvider(): void
    {
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
        App::initialize();
        
        $result = App::register_provider('test_provider', 'NonExistentClass');
        
        $this->assertFalse($result, 'Provider registration should fail for non-existent class');
    }

    public function testDoubleInitialization(): void
    {
        $app = App::initialize();
        $app->init(); // Second initialization
        
        // Should not throw an error
        $this->assertTrue($app->is_initialized());
    }
}