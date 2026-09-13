<?php
/**
 * Tests for GenericSchemaProvider gating.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\GenericSchemaProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class GenericSchemaProviderTest extends TestCase {

	private GenericSchemaProvider $provider;

	public function setUp(): void {
		parent::setUp();
		$this->provider = new GenericSchemaProvider();
		$this->setUpCommonMocks();
	}

	/**
	 * WP_Mock passes unregistered filters straight through (returns the first
	 * argument), which is what we want for wp_schema_framework_post_type_override
	 * — it just echoes the mapped type. Only the mapping and skip filters need a
	 * canned reply, and onFilter()->with() matches arguments literally.
	 */
	private function queried_post( string $post_type, string $mapped_type ): void {
		$post            = new \stdClass();
		$post->ID        = 42;
		$post->post_type = $post_type;

		WP_Mock::userFunction( 'get_queried_object' )->andReturn( $post );

		WP_Mock::onFilter( 'wp_schema_framework_post_type_mapping' )
			->with( '', $post_type )
			->reply( $mapped_type );
	}

	/**
	 * A type with no dedicated provider (VideoObject) is exactly what this
	 * fallback exists for.
	 */
	public function test_provides_for_a_type_without_a_dedicated_provider(): void {
		$this->queried_post( 'video', 'VideoObject' );

		$this->assertTrue( $this->provider->can_provide( 'singular' ) );
	}

	/**
	 * A package that emits its own entity for a post type opts out via
	 * wp_schema_framework_generic_skip_post_types, so this fallback does not
	 * emit a second entity node for the same post. It has to be keyed by post
	 * type: a location may resolve to Hotel, Store, GasStation… so no single
	 * type is a reliable signal.
	 */
	public function test_skips_post_types_claimed_by_another_provider(): void {
		$this->queried_post( 'polaris_location', 'Hotel' );

		WP_Mock::onFilter( 'wp_schema_framework_generic_skip_post_types' )
			->with( [] )
			->reply( [ 'polaris_location' ] );

		$this->assertFalse( $this->provider->can_provide( 'singular' ) );
	}

	/**
	 * Types with a dedicated provider (Article, Service, WebPage…) are already
	 * excluded by the handled-types list, independent of the post-type opt-out.
	 */
	public function test_skips_types_with_a_dedicated_provider(): void {
		$this->queried_post( 'post', 'Article' );

		$this->assertFalse( $this->provider->can_provide( 'singular' ) );
	}
}
