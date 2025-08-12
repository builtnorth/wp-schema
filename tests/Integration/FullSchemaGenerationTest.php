<?php
/**
 * Integration tests for full schema generation
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Integration;

use BuiltNorth\WPSchema\App;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;
use stdClass;

/**
 * Full schema generation integration test class
 */
class FullSchemaGenerationTest extends TestCase {

	/**
	 * Test full schema generation for home page
	 */
	public function test_generates_schema_for_home_page(): void {
		// Mock WordPress context functions
		WP_Mock::userFunction( 'is_front_page' )->andReturn( true );
		WP_Mock::userFunction( 'is_home' )->andReturn( true );
		WP_Mock::userFunction( 'is_singular' )->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->andReturn( false );
		WP_Mock::userFunction( 'is_404' )->andReturn( false );
		WP_Mock::userFunction( 'is_attachment' )->andReturn( false );

		// Mock site info
		WP_Mock::userFunction( 'home_url' )->andReturn( 'https://example.com' );
		WP_Mock::userFunction( 'get_bloginfo' )
			->andReturnUsing( function( $show ) {
				$info = [
					'name' => 'Test Site',
					'description' => 'A test WordPress site',
					'url' => 'https://example.com',
				];
				return $info[ $show ] ?? 'Test Site';
			} );

		// Mock options
		WP_Mock::userFunction( 'get_option' )
			->andReturnUsing( function( $option, $default = false ) {
				$options = [
					'wp_schema_organization_type' => 'Organization',
					'wp_schema_organization_name' => 'Test Organization',
				];
				return $options[ $option ] ?? $default;
			} );
		
		// Mock navigation functions
		WP_Mock::userFunction( 'wp_get_nav_menus' )->andReturn( [] );
		WP_Mock::userFunction( 'has_custom_logo' )->andReturn( false );
		WP_Mock::userFunction( 'get_site_icon_url' )->andReturn( false );

		// Mock filters
		WP_Mock::expectFilter( 'wp_schema_framework_context' );
		WP_Mock::expectFilter( 'wp_schema_framework_output_enabled' );
		WP_Mock::expectFilter( 'wp_schema_framework_pieces' );
		WP_Mock::expectFilter( 'wp_schema_framework_graph' );
		WP_Mock::expectFilter( 'wp_schema_framework_organization_data' );
		WP_Mock::expectFilter( 'wp_schema_framework_website_data' );

		// Mock action hooks
		WP_Mock::expectAction( 'wp_schema_framework_register_providers' );
		WP_Mock::expectAction( 'wp_schema_framework_ready' );

		// Initialize the app
		$app = App::initialize();

		$this->assertTrue( $app->is_initialized() );

		// Get the graph builder and build schema
		$graph_builder = $app->get_graph_builder();
		$graph = $graph_builder->build_for_context( 'home' );

		$this->assertNotNull( $graph );
		$this->assertFalse( $graph->is_empty() );

		// Convert to array for assertions
		$schema = $graph->to_array();

		$this->assertArrayHasKey( '@context', $schema );
		$this->assertEquals( 'https://schema.org', $schema['@context'] );
		$this->assertArrayHasKey( '@graph', $schema );
		$this->assertIsArray( $schema['@graph'] );
	}

	/**
	 * Test full schema generation for singular post
	 */
	public function test_generates_schema_for_singular_post(): void {
		// Create mock post
		$post = new stdClass();
		$post->ID = 123;
		$post->post_title = 'Test Post';
		$post->post_content = 'Test content';
		$post->post_excerpt = 'Test excerpt';
		$post->post_author = 1;
		$post->post_date = '2024-01-15 10:00:00';
		$post->post_modified = '2024-01-16 11:00:00';
		$post->post_type = 'post';

		// Mock WordPress context
		WP_Mock::userFunction( 'is_front_page' )->andReturn( false );
		WP_Mock::userFunction( 'is_home' )->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )
			->andReturnUsing( function( $post_type = '' ) {
				return $post_type === 'post' || $post_type === '';
			} );
		WP_Mock::userFunction( 'is_archive' )->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->andReturn( false );
		WP_Mock::userFunction( 'is_404' )->andReturn( false );
		WP_Mock::userFunction( 'is_attachment' )->andReturn( false );

		// Mock post data
		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 123 );
		WP_Mock::userFunction( 'get_queried_object' )->andReturn( $post );
		WP_Mock::userFunction( 'get_post_type' )->andReturn( 'post' );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( 'https://example.com/test-post' );
		WP_Mock::userFunction( 'get_the_title' )->andReturn( 'Test Post' );
		WP_Mock::userFunction( 'get_the_excerpt' )->andReturn( 'Test excerpt' );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_the_post_thumbnail_url' )->andReturn( false );
		WP_Mock::userFunction( 'get_post_time' )->andReturn( '2024-01-15T10:00:00+00:00' );
		WP_Mock::userFunction( 'get_the_modified_time' )->andReturn( '2024-01-16T11:00:00+00:00' );
		WP_Mock::userFunction( 'wp_get_nav_menus' )->andReturn( [] );
		WP_Mock::userFunction( 'has_custom_logo' )->andReturn( false );
		WP_Mock::userFunction( 'get_site_icon_url' )->andReturn( false );

		// Mock author data
		$author = new stdClass();
		$author->ID = 1;
		$author->display_name = 'John Doe';
		WP_Mock::userFunction( 'get_userdata' )->andReturn( $author );
		WP_Mock::userFunction( 'get_author_posts_url' )->andReturn( 'https://example.com/author/john' );
		WP_Mock::userFunction( 'get_user_meta' )->andReturn( '' );

		// Mock site info
		WP_Mock::userFunction( 'home_url' )->andReturn( 'https://example.com' );
		WP_Mock::userFunction( 'get_bloginfo' )->andReturn( 'Test Site' );

		// Mock options
		WP_Mock::userFunction( 'get_option' )
			->andReturnUsing( function( $option, $default = false ) {
				$options = [
					'date_format' => 'Y-m-d',
					'time_format' => 'H:i:s',
				];
				return $options[ $option ] ?? $default;
			} );

		// Mock filters
		WP_Mock::expectFilter( 'wp_schema_framework_context' );
		WP_Mock::expectFilter( 'wp_schema_framework_output_enabled' );
		WP_Mock::expectFilter( 'wp_schema_framework_pieces' );
		WP_Mock::expectFilter( 'wp_schema_framework_graph' );
		WP_Mock::expectFilter( 'wp_schema_framework_article_data' );
		WP_Mock::expectFilter( 'wp_schema_framework_post_type_override' );

		// Mock action hooks
		WP_Mock::expectAction( 'wp_schema_framework_register_providers' );
		WP_Mock::expectAction( 'wp_schema_framework_ready' );

		// Initialize and get schema
		$app = App::initialize();
		$graph = $app->get_graph_builder()->build_for_context( 'singular' );
		$schema = $graph->to_array();

		$this->assertArrayHasKey( '@graph', $schema );
		$this->assertNotEmpty( $schema['@graph'] );

		// Find the article in the graph
		$article = null;
		foreach ( $schema['@graph'] as $item ) {
			if ( isset( $item['@type'] ) && $item['@type'] === 'Article' ) {
				$article = $item;
				break;
			}
		}

		$this->assertNotNull( $article, 'Article schema should be present' );
		$this->assertEquals( 'Test Post', $article['headline'] );
		$this->assertEquals( 'Test excerpt', $article['description'] );
	}

	/**
	 * Test schema generation with custom provider
	 */
	public function test_schema_generation_with_custom_provider(): void {
		// Mock WordPress context
		WP_Mock::userFunction( 'is_front_page' )->andReturn( true );
		WP_Mock::userFunction( 'is_home' )->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->andReturn( false );
		WP_Mock::userFunction( 'is_404' )->andReturn( false );
		WP_Mock::userFunction( 'is_attachment' )->andReturn( false );

		// Mock site info
		WP_Mock::userFunction( 'home_url' )->andReturn( 'https://example.com' );
		WP_Mock::userFunction( 'get_bloginfo' )->andReturn( 'Test Site' );
		WP_Mock::userFunction( 'get_option' )->andReturn( false );
		
		// Mock navigation functions
		WP_Mock::userFunction( 'wp_get_nav_menus' )->andReturn( [] );
		WP_Mock::userFunction( 'has_custom_logo' )->andReturn( false );
		WP_Mock::userFunction( 'get_site_icon_url' )->andReturn( false );

		// Create a custom provider class
		$custom_provider_class = new class implements \BuiltNorth\WPSchema\Contracts\SchemaProviderInterface {
			public function can_provide( string $context ): bool {
				return $context === 'home';
			}

			public function get_pieces( string $context ): array {
				return [
					[
						'@type' => 'CustomType',
						'@id' => 'custom-schema',
						'name' => 'Custom Schema Item',
					]
				];
			}

			public function get_priority(): int {
				return 10;
			}
		};

		// Mock filters and actions
		WP_Mock::expectAction( 'wp_schema_framework_register_providers' );
		WP_Mock::expectAction( 'wp_schema_framework_ready' );
		WP_Mock::expectFilter( 'wp_schema_framework_context' );
		WP_Mock::expectFilter( 'wp_schema_framework_output_enabled' );
		WP_Mock::expectFilter( 'wp_schema_framework_pieces' );
		WP_Mock::expectFilter( 'wp_schema_framework_graph' );

		// Initialize app
		$app = App::initialize();

		// Register custom provider
		$app->get_registry()->register( 'custom', $custom_provider_class );

		// Build schema
		$graph = $app->get_graph_builder()->build_for_context( 'home' );
		$schema = $graph->to_array();

		// Check for custom schema
		$this->assertArrayHasKey( '@graph', $schema );
		
		$custom_found = false;
		foreach ( $schema['@graph'] as $item ) {
			if ( isset( $item['@type'] ) && $item['@type'] === 'CustomType' ) {
				$custom_found = true;
				$this->assertEquals( 'Custom Schema Item', $item['name'] );
				break;
			}
		}

		$this->assertTrue( $custom_found, 'Custom schema type should be present' );
	}
}