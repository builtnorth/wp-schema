<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Tests\TestCase;
use BuiltNorth\WPSchema\Providers\OrganizationProvider;
use WP_Mock;

/**
 * Test the OrganizationProvider class
 */
class OrganizationProviderTest extends TestCase
{
    private OrganizationProvider $provider;

    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new OrganizationProvider();
        
        // Set up common mocks for WordPress functions used by the provider
        $this->setUpCommonMocks();
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
        // Mock WordPress functions that might be used
        WP_Mock::userFunction('has_custom_logo')->andReturn(false);
        WP_Mock::userFunction('get_theme_mod')->andReturn(false);
        WP_Mock::userFunction('get_site_icon_url')->andReturn('');
        WP_Mock::userFunction('apply_filters')
            ->andReturnUsing(function($hook, $value) {
                return $value;
            });
        
        $pieces = $this->provider->get_pieces('home');
        
        $this->assertIsArray($pieces);
        $this->assertCount(1, $pieces);
        
        $organization = $pieces[0];
        $this->assertInstanceOf('BuiltNorth\WPSchema\Graph\SchemaPiece', $organization);
        $this->assertEquals('#organization', $organization->get_id());
        $this->assertEquals('Organization', $organization->get_type());
        
        // Check basic properties
        $data = $organization->to_array();
        $this->assertArrayHasKey('@type', $data);
        $this->assertArrayHasKey('@id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('url', $data);
        
        $this->assertConditionsMet();
    }

    public function testGetPiecesWithLogo(): void
    {
        // Mock WordPress functions for logo scenario
        WP_Mock::userFunction('has_custom_logo')->andReturn(true);
        WP_Mock::userFunction('get_theme_mod')
            ->with('custom_logo')
            ->andReturn(123);
        WP_Mock::userFunction('wp_get_attachment_image_src')
            ->with(123, 'full')
            ->andReturn(['https://example.com/logo.png', 300, 100]);
        WP_Mock::userFunction('get_site_icon_url')->andReturn('');
        WP_Mock::userFunction('apply_filters')
            ->andReturnUsing(function($hook, $value) {
                return $value;
            });
        
        $pieces = $this->provider->get_pieces('home');
        
        $this->assertIsArray($pieces);
        $this->assertGreaterThanOrEqual(1, count($pieces));
        
        // Find the organization piece
        $organization = null;
        foreach ($pieces as $piece) {
            if ($piece->get_id() === '#organization') {
                $organization = $piece;
                break;
            }
        }
        
        $this->assertNotNull($organization);
        $data = $organization->to_array();
        
        // Check if logo information is included
        if (isset($data['logo'])) {
            $this->assertIsArray($data['logo']);
        }
        
        $this->assertConditionsMet();
    }
}