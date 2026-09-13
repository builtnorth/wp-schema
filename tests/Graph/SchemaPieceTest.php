<?php
/**
 * Tests for SchemaPiece name derivation.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Graph;

use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Tests\TestCase;

class SchemaPieceTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setUpCommonMocks();
	}

	/**
	 * An absolute @id and a bare fragment for the same node must agree, so a
	 * hook registered against the name works regardless of which convention
	 * the emitting provider used.
	 */
	public function test_absolute_and_fragment_ids_derive_the_same_name(): void {
		$absolute = new SchemaPiece( 'https://example.com/#organization', 'Organization' );
		$fragment = new SchemaPiece( '#organization', 'Organization' );

		$this->assertSame( 'organization', $absolute->get_name() );
		$this->assertSame( 'organization', $fragment->get_name() );
	}

	/**
	 * The derived name must not contain the site URL — that was the original
	 * bug, producing hook names no plugin could target portably.
	 */
	public function test_derived_name_excludes_the_site_url(): void {
		$piece = new SchemaPiece( 'https://example.com/#organization', 'Organization' );

		$this->assertStringNotContainsString( 'example', $piece->get_name() );
		$this->assertStringNotContainsString( 'https', $piece->get_name() );
	}

	public function test_explicit_name_overrides_derivation(): void {
		$piece = new SchemaPiece( 'https://example.com/#a1b2c3', 'Organization', [], 'organization' );

		$this->assertSame( 'organization', $piece->get_name() );
	}

	public function test_name_is_reduced_to_hook_safe_characters(): void {
		$piece = new SchemaPiece( 'https://example.com/#post:12/section', 'WebPage' );

		$this->assertMatchesRegularExpression( '/\A[a-z0-9_-]+\z/', $piece->get_name() );
	}

	public function test_id_and_type_remain_untouched_by_naming(): void {
		$piece = new SchemaPiece( 'https://example.com/#organization', 'Store' );
		$data  = $piece->to_array();

		$this->assertSame( 'https://example.com/#organization', $data['@id'] );
		$this->assertSame( 'Store', $data['@type'] );
	}
}
