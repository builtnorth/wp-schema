<?php
/**
 * Tests for ProviderRegistry service
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\ProviderRegistry;
use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Tests\TestCase;
use Mockery;

/**
 * ProviderRegistry test class
 */
class ProviderRegistryTest extends TestCase {

	private ProviderRegistry $registry;

	public function setUp(): void {
		parent::setUp();
		$this->registry = new ProviderRegistry();
	}

	/**
	 * Test register adds provider
	 */
	public function test_register_adds_provider(): void {
		$provider = Mockery::mock( SchemaProviderInterface::class );
		
		$this->registry->register( 'test_provider', $provider );
		
		$names = $this->registry->get_provider_names();
		$this->assertContains( 'test_provider', $names );
	}

	/**
	 * Test register overwrites existing provider
	 */
	public function test_register_overwrites_existing_provider(): void {
		$provider1 = Mockery::mock( SchemaProviderInterface::class );
		$provider2 = Mockery::mock( SchemaProviderInterface::class );
		
		$this->registry->register( 'test_provider', $provider1 );
		$this->registry->register( 'test_provider', $provider2 );
		
		$names = $this->registry->get_provider_names();
		$this->assertCount( 1, array_filter( $names, fn($name) => $name === 'test_provider' ) );
	}

	/**
	 * Test get_providers_for_context returns matching providers
	 */
	public function test_get_providers_for_context_returns_matching_providers(): void {
		$provider1 = Mockery::mock( SchemaProviderInterface::class );
		$provider1->shouldReceive( 'can_provide' )
			->with( 'home' )
			->andReturn( true );
		$provider1->shouldReceive( 'get_priority' )
			->andReturn( 10 );

		$provider2 = Mockery::mock( SchemaProviderInterface::class );
		$provider2->shouldReceive( 'can_provide' )
			->with( 'home' )
			->andReturn( false );

		$provider3 = Mockery::mock( SchemaProviderInterface::class );
		$provider3->shouldReceive( 'can_provide' )
			->with( 'home' )
			->andReturn( true );
		$provider3->shouldReceive( 'get_priority' )
			->andReturn( 5 );

		$this->registry->register( 'provider1', $provider1 );
		$this->registry->register( 'provider2', $provider2 );
		$this->registry->register( 'provider3', $provider3 );

		$providers = $this->registry->get_providers_for_context( 'home' );

		$this->assertCount( 2, $providers );
		$this->assertContains( $provider1, $providers );
		$this->assertContains( $provider3, $providers );
		$this->assertNotContains( $provider2, $providers );
	}

	/**
	 * Test get_providers_for_context sorts by priority
	 */
	public function test_get_providers_for_context_sorts_by_priority(): void {
		$provider1 = Mockery::mock( SchemaProviderInterface::class );
		$provider1->shouldReceive( 'can_provide' )
			->with( 'home' )
			->andReturn( true );
		$provider1->shouldReceive( 'get_priority' )
			->andReturn( 20 );

		$provider2 = Mockery::mock( SchemaProviderInterface::class );
		$provider2->shouldReceive( 'can_provide' )
			->with( 'home' )
			->andReturn( true );
		$provider2->shouldReceive( 'get_priority' )
			->andReturn( 5 );

		$provider3 = Mockery::mock( SchemaProviderInterface::class );
		$provider3->shouldReceive( 'can_provide' )
			->with( 'home' )
			->andReturn( true );
		$provider3->shouldReceive( 'get_priority' )
			->andReturn( 10 );

		$this->registry->register( 'provider1', $provider1 );
		$this->registry->register( 'provider2', $provider2 );
		$this->registry->register( 'provider3', $provider3 );

		$providers = $this->registry->get_providers_for_context( 'home' );

		// Should be sorted by priority: 5, 10, 20
		$this->assertSame( $provider2, $providers[0] );
		$this->assertSame( $provider3, $providers[1] );
		$this->assertSame( $provider1, $providers[2] );
	}

	/**
	 * Test get_providers_for_context returns empty array when no matches
	 */
	public function test_get_providers_for_context_returns_empty_when_no_matches(): void {
		$provider = Mockery::mock( SchemaProviderInterface::class );
		$provider->shouldReceive( 'can_provide' )
			->with( 'search' )
			->andReturn( false );

		$this->registry->register( 'provider1', $provider );

		$providers = $this->registry->get_providers_for_context( 'search' );

		$this->assertIsArray( $providers );
		$this->assertEmpty( $providers );
	}

	/**
	 * Test get_provider_names returns all registered names
	 */
	public function test_get_provider_names_returns_all_names(): void {
		$provider1 = Mockery::mock( SchemaProviderInterface::class );
		$provider2 = Mockery::mock( SchemaProviderInterface::class );
		$provider3 = Mockery::mock( SchemaProviderInterface::class );

		$this->registry->register( 'provider1', $provider1 );
		$this->registry->register( 'provider2', $provider2 );
		$this->registry->register( 'provider3', $provider3 );

		$names = $this->registry->get_provider_names();

		$this->assertCount( 3, $names );
		$this->assertContains( 'provider1', $names );
		$this->assertContains( 'provider2', $names );
		$this->assertContains( 'provider3', $names );
	}

	/**
	 * Test get_provider_names returns empty array when no providers
	 */
	public function test_get_provider_names_returns_empty_when_no_providers(): void {
		$names = $this->registry->get_provider_names();

		$this->assertIsArray( $names );
		$this->assertEmpty( $names );
	}
}