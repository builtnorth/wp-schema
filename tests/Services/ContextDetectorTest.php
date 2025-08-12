<?php
/**
 * Tests for ContextDetector service
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\ContextDetector;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

/**
 * ContextDetector test class
 */
class ContextDetectorTest extends TestCase {

	private ContextDetector $detector;

	public function setUp(): void {
		parent::setUp();
		$this->detector = new ContextDetector();
	}

	/**
	 * Test home context detection
	 */
	public function test_detects_home_context(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( true );
		WP_Mock::userFunction( 'is_home' )->never();
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( 'home', $context );
	}

	/**
	 * Test singular context detection
	 */
	public function test_detects_singular_context(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->once()->andReturn( true );
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( 'singular', $context );
	}

	/**
	 * Test archive context detection
	 */
	public function test_detects_archive_context(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->once()->andReturn( true );
		// is_home won't be called because is_archive returns true (|| short-circuit)
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( 'archive', $context );
	}

	/**
	 * Test search context detection
	 */
	public function test_detects_search_context(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_home' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->once()->andReturn( true );
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( 'search', $context );
	}

	/**
	 * Test 404 context detection
	 */
	public function test_detects_404_context(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_home' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_404' )->once()->andReturn( true );
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( '404', $context );
	}

	/**
	 * Test unknown context detection
	 */
	public function test_detects_unknown_context(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_home' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->once()->andReturn( false );
		WP_Mock::userFunction( 'is_404' )->once()->andReturn( false );
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( 'unknown', $context );
	}

	/**
	 * Test context filter override
	 */
	public function test_context_can_be_filtered(): void {
		WP_Mock::userFunction( 'is_front_page' )->once()->andReturn( true );
		
		// Mock apply_filters to return 'custom' when called with wp_schema_framework_context
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_schema_framework_context', 'home' )
			->once()
			->andReturn( 'custom' );
		
		$context = $this->detector->get_current_context();
		
		$this->assertEquals( 'custom', $context );
	}

	/**
	 * Test should generate schema returns true for valid contexts
	 */
	public function test_should_generate_schema_for_valid_contexts(): void {
		$valid_contexts = [ 'home', 'singular', 'archive', 'search' ];
		
		// Mock all the skip conditions to return false
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::userFunction( 'is_feed' )->andReturn( false );
		WP_Mock::userFunction( 'is_robots' )->andReturn( false );
		WP_Mock::userFunction( 'is_trackback' )->andReturn( false );
		
		foreach ( $valid_contexts as $context ) {
			$this->assertTrue( 
				$this->detector->should_generate_schema( $context ),
				"Should generate schema for context: $context"
			);
		}
	}

	/**
	 * Test should generate schema returns false for 404
	 */
	public function test_should_not_generate_schema_for_404(): void {
		// Mock all the skip conditions to return false
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::userFunction( 'is_feed' )->andReturn( false );
		WP_Mock::userFunction( 'is_robots' )->andReturn( false );
		WP_Mock::userFunction( 'is_trackback' )->andReturn( false );
		
		$this->assertFalse( $this->detector->should_generate_schema( '404' ) );
	}

	/**
	 * Test should generate schema can be filtered
	 */
	public function test_should_generate_schema_can_be_filtered(): void {
		// Mock all the skip conditions to return false
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		WP_Mock::userFunction( 'is_feed' )->andReturn( false );
		WP_Mock::userFunction( 'is_robots' )->andReturn( false );
		WP_Mock::userFunction( 'is_trackback' )->andReturn( false );
		
		// Mock apply_filters to return false
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_schema_framework_output_enabled', true, 'home' )
			->once()
			->andReturn( false );
		
		$this->assertFalse( $this->detector->should_generate_schema( 'home' ) );
	}
}