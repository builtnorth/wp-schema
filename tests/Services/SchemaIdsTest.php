<?php
/**
 * Tests for SchemaIds @id conventions.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\SchemaIds;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class SchemaIdsTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setUpCommonMocks();

		WP_Mock::userFunction( 'trailingslashit' )
			->andReturnUsing( static fn( string $s ) => rtrim( $s, '/' ) . '/' );
	}

	/**
	 * Registered per test rather than in setUp(): a userFunction mock declared in
	 * setUp() takes precedence over a later per-test re-mock, which would make
	 * the "no permalink" case impossible to express.
	 */
	private function permalink_is( string|false $permalink ): void {
		WP_Mock::userFunction( 'get_permalink' )->andReturn( $permalink );
	}

	/**
	 * The WebPage @id is the bare permalink, no fragment — Yoast's main_schema_id.
	 */
	public function test_webpage_id_is_the_bare_permalink(): void {
		$this->permalink_is( 'https://example.com/hello-world/' );

		$this->assertSame( 'https://example.com/hello-world/', SchemaIds::webpage_id( 42 ) );
	}

	public function test_home_webpage_id_is_the_home_url(): void {
		$this->assertSame( 'https://example.com/', SchemaIds::home_webpage_id() );
	}

	/**
	 * Entity @ids hang off the permalink as fragments, so they are absolute IRIs
	 * that resolve to the page they belong to.
	 */
	public function test_entity_id_is_permalink_plus_fragment(): void {
		$this->permalink_is( 'https://example.com/hello-world/' );

		$this->assertSame( 'https://example.com/hello-world/#article', SchemaIds::entity_id( 42, '#article' ) );
	}

	public function test_entity_id_normalises_a_missing_hash(): void {
		$this->permalink_is( 'https://example.com/hello-world/' );

		$this->assertSame( 'https://example.com/hello-world/#localbusiness', SchemaIds::entity_id( 42, 'localbusiness' ) );
	}

	/**
	 * An entity links *up* to its page with both properties pointing at the same
	 * WebPage @id — the page never references the entity.
	 */
	public function test_page_links_point_both_properties_at_the_webpage(): void {
		$this->permalink_is( 'https://example.com/hello-world/' );

		$this->assertSame(
			[
				'isPartOf'         => [ '@id' => 'https://example.com/hello-world/' ],
				'mainEntityOfPage' => [ '@id' => 'https://example.com/hello-world/' ],
			],
			SchemaIds::page_links( 42 )
		);
	}

	public function test_helpers_return_empty_when_the_post_has_no_permalink(): void {
		$this->permalink_is( false );

		$this->assertSame( '', SchemaIds::webpage_id( 99 ) );
		$this->assertSame( '', SchemaIds::entity_id( 99, '#article' ) );
		$this->assertSame( [], SchemaIds::page_links( 99 ) );
	}
}
