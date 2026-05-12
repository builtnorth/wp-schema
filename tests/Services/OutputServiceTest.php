<?php
/**
 * Tests for OutputService service
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Graph\SchemaGraph;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Services\ContextDetector;
use BuiltNorth\WPSchema\Services\GraphBuilder;
use BuiltNorth\WPSchema\Services\OutputService;
use BuiltNorth\WPSchema\Tests\TestCase;
use Mockery;
use WP_Mock;

/**
 * OutputService test class
 */
class OutputServiceTest extends TestCase {

	public function test_output_schema_escapes_script_breakout_sequences(): void {
		$graph_builder = Mockery::mock( GraphBuilder::class );
		$context_detector = Mockery::mock( ContextDetector::class );

		$graph = new SchemaGraph();
		$piece = new SchemaPiece( '#test', 'Thing', [
			'name' => '</script><script>alert(1)</script>',
		] );
		$graph->add_piece( $piece );

		$context_detector->shouldReceive( 'get_current_context' )->once()->andReturn( 'singular' );
		$context_detector->shouldReceive( 'should_generate_schema' )->once()->with( 'singular' )->andReturn( true );
		$graph_builder->shouldReceive( 'build_for_context' )->once()->with( 'singular' )->andReturn( $graph );

		WP_Mock::userFunction( 'do_action' )->andReturn( null );
		WP_Mock::userFunction( 'apply_filters' )
			->andReturnUsing(
				function( $hook, ...$args ) {
					if ( 'wp_schema_framework_graph' === $hook ) {
						return $args[0];
					}
					if ( 'wp_schema_framework_json_output' === $hook ) {
						return $args[0];
					}
					return $args[0] ?? null;
				}
			);

		$service = new OutputService( $graph_builder, $context_detector );

		ob_start();
		$service->output_schema();
		$output = ob_get_clean();

		$this->assertStringContainsString( '\\u003C/script\\u003E\\u003Cscript\\u003Ealert(1)\\u003C/script\\u003E', $output );
		$this->assertStringNotContainsString( '</script><script>alert(1)</script>', $output );
	}
}
