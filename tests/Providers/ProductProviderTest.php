<?php
/**
 * Tests for ProductProvider @id conventions and page linkage.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\ProductProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class ProductProviderTest extends TestCase {

	private ProductProvider $provider;

	public function setUp(): void {
		parent::setUp();
		$this->provider = new ProductProvider();
		$this->setUpCommonMocks();
	}

	/**
	 * The provider reads product data through a filter before touching any
	 * commerce plugin, so a canned reply is all that is needed to exercise
	 * get_pieces() without WooCommerce/EDD/BigCommerce present.
	 */
	private function product_data_is( array $data ): void {
		WP_Mock::onFilter( 'wp_schema_framework_get_product_data' )
			->with( null, 42 )
			->reply( $data );
	}

	private function queried_post( string|false $permalink ): \stdClass {
		$post            = new \stdClass();
		$post->ID        = 42;
		$post->post_type = 'product';

		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 42 );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( $permalink );
		WP_Mock::userFunction( 'get_the_title' )->andReturn( 'Test Product' );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnUsing( static fn( $s ) => $s );

		return $post;
	}

	/**
	 * A bare "#product" fragment resolves against whatever URL the consumer
	 * reads the graph from, so the entity @id has to be page-scoped — the same
	 * convention ArticleProvider and SchemaIds already use.
	 */
	public function test_product_id_is_page_scoped(): void {
		$this->queried_post( 'https://example.com/thing/' );
		$this->product_data_is( [ 'name' => 'Test Product' ] );

		$pieces = $this->provider->get_pieces( 'singular' );

		$this->assertCount( 1, $pieces );
		$this->assertSame( 'https://example.com/thing/#product', $pieces[0]->get_id() );
	}

	/**
	 * Without isPartOf/mainEntityOfPage the Product node is an island: the page
	 * and the entity both exist but nothing connects them.
	 */
	public function test_product_links_up_to_its_page(): void {
		$this->queried_post( 'https://example.com/thing/' );
		$this->product_data_is( [ 'name' => 'Test Product' ] );

		$data = $this->provider->get_pieces( 'singular' )[0]->to_array();

		$this->assertSame( [ '@id' => 'https://example.com/thing/' ], $data['isPartOf'] );
		$this->assertSame( [ '@id' => 'https://example.com/thing/' ], $data['mainEntityOfPage'] );
	}

	/**
	 * No permalink means no page node to hang the entity off, so emitting one
	 * would guarantee a dangling reference.
	 */
	public function test_emits_nothing_without_a_permalink(): void {
		$this->queried_post( false );
		$this->product_data_is( [ 'name' => 'Test Product' ] );

		$this->assertSame( [], $this->provider->get_pieces( 'singular' ) );
	}

	/**
	 * EDD 3+ ships Product JSON-LD — stand down when its structured-data class is present.
	 */
	public function test_stands_down_when_edd_structured_data_is_active(): void {
		if ( ! class_exists( 'EDD_Structured_Data', false ) ) {
			eval( 'class EDD_Structured_Data {}' );
		}
		if ( ! class_exists( 'Easy_Digital_Downloads', false ) ) {
			eval( 'class Easy_Digital_Downloads {}' );
		}

		WP_Mock::userFunction( 'get_post_type' )->andReturn( 'download' );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 42 );
		WP_Mock::onFilter( 'wp_schema_framework_edd_schema_active' )
			->with( true )
			->reply( true );

		$this->assertFalse( $this->provider->can_provide( 'singular' ) );
	}

	/**
	 * BigCommerce defaults to us providing; filter can force a stand-down.
	 */
	public function test_bigcommerce_stand_down_is_opt_in(): void {
		WP_Mock::userFunction( 'get_post_type' )->andReturn( 'bigcommerce_product' );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 42 );
		WP_Mock::userFunction( 'bigcommerce' )->andReturn( true );

		WP_Mock::onFilter( 'wp_schema_framework_bigcommerce_schema_active' )
			->with( false )
			->reply( false );

		$this->assertTrue( $this->provider->can_provide( 'singular' ) );

		WP_Mock::onFilter( 'wp_schema_framework_bigcommerce_schema_active' )
			->with( false )
			->reply( true );

		$this->assertFalse( $this->provider->can_provide( 'singular' ) );
	}
}
