<?php
/**
 * Tests for the public Schema facade.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests;

use BuiltNorth\WPSchema\Schema;
use WP_Mock;

class SchemaTest extends TestCase {

	/**
	 * Callers that arrive before wp_schema_framework_register_providers fires
	 * must defer, so their provider is picked up when the action runs.
	 */
	public function test_register_provider_defers_when_action_has_not_fired(): void {
		WP_Mock::userFunction( 'did_action' )
			->with( 'wp_schema_framework_register_providers' )
			->andReturn( 0 );

		WP_Mock::expectActionAdded(
			'wp_schema_framework_register_providers',
			WP_Mock\Functions::type( 'callable' )
		);

		Schema::registerProvider( 'deferred_provider', \stdClass::class );

		$this->assertConditionsMet();
	}

	/**
	 * Callers on init (or later) arrive after the action has already fired.
	 * Deferring would attach to a hook that never runs again, silently
	 * dropping the provider — so registration has to happen immediately.
	 */
	public function test_register_provider_registers_immediately_when_action_already_fired(): void {
		WP_Mock::userFunction( 'did_action' )
			->with( 'wp_schema_framework_register_providers' )
			->andReturn( 1 );

		// No add_action() expectation: deferring here would lose the provider.
		Schema::registerProvider( 'late_provider', \stdClass::class );

		$this->assertConditionsMet();
	}
}
