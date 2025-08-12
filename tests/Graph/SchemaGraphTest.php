<?php
/**
 * Tests for SchemaGraph class
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Graph;

use BuiltNorth\WPSchema\Graph\SchemaGraph;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

/**
 * SchemaGraph test class
 */
class SchemaGraphTest extends TestCase {

	private SchemaGraph $graph;

	public function setUp(): void {
		parent::setUp();
		$this->graph = new SchemaGraph();
	}

	/**
	 * Test add_piece adds piece to graph
	 */
	public function test_add_piece_adds_piece_to_graph(): void {
		$piece = new SchemaPiece( 'test-id', 'Article', [ 'headline' => 'Test' ] );
		
		$this->graph->add_piece( $piece );
		
		$this->assertTrue( $this->graph->has_piece( 'test-id' ) );
		$this->assertEquals( $piece, $this->graph->get_piece( 'test-id' ) );
	}

	/**
	 * Test add_pieces adds multiple pieces
	 */
	public function test_add_pieces_adds_multiple_pieces(): void {
		$piece1 = new SchemaPiece( 'id1', 'Article' );
		$piece2 = new SchemaPiece( 'id2', 'Person' );
		$piece3 = new SchemaPiece( 'id3', 'Organization' );
		
		$this->graph->add_pieces( [ $piece1, $piece2, $piece3 ] );
		
		$this->assertEquals( 3, $this->graph->count() );
		$this->assertTrue( $this->graph->has_piece( 'id1' ) );
		$this->assertTrue( $this->graph->has_piece( 'id2' ) );
		$this->assertTrue( $this->graph->has_piece( 'id3' ) );
	}

	/**
	 * Test get_pieces_by_type returns correct pieces
	 */
	public function test_get_pieces_by_type_returns_correct_pieces(): void {
		$article1 = new SchemaPiece( 'article1', 'Article' );
		$article2 = new SchemaPiece( 'article2', 'Article' );
		$person = new SchemaPiece( 'person1', 'Person' );
		
		$this->graph->add_pieces( [ $article1, $article2, $person ] );
		
		$articles = $this->graph->get_pieces_by_type( 'Article' );
		
		$this->assertCount( 2, $articles );
		$this->assertContains( $article1, $articles );
		$this->assertContains( $article2, $articles );
		$this->assertNotContains( $person, $articles );
	}

	/**
	 * Test remove_piece removes piece from graph
	 */
	public function test_remove_piece_removes_piece_from_graph(): void {
		$piece = new SchemaPiece( 'test-id', 'Article' );
		
		$this->graph->add_piece( $piece );
		$this->assertTrue( $this->graph->has_piece( 'test-id' ) );
		
		$this->graph->remove_piece( 'test-id' );
		$this->assertFalse( $this->graph->has_piece( 'test-id' ) );
	}

	/**
	 * Test clear removes all pieces
	 */
	public function test_clear_removes_all_pieces(): void {
		$this->graph->add_pieces( [
			new SchemaPiece( 'id1', 'Article' ),
			new SchemaPiece( 'id2', 'Person' ),
			new SchemaPiece( 'id3', 'Organization' )
		] );
		
		$this->assertEquals( 3, $this->graph->count() );
		
		$this->graph->clear();
		
		$this->assertEquals( 0, $this->graph->count() );
		$this->assertTrue( $this->graph->is_empty() );
	}

	/**
	 * Test is_empty returns correct status
	 */
	public function test_is_empty_returns_correct_status(): void {
		$this->assertTrue( $this->graph->is_empty() );
		
		$this->graph->add_piece( new SchemaPiece( 'id1', 'Article' ) );
		
		$this->assertFalse( $this->graph->is_empty() );
	}

	/**
	 * Test validate_references finds broken references
	 */
	public function test_validate_references_finds_broken_references(): void {
		$piece1 = new SchemaPiece( 'article', 'Article' );
		$piece1->add_reference( 'author', 'missing-author' );
		
		$piece2 = new SchemaPiece( 'person', 'Person' );
		$piece2->add_reference( 'worksFor', 'existing-org' );
		
		$piece3 = new SchemaPiece( 'existing-org', 'Organization' );
		
		$this->graph->add_pieces( [ $piece1, $piece2, $piece3 ] );
		
		$errors = $this->graph->validate_references();
		
		$this->assertCount( 1, $errors );
		$this->assertStringContainsString( 'missing-author', $errors[0] );
	}

	/**
	 * Test to_array converts graph to array format
	 */
	public function test_to_array_converts_graph_to_array(): void {
		$piece1 = new SchemaPiece( 'article', 'Article', [ 'headline' => 'Test Article' ] );
		$piece2 = new SchemaPiece( 'person', 'Person', [ 'name' => 'John Doe' ] );
		
		$this->graph->add_pieces( [ $piece1, $piece2 ] );
		
		$array = $this->graph->to_array();
		
		$this->assertIsArray( $array );
		$this->assertCount( 2, $array );
		
		// Each piece should have @context added
		foreach ( $array as $piece_data ) {
			$this->assertArrayHasKey( '@context', $piece_data );
			$this->assertEquals( 'https://schema.org', $piece_data['@context'] );
		}
	}

	/**
	 * Test to_json converts graph to JSON
	 */
	public function test_to_json_converts_graph_to_json(): void {
		$piece = new SchemaPiece( 'article', 'Article', [ 'headline' => 'Test' ] );
		$this->graph->add_piece( $piece );
		
		$json = $this->graph->to_json();
		
		$this->assertJson( $json );
		
		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
		$this->assertCount( 1, $decoded );
		
		// First piece should have @context
		$this->assertArrayHasKey( '@context', $decoded[0] );
		$this->assertEquals( 'https://schema.org', $decoded[0]['@context'] );
	}

	/**
	 * Test apply_filters applies WordPress filters
	 */
	public function test_apply_filters_applies_wordpress_filters(): void {
		$piece = new SchemaPiece( 'article', 'Article', [ 'headline' => 'Original' ] );
		$this->graph->add_piece( $piece );
		
		// Mock the filter to modify the piece
		WP_Mock::onFilter( 'wp_schema_framework_pieces' )
			->with( [ $piece ], 'home' )
			->reply( function( $pieces ) {
				$modified = new SchemaPiece( 'article', 'Article', [ 'headline' => 'Modified' ] );
				return [ $modified ];
			} );
		
		WP_Mock::onFilter( 'wp_schema_framework_piece_article' )
			->with( \WP_Mock\Functions::type( SchemaPiece::class ), 'home' )
			->reply( function( $piece ) {
				return $piece;
			} );
		
		WP_Mock::onFilter( 'wp_schema_framework_graph' )
			->with( \WP_Mock\Functions::type( 'array' ), 'home' )
			->reply( function( $graph ) {
				return $graph;
			} );
		
		$this->graph->apply_filters( 'home' );
		
		$updated_piece = $this->graph->get_piece( 'article' );
		$this->assertEquals( 'Modified', $updated_piece->get( 'headline' ) );
	}

	/**
	 * Test count returns correct number of pieces
	 */
	public function test_count_returns_correct_number(): void {
		$this->assertEquals( 0, $this->graph->count() );
		
		$this->graph->add_piece( new SchemaPiece( 'id1', 'Article' ) );
		$this->assertEquals( 1, $this->graph->count() );
		
		$this->graph->add_piece( new SchemaPiece( 'id2', 'Person' ) );
		$this->assertEquals( 2, $this->graph->count() );
		
		$this->graph->remove_piece( 'id1' );
		$this->assertEquals( 1, $this->graph->count() );
	}
}