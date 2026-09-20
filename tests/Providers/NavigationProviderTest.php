<?php
/**
 * Tests for NavigationProvider URL normalisation and visibility helpers.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\NavigationProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class NavigationProviderTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setUpCommonMocks();
	}

	public function test_absolute_url_resolves_root_relative_paths(): void {
		$this->assertSame(
			'https://example.com/about/',
			NavigationProvider::absolute_url( '/about/' )
		);
	}

	public function test_absolute_url_keeps_http_and_https(): void {
		$this->assertSame(
			'https://example.com/page',
			NavigationProvider::absolute_url( 'https://example.com/page' )
		);
		$this->assertSame(
			'http://example.com/page',
			NavigationProvider::absolute_url( 'http://example.com/page' )
		);
	}

	public function test_absolute_url_rejects_unsafe_schemes(): void {
		$this->assertSame( '', NavigationProvider::absolute_url( 'javascript:alert(1)' ) );
		$this->assertSame( '', NavigationProvider::absolute_url( 'data:text/html,hi' ) );
		$this->assertSame( '', NavigationProvider::absolute_url( '//evil.example/path' ) );
	}

	public function test_navigation_id_is_site_scoped(): void {
		$this->assertSame(
			'https://example.com/#navigation-primary',
			NavigationProvider::navigation_id( 'primary' )
		);
	}

	public function test_flush_cache_deletes_both_transients(): void {
		WP_Mock::userFunction( 'delete_transient' )
			->with( 'wp_schema_nav_pieces_v3' )
			->once();
		WP_Mock::userFunction( 'delete_transient' )
			->with( 'wp_schema_nav_ids_v2' )
			->once();

		NavigationProvider::flush_cache();

		$this->assertConditionsMet();
	}

	/**
	 * FSE discovery walks core/navigation blocks nested in chrome markup.
	 */
	public function test_find_navigation_blocks_collects_nested_refs(): void {
		$provider = new NavigationProvider();
		$method   = new \ReflectionMethod( NavigationProvider::class, 'find_navigation_blocks' );
		$method->setAccessible( true );

		$blocks = [
			[
				'blockName'   => 'core/group',
				'attrs'       => [],
				'innerBlocks' => [
					[
						'blockName'   => 'core/navigation',
						'attrs'       => [ 'ref' => 295 ],
						'innerBlocks' => [],
					],
				],
			],
		];

		$found = $method->invoke( $provider, $blocks );

		$this->assertCount( 1, $found );
		$this->assertSame( 295, $found[0]['attrs']['ref'] );
	}
}
