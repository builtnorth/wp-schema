<?php
/**
 * Tests for SearchResultsProvider highlight regex escaping.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\SearchResultsProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use ReflectionMethod;

class SearchResultsProviderTest extends TestCase {

	public function test_highlight_tolerates_slash_in_query(): void {
		$provider = new SearchResultsProvider();
		$method   = new ReflectionMethod( SearchResultsProvider::class, 'highlight_search_terms' );
		$method->setAccessible( true );

		$result = $method->invoke( $provider, 'See foo/bar docs', 'foo/bar' );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '<mark>', $result );
		$this->assertStringContainsString( 'foo/bar', $result );
	}
}
