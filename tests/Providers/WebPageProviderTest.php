<?php
/**
 * Tests for WebPageProvider owning the page node.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\WebPageProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class WebPageProviderTest extends TestCase {

	private WebPageProvider $provider;

	public function setUp(): void {
		parent::setUp();
		$this->provider = new WebPageProvider();
		$this->setUpCommonMocks();
	}

	private function queried_post( string $post_type = 'page' ): \WP_Post {
		$post = new \WP_Post(
			[
				'ID'          => 42,
				'post_type'   => $post_type,
				'post_name'   => 'contact-us',
				'post_title'  => 'Contact Us',
				'post_author' => 7,
			]
		);

		WP_Mock::userFunction( 'get_queried_object' )->andReturn( $post );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( 'https://example.com/contact-us/' );
		WP_Mock::userFunction( 'get_the_date' )->andReturn( '2026-01-01T00:00:00+00:00' );
		WP_Mock::userFunction( 'get_the_modified_date' )->andReturn( '2026-01-02T00:00:00+00:00' );
		WP_Mock::userFunction( 'get_post_thumbnail_id' )->andReturn( 0 );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnUsing( static fn( $s ) => $s );

		// AuthorProvider::has_resolvable_author() resolves the author before
		// WebPageProvider decides whether to reference the #author node.
		WP_Mock::userFunction( 'get_userdata' )->andReturn( false );

		return $post;
	}

	/**
	 * The page node is unconditional now: it is the anchor every entity node
	 * points at via isPartOf/mainEntityOfPage, so yielding it to another
	 * provider left those references dangling.
	 */
	public function test_always_provides_for_a_singular_request(): void {
		$this->queried_post();

		$this->assertTrue( $this->provider->can_provide( 'singular' ) );
	}

	public function test_provides_for_the_home_context(): void {
		$this->assertTrue( $this->provider->can_provide( 'home' ) );
	}

	public function test_does_not_provide_for_archive_or_search(): void {
		$this->assertFalse( $this->provider->can_provide( 'archive' ) );
		$this->assertFalse( $this->provider->can_provide( 'search' ) );
	}

	/**
	 * An ordinary page is a plain scalar @type.
	 */
	public function test_ordinary_page_is_typed_webpage(): void {
		$this->queried_post();

		$piece = $this->provider->get_pieces( 'singular' )[0];

		$this->assertSame( 'https://example.com/contact-us/', $piece->get_id() );
		$this->assertSame( 'WebPage', $piece->to_array()['@type'] );
	}

	/**
	 * A page resolving to a WebPage subtype becomes one multi-typed node rather
	 * than gaining a second node for the same URL — schema.org's shape for
	 * "this page is also a contact page".
	 */
	public function test_subtype_is_appended_to_the_page_node(): void {
		$this->queried_post();

		// WP_Mock passes unregistered filters straight through (returning the
		// first argument), so post_type_override echoes whatever the mapping
		// filter resolved — mock the mapping and let the override pass it on.
		WP_Mock::onFilter( 'wp_schema_framework_post_type_mapping' )
			->with( 'WebPage', 'page' )
			->reply( 'ContactPage' );

		$piece = $this->provider->get_pieces( 'singular' )[0];

		$this->assertSame( [ 'WebPage', 'ContactPage' ], $piece->to_array()['@type'] );
		$this->assertSame( 'https://example.com/contact-us/', $piece->get_id() );
	}

	/**
	 * An entity type (Article, Service, Hotel…) belongs to the entity node, not
	 * the page node — the page stays a plain WebPage.
	 */
	public function test_entity_type_does_not_retype_the_page_node(): void {
		$this->queried_post( 'post' );

		WP_Mock::onFilter( 'wp_schema_framework_post_type_mapping' )
			->with( '', 'post' )
			->reply( 'Article' );

		$piece = $this->provider->get_pieces( 'singular' )[0];

		$this->assertSame( 'WebPage', $piece->to_array()['@type'] );
	}

	/**
	 * Referencing a BreadcrumbList that nothing emits is a dangling reference.
	 */
	public function test_breadcrumb_reference_is_gated(): void {
		$this->queried_post();

		$data = $this->provider->get_pieces( 'singular' )[0]->to_array();

		$this->assertArrayNotHasKey( 'breadcrumb', $data );
	}
}
