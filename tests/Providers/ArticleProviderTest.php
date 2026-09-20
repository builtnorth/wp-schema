<?php
/**
 * Tests for ArticleProvider password-protected wordCount gating.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\ArticleProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class ArticleProviderTest extends TestCase {

	private ArticleProvider $provider;

	public function setUp(): void {
		parent::setUp();
		$this->provider = new ArticleProvider();
		$this->setUpCommonMocks();
	}

	private function queried_post( bool $password_required ): \WP_Post {
		$post = new \WP_Post(
			[
				'ID'           => 42,
				'post_type'    => 'post',
				'post_name'    => 'secret',
				'post_title'   => 'Secret Post',
				'post_content' => str_repeat( 'word ', 40 ),
				'post_excerpt' => '',
				'post_author'  => 0,
			]
		);

		WP_Mock::userFunction( 'get_queried_object' )->andReturn( $post );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( 'https://example.com/secret/' );
		WP_Mock::userFunction( 'get_the_date' )->andReturn( '2026-01-01T00:00:00+00:00' );
		WP_Mock::userFunction( 'get_the_modified_date' )->andReturn( '2026-01-02T00:00:00+00:00' );
		WP_Mock::userFunction( 'get_post_thumbnail_id' )->andReturn( 0 );
		WP_Mock::userFunction( 'get_the_tags' )->andReturn( false );
		WP_Mock::userFunction( 'get_the_category' )->andReturn( [] );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnUsing( static fn( $s ) => $s );
		WP_Mock::userFunction( 'post_password_required' )->andReturn( $password_required );
		WP_Mock::userFunction( 'get_userdata' )->andReturn( false );

		WP_Mock::onFilter( 'wp_schema_framework_post_type_override' )
			->with( 'Article', 42, 'post', $post )
			->reply( 'Article' );

		return $post;
	}

	public function test_word_count_omitted_when_password_required(): void {
		$this->queried_post( true );

		$data = $this->provider->get_pieces( 'singular' )[0]->to_array();

		$this->assertArrayNotHasKey( 'wordCount', $data );
	}

	public function test_word_count_present_when_public(): void {
		$this->queried_post( false );

		$data = $this->provider->get_pieces( 'singular' )[0]->to_array();

		$this->assertArrayHasKey( 'wordCount', $data );
		$this->assertGreaterThan( 0, $data['wordCount'] );
	}
}
