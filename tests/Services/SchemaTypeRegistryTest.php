<?php
/**
 * Tests for SchemaTypeRegistry service
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\SchemaTypeRegistry;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

/**
 * SchemaTypeRegistry test class
 */
class SchemaTypeRegistryTest extends TestCase {

	private SchemaTypeRegistry $registry;

	public function setUp(): void {
		parent::setUp();
		$this->registry = new SchemaTypeRegistry();
	}

	/**
	 * Test get_available_types returns array with correct structure
	 */
	public function test_get_available_types_returns_correct_structure(): void {
		WP_Mock::onFilter( 'wp_schema_framework_type_registry_types' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $types ) {
				return $types;
			} );

		$types = $this->registry->get_available_types();

		$this->assertIsArray( $types );
		$this->assertNotEmpty( $types );
		
		// Check structure of first item
		$first_type = $types[0];
		$this->assertArrayHasKey( 'label', $first_type );
		$this->assertArrayHasKey( 'value', $first_type );
		$this->assertIsString( $first_type['label'] );
		$this->assertIsString( $first_type['value'] );
	}

	/**
	 * Test get_available_types includes common schema types
	 */
	public function test_get_available_types_includes_common_types(): void {
		WP_Mock::onFilter( 'wp_schema_framework_type_registry_types' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $types ) {
				return $types;
			} );

		$types = $this->registry->get_available_types();
		$values = array_column( $types, 'value' );

		// Check for common types
		$common_types = [
			'Article',
			'BlogPosting',
			'WebPage',
			'Product',
			'LocalBusiness',
			'Event',
			'Person',
			'Organization',
			'VideoObject',
			'ImageObject'
		];

		foreach ( $common_types as $type ) {
			$this->assertContains( $type, $values, "Missing common type: $type" );
		}
	}

	/**
	 * Test get_available_types can be filtered
	 */
	public function test_get_available_types_can_be_filtered(): void {
		// Mock apply_filters to pass through the default types
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_schema_framework_type_registry_types', \WP_Mock\Functions::type( 'array' ) )
			->once()
			->andReturnArg( 1 );

		$types = $this->registry->get_available_types();

		// At minimum, verify we get an array with expected structure
		$this->assertIsArray( $types );
		$this->assertNotEmpty( $types );
	}

	/**
	 * Test get_post_type_mappings returns correct mappings
	 */
	public function test_get_post_type_mappings_returns_correct_mappings(): void {
		WP_Mock::onFilter( 'wp_schema_framework_post_type_mappings' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $mappings ) {
				return $mappings;
			} );

		$mappings = $this->registry->get_post_type_mappings();

		$this->assertIsArray( $mappings );
		$this->assertArrayHasKey( 'post', $mappings );
		$this->assertArrayHasKey( 'page', $mappings );
		$this->assertEquals( 'Article', $mappings['post'] );
		$this->assertEquals( 'WebPage', $mappings['page'] );
	}

	/**
	 * Test get_post_type_mappings can be filtered
	 */
	public function test_get_post_type_mappings_can_be_filtered(): void {
		// Mock apply_filters to pass through the default mappings
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wp_schema_framework_post_type_mappings', \WP_Mock\Functions::type( 'array' ) )
			->once()
			->andReturnArg( 1 );

		$mappings = $this->registry->get_post_type_mappings();

		// Verify we get the default mappings
		$this->assertIsArray( $mappings );
		$this->assertArrayHasKey( 'post', $mappings );
		$this->assertArrayHasKey( 'page', $mappings );
	}

	/**
	 * Test get_schema_type_for_post_type returns correct type
	 */
	public function test_get_schema_type_for_post_type_returns_correct_type(): void {
		WP_Mock::onFilter( 'wp_schema_framework_post_type_mappings' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $mappings ) {
				return $mappings;
			} );

		$this->assertEquals( 'Article', $this->registry->get_schema_type_for_post_type( 'post' ) );
		$this->assertEquals( 'WebPage', $this->registry->get_schema_type_for_post_type( 'page' ) );
		$this->assertEquals( 'Product', $this->registry->get_schema_type_for_post_type( 'product' ) );
	}

	/**
	 * Test get_schema_type_for_post_type returns default for unknown types
	 */
	public function test_get_schema_type_for_post_type_returns_default(): void {
		WP_Mock::onFilter( 'wp_schema_framework_post_type_mappings' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $mappings ) {
				return $mappings;
			} );

		$this->assertEquals( 'Article', $this->registry->get_schema_type_for_post_type( 'unknown_type' ) );
	}

	/**
	 * Test is_valid_type returns true for valid types
	 */
	public function test_is_valid_type_returns_true_for_valid_types(): void {
		WP_Mock::onFilter( 'wp_schema_framework_type_registry_types' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $types ) {
				return $types;
			} );

		$this->assertTrue( $this->registry->is_valid_type( 'Article' ) );
		$this->assertTrue( $this->registry->is_valid_type( 'Product' ) );
		$this->assertTrue( $this->registry->is_valid_type( 'WebPage' ) );
	}

	/**
	 * Test is_valid_type returns false for invalid types
	 */
	public function test_is_valid_type_returns_false_for_invalid_types(): void {
		WP_Mock::onFilter( 'wp_schema_framework_type_registry_types' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->reply( function( $types ) {
				return $types;
			} );

		$this->assertFalse( $this->registry->is_valid_type( 'InvalidType' ) );
		$this->assertFalse( $this->registry->is_valid_type( 'NotASchemaType' ) );
	}
}