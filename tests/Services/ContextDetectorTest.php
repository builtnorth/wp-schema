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

	public function test_get_current_context_can_be_filtered(): void {
		WP_Mock::userFunction( 'is_front_page' )->andReturn( false );
		WP_Mock::userFunction( 'is_attachment' )->andReturn( false );
		WP_Mock::userFunction( 'is_singular' )->andReturn( false );
		WP_Mock::userFunction( 'is_search' )->andReturn( false );
		WP_Mock::userFunction( 'is_archive' )->andReturn( false );
		WP_Mock::userFunction( 'is_home' )->andReturn( false );
		WP_Mock::userFunction( 'is_404' )->andReturn( false );

		WP_Mock::onFilter( 'wp_schema_framework_context' )
			->with( 'unknown' )
			->reply( 'custom_context' );

		$this->assertSame( 'custom_context', $this->detector->get_current_context() );
	}

	public function test_get_current_context_detects_attachment(): void {
		WP_Mock::userFunction( 'is_front_page' )->andReturn( false );
		WP_Mock::userFunction( 'is_attachment' )->andReturn( true );

		WP_Mock::onFilter( 'wp_schema_framework_context' )
			->with( 'attachment' )
			->reply( 'attachment' );

		$this->assertSame( 'attachment', $this->detector->get_current_context() );
	}
}
