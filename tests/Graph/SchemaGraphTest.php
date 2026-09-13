<?php
/**
 * Tests for SchemaGraph piece collection and filtering.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Graph;

use BuiltNorth\WPSchema\Graph\SchemaGraph;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Tests\TestCase;

class SchemaGraphTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		$this->setUpCommonMocks();
	}

	public function test_distinct_ids_are_both_kept(): void {
		$graph = new SchemaGraph();
		$graph->add_piece( new SchemaPiece( '#organization', 'Organization' ) );
		$graph->add_piece( new SchemaPiece( '#website', 'WebSite' ) );

		$this->assertCount( 2, $graph->get_pieces() );
	}

	/**
	 * Colliding @ids resolve last-writer-wins. The warning is only emitted when
	 * WordPress is loaded, so assert the resolution here and cover the notice
	 * separately.
	 */
	public function test_colliding_id_keeps_the_later_piece(): void {
		$graph = new SchemaGraph();
		$graph->add_piece( new SchemaPiece( '#thing', 'Organization' ) );
		$graph->add_piece( new SchemaPiece( '#thing', 'LocalBusiness' ) );

		$this->assertCount( 1, $graph->get_pieces() );
		$this->assertSame( 'LocalBusiness', $graph->get_piece( '#thing' )->get_type() );
	}

	/**
	 * The by-name filter must reach a node whose @id is an absolute IRI —
	 * previously the hook name embedded the site URL, so nothing could target it.
	 */
	public function test_name_filter_reaches_a_node_with_an_absolute_id(): void {
		$original = new SchemaPiece( 'https://example.com/#organization', 'Organization' );
		$replaced = new SchemaPiece( 'https://example.com/#organization', 'Organization', [ 'probe' => 'reached' ] );

		// onFilter()->with() matches arguments literally, so pass the exact
		// instance the graph will hand the filter rather than a type matcher.
		\WP_Mock::onFilter( 'wp_schema_framework_piece_id_organization' )
			->with( $original, 'home' )
			->reply( $replaced );

		$graph = new SchemaGraph();
		$graph->add_piece( $original );
		$graph->apply_filters( 'home' );

		$this->assertSame(
			'reached',
			$graph->get_piece( 'https://example.com/#organization' )->get( 'probe' )
		);
	}
}
