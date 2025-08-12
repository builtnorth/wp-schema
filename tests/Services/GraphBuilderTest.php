<?php
/**
 * Tests for GraphBuilder service
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\GraphBuilder;
use BuiltNorth\WPSchema\Services\ProviderRegistry;
use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaGraph;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Tests\TestCase;
use Mockery;
use WP_Mock;

/**
 * GraphBuilder test class
 */
class GraphBuilderTest extends TestCase {

	private GraphBuilder $builder;
	private ProviderRegistry $registry;

	public function setUp(): void {
		parent::setUp();
		$this->registry = Mockery::mock( ProviderRegistry::class );
		$this->builder = new GraphBuilder( $this->registry );
	}

	/**
	 * Test build_for_context creates graph from providers
	 */
	public function test_build_for_context_creates_graph_from_providers(): void {
		$piece1 = new SchemaPiece( 'site', 'WebSite', [ 'name' => 'Test Site' ] );
		$piece2 = new SchemaPiece( 'org', 'Organization', [ 'name' => 'Test Org' ] );

		$provider1 = Mockery::mock( SchemaProviderInterface::class );
		$provider1->shouldReceive( 'get_pieces' )
			->with( 'home' )
			->andReturn( [ $piece1 ] );

		$provider2 = Mockery::mock( SchemaProviderInterface::class );
		$provider2->shouldReceive( 'get_pieces' )
			->with( 'home' )
			->andReturn( [ $piece2 ] );

		$this->registry->shouldReceive( 'get_providers_for_context' )
			->with( 'home' )
			->andReturn( [ $provider1, $provider2 ] );

		// Mock WordPress filters - apply_filters needs to pass through the values
		WP_Mock::userFunction( 'apply_filters' )
			->andReturnUsing( function( $tag, $value ) {
				return $value;
			} );

		$graph = $this->builder->build_for_context( 'home' );

		$this->assertInstanceOf( SchemaGraph::class, $graph );
		$this->assertEquals( 2, $graph->count() );
		$this->assertTrue( $graph->has_piece( 'site' ) );
		$this->assertTrue( $graph->has_piece( 'org' ) );
	}

	/**
	 * Test build_for_context handles empty providers
	 */
	public function test_build_for_context_handles_empty_providers(): void {
		$this->registry->shouldReceive( 'get_providers_for_context' )
			->with( 'search' )
			->andReturn( [] );

		// Mock WordPress filters - apply_filters needs to pass through the values
		WP_Mock::userFunction( 'apply_filters' )
			->andReturnUsing( function( $tag, $value ) {
				return $value;
			} );

		$graph = $this->builder->build_for_context( 'search' );

		$this->assertInstanceOf( SchemaGraph::class, $graph );
		$this->assertTrue( $graph->is_empty() );
	}

	/**
	 * Test build_for_context handles SchemaPiece objects
	 */
	public function test_build_for_context_handles_schema_piece_objects(): void {
		$piece = new SchemaPiece( 'article', 'Article', [ 'headline' => 'Test' ] );

		$provider = Mockery::mock( SchemaProviderInterface::class );
		$provider->shouldReceive( 'get_pieces' )
			->with( 'singular' )
			->andReturn( [ $piece ] );

		$this->registry->shouldReceive( 'get_providers_for_context' )
			->with( 'singular' )
			->andReturn( [ $provider ] );

		// Mock WordPress filters - apply_filters needs to pass through the values
		WP_Mock::userFunction( 'apply_filters' )
			->andReturnUsing( function( $tag, $value ) {
				return $value;
			} );

		$graph = $this->builder->build_for_context( 'singular' );

		$this->assertEquals( 1, $graph->count() );
		$this->assertTrue( $graph->has_piece( 'article' ) );
	}

	/**
	 * Test build_for_context generates IDs for pieces without them
	 */
	public function test_build_for_context_generates_ids_for_pieces(): void {
		$provider = Mockery::mock( SchemaProviderInterface::class );
		$provider->shouldReceive( 'get_pieces' )
			->with( 'home' )
			->andReturn( [
				[ '@type' => 'Article', 'headline' => 'No ID Article' ]
			] );

		$this->registry->shouldReceive( 'get_providers_for_context' )
			->with( 'home' )
			->andReturn( [ $provider ] );

		// Mock WordPress filters
		WP_Mock::expectFilter( 'wp_schema_framework_pieces', Mockery::type( 'array' ), 'home' );
		WP_Mock::expectFilter( 'wp_schema_framework_piece_article', Mockery::type( SchemaPiece::class ), 'home' );
		WP_Mock::expectFilter( 'wp_schema_framework_graph', Mockery::type( 'array' ), 'home' );

		$graph = $this->builder->build_for_context( 'home' );

		$this->assertEquals( 1, $graph->count() );
		$pieces = $graph->get_pieces();
		$piece = array_values( $pieces )[0];
		$this->assertNotEmpty( $piece->get_id() );
	}

	/**
	 * Test build_for_context merges duplicate pieces
	 */
	public function test_build_for_context_merges_duplicate_pieces(): void {
		$provider1 = Mockery::mock( SchemaProviderInterface::class );
		$provider1->shouldReceive( 'get_pieces' )
			->with( 'home' )
			->andReturn( [
				[ '@type' => 'Organization', '@id' => 'org', 'name' => 'Name 1' ]
			] );

		$provider2 = Mockery::mock( SchemaProviderInterface::class );
		$provider2->shouldReceive( 'get_pieces' )
			->with( 'home' )
			->andReturn( [
				[ '@type' => 'Organization', '@id' => 'org', 'url' => 'https://example.com' ]
			] );

		$this->registry->shouldReceive( 'get_providers_for_context' )
			->with( 'home' )
			->andReturn( [ $provider1, $provider2 ] );

		// Mock WordPress filters
		WP_Mock::expectFilter( 'wp_schema_framework_pieces', Mockery::type( 'array' ), 'home' );
		WP_Mock::expectFilter( 'wp_schema_framework_piece_organization', Mockery::type( SchemaPiece::class ), 'home' );
		WP_Mock::expectFilter( 'wp_schema_framework_graph', Mockery::type( 'array' ), 'home' );

		$graph = $this->builder->build_for_context( 'home' );

		$this->assertEquals( 1, $graph->count() );
		$org = $graph->get_piece( 'org' );
		$this->assertEquals( 'Name 1', $org->get( 'name' ) );
		$this->assertEquals( 'https://example.com', $org->get( 'url' ) );
	}

	/**
	 * Test build_for_context handles invalid piece data
	 */
	public function test_build_for_context_handles_invalid_piece_data(): void {
		$provider = Mockery::mock( SchemaProviderInterface::class );
		$provider->shouldReceive( 'get_pieces' )
			->with( 'home' )
			->andReturn( [
				[ '@type' => 'Article', 'headline' => 'Valid' ],
				'invalid_string',
				123,
				null,
				[ 'no_type' => 'invalid' ]
			] );

		$this->registry->shouldReceive( 'get_providers_for_context' )
			->with( 'home' )
			->andReturn( [ $provider ] );

		// Mock WordPress filters
		WP_Mock::expectFilter( 'wp_schema_framework_pieces', Mockery::type( 'array' ), 'home' );
		WP_Mock::expectFilter( 'wp_schema_framework_piece_article', Mockery::type( SchemaPiece::class ), 'home' );
		WP_Mock::expectFilter( 'wp_schema_framework_graph', Mockery::type( 'array' ), 'home' );

		$graph = $this->builder->build_for_context( 'home' );

		// Should only have the valid Article piece
		$this->assertEquals( 1, $graph->count() );
		$pieces = $graph->get_pieces_by_type( 'Article' );
		$this->assertCount( 1, $pieces );
	}
}