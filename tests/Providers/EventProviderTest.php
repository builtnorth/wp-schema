<?php
/**
 * Tests for EventProvider @id conventions and page linkage.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\EventProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class EventProviderTest extends TestCase {

	private EventProvider $provider;

	public function setUp(): void {
		parent::setUp();
		$this->provider = new EventProvider();
		$this->setUpCommonMocks();
	}

	/**
	 * The provider reads event data through a filter before touching any event
	 * plugin, so a canned reply exercises get_pieces() with none installed.
	 */
	private function event_data_is( array $data ): void {
		WP_Mock::onFilter( 'wp_schema_framework_get_event_data' )
			->with( null, 42 )
			->reply( $data );
	}

	private function queried_post( string|false $permalink ): void {
		$post            = new \stdClass();
		$post->ID        = 42;
		$post->post_type = 'tribe_events';

		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 42 );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( $permalink );
		WP_Mock::userFunction( 'get_the_title' )->andReturn( 'Test Event' );
		WP_Mock::userFunction( 'has_post_thumbnail' )->andReturn( false );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnUsing( static fn( $s ) => $s );
	}

	/**
	 * A bare "#event" fragment resolves against whatever URL the consumer reads
	 * the graph from, so the entity @id has to be page-scoped.
	 */
	public function test_event_id_is_page_scoped(): void {
		$this->queried_post( 'https://example.com/thing/' );
		$this->event_data_is( [ 'name' => 'Test Event' ] );

		$pieces = $this->provider->get_pieces( 'singular' );

		$this->assertCount( 1, $pieces );
		$this->assertSame( 'https://example.com/thing/#event', $pieces[0]->get_id() );
	}

	/**
	 * eventType picks the node's @type, but must not change the @id shape.
	 */
	public function test_event_type_does_not_affect_the_id(): void {
		$this->queried_post( 'https://example.com/thing/' );
		$this->event_data_is( [ 'name' => 'Test Event', 'eventType' => 'MusicEvent' ] );

		$piece = $this->provider->get_pieces( 'singular' )[0];

		$this->assertSame( 'MusicEvent', $piece->get_type() );
		$this->assertSame( 'https://example.com/thing/#event', $piece->get_id() );
	}

	/**
	 * Without isPartOf/mainEntityOfPage the Event node is an island: the page
	 * and the entity both exist but nothing connects them.
	 */
	public function test_event_links_up_to_its_page(): void {
		$this->queried_post( 'https://example.com/thing/' );
		$this->event_data_is( [ 'name' => 'Test Event' ] );

		$data = $this->provider->get_pieces( 'singular' )[0]->to_array();

		$this->assertSame( [ '@id' => 'https://example.com/thing/' ], $data['isPartOf'] );
		$this->assertSame( [ '@id' => 'https://example.com/thing/' ], $data['mainEntityOfPage'] );
	}

	/**
	 * No permalink means no page node to hang the entity off, so emitting one
	 * would guarantee a dangling reference.
	 */
	public function test_emits_nothing_without_a_permalink(): void {
		$this->queried_post( false );
		$this->event_data_is( [ 'name' => 'Test Event' ] );

		$this->assertSame( [], $this->provider->get_pieces( 'singular' ) );
	}
}
