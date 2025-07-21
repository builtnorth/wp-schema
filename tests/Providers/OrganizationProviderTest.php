<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests\Providers;

use PHPUnit\Framework\TestCase;
use BuiltNorth\WPSchema\Providers\OrganizationProvider;

/**
 * Test the OrganizationProvider class
 */
class OrganizationProviderTest extends TestCase
{
    private OrganizationProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new OrganizationProvider();
    }

    public function testCanProvide(): void
    {
        // Organization should be provided on all pages
        $this->assertTrue($this->provider->can_provide('home'));
        $this->assertTrue($this->provider->can_provide('singular'));
        $this->assertTrue($this->provider->can_provide('archive'));
        $this->assertTrue($this->provider->can_provide('any-context'));
    }

    public function testGetPriority(): void
    {
        $this->assertEquals(5, $this->provider->get_priority());
    }

    public function testGetPieces(): void
    {
        $pieces = $this->provider->get_pieces('home');
        
        $this->assertIsArray($pieces);
        $this->assertCount(1, $pieces);
        
        $organization = $pieces[0];
        $this->assertInstanceOf('BuiltNorth\WPSchema\Graph\SchemaPiece', $organization);
        $this->assertEquals('#organization', $organization->get_id());
        $this->assertEquals('Organization', $organization->get_type());
        
        // Check basic properties
        $data = $organization->to_array();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('url', $data);
        $this->assertEquals('Test Site', $data['name']); // From mocked get_bloginfo
        $this->assertEquals('https://example.com/', $data['url']); // From mocked home_url
    }

    public function testFilterHook(): void
    {
        // Add a filter to modify organization data
        add_filter('wp_schema_framework_organization_data', function($data) {
            $data['sameAs'] = ['https://twitter.com/example'];
            return $data;
        });
        
        $pieces = $this->provider->get_pieces('home');
        $organization = $pieces[0];
        $data = $organization->to_array();
        
        $this->assertArrayHasKey('sameAs', $data);
        $this->assertEquals(['https://twitter.com/example'], $data['sameAs']);
    }
}