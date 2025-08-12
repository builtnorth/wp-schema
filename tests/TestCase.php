<?php
/**
 * Base test case for WP Schema
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests;

use WP_Mock;
use WP_Mock\Tools\TestCase as BaseTestCase;

/**
 * Base test case class
 */
abstract class TestCase extends BaseTestCase {

	/**
	 * Set up before each test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * Tear down after each test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Set up common WordPress function mocks
	 *
	 * @return void
	 */
	protected function setUpCommonMocks(): void {
		WP_Mock::userFunction( 'home_url' )
			->andReturnUsing( function( $path = '' ) {
				return 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
			} );

		WP_Mock::userFunction( 'get_bloginfo' )
			->andReturnUsing( function( $show = '' ) {
				$info = [
					'name' => 'Test Site',
					'description' => 'Just another WordPress site',
					'url' => 'https://example.com',
					'wpurl' => 'https://example.com',
				];
				return $info[ $show ] ?? 'Test Site';
			} );

		WP_Mock::userFunction( 'get_option' )
			->andReturnUsing( function( $option, $default = false ) {
				$options = [
					'date_format' => 'Y-m-d',
					'time_format' => 'H:i:s',
				];
				return $options[ $option ] ?? $default;
			} );
	}
}