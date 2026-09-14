<?php
/**
 * Tests for SchemaPiece multi-typed nodes.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Graph;

use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Tests\TestCase;

class SchemaPieceTypeTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setUpCommonMocks();
	}

	/**
	 * The common case must keep emitting a scalar @type — `["WebPage"]` is valid
	 * JSON-LD but not what consumers or the rest of this suite expect.
	 */
	public function test_single_type_stays_a_scalar(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', [ 'WebPage' ] );

		$this->assertSame( 'WebPage', $piece->to_array()['@type'] );
	}

	public function test_multiple_types_emit_as_a_list(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', [ 'WebPage', 'ContactPage' ] );

		$this->assertSame( [ 'WebPage', 'ContactPage' ], $piece->to_array()['@type'] );
	}

	/**
	 * get_type() feeds hook names (`piece_{type}`), type comparison, and
	 * esc_html() in the duplicate-@id warning. All three need a string, so a
	 * node becoming multi-typed must not change what get_type() returns.
	 */
	public function test_get_type_returns_the_primary_type_as_a_string(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', [ 'WebPage', 'FAQPage' ] );

		$this->assertSame( 'WebPage', $piece->get_type() );
		$this->assertIsString( $piece->get_type() );
	}

	public function test_get_types_returns_every_type(): void {
		$scalar = new SchemaPiece( 'https://example.com/a/', 'WebPage' );
		$multi  = new SchemaPiece( 'https://example.com/b/', [ 'WebPage', 'FAQPage' ] );

		$this->assertSame( [ 'WebPage' ], $scalar->get_types() );
		$this->assertSame( [ 'WebPage', 'FAQPage' ], $multi->get_types() );
	}

	/**
	 * add_type() is how a page becomes "also a ContactPage" without gaining a
	 * second node.
	 */
	public function test_add_type_promotes_a_scalar_to_a_list(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', 'WebPage' );

		$piece->add_type( 'ContactPage' );

		$this->assertSame( [ 'WebPage', 'ContactPage' ], $piece->to_array()['@type'] );
		$this->assertSame( 'WebPage', $piece->get_type() );
	}

	public function test_add_type_is_idempotent(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', 'WebPage' );

		$piece->add_type( 'ContactPage' )->add_type( 'ContactPage' );

		$this->assertSame( [ 'WebPage', 'ContactPage' ], $piece->to_array()['@type'] );
	}

	public function test_add_type_ignores_an_empty_type(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', 'WebPage' );

		$piece->add_type( '' );

		$this->assertSame( 'WebPage', $piece->to_array()['@type'] );
	}

	/**
	 * Duplicates in the constructor would emit `["WebPage","WebPage"]`.
	 */
	public function test_duplicate_types_are_collapsed(): void {
		$piece = new SchemaPiece( 'https://example.com/thing/', [ 'WebPage', 'WebPage', 'FAQPage' ] );

		$this->assertSame( [ 'WebPage', 'FAQPage' ], $piece->to_array()['@type'] );
	}
}
