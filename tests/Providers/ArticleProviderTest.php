<?php
/**
 * Tests for ArticleProvider
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\ArticleProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;
use stdClass;

/**
 * ArticleProvider test class
 */
class ArticleProviderTest extends TestCase {

	private ArticleProvider $provider;

	public function setUp(): void {
		parent::setUp();
		$this->provider = new ArticleProvider();
	}

	/**
	 * Test can_provide returns true for singular posts
	 */
	public function test_can_provide_returns_true_for_singular_posts(): void {
		WP_Mock::userFunction( 'is_singular' )
			->with( 'post' )
			->andReturn( true );
		
		// Mock post object
		$post = new stdClass();
		$post->post_type = 'post';
		WP_Mock::userFunction( 'get_queried_object' )
			->andReturn( $post );

		$this->assertTrue( $this->provider->can_provide( 'singular' ) );
	}

	/**
	 * Test can_provide returns false for non-singular contexts
	 */
	public function test_can_provide_returns_false_for_non_singular(): void {
		$this->assertFalse( $this->provider->can_provide( 'home' ) );
		$this->assertFalse( $this->provider->can_provide( 'archive' ) );
		$this->assertFalse( $this->provider->can_provide( 'search' ) );
	}

	/**
	 * Test can_provide returns false for pages
	 */
	public function test_can_provide_returns_false_for_pages(): void {
		WP_Mock::userFunction( 'is_singular' )
			->with( 'post' )
			->andReturn( false );
		
		// Mock page object
		$page = new stdClass();
		$page->post_type = 'page';
		WP_Mock::userFunction( 'get_queried_object' )
			->andReturn( $page );

		$this->assertFalse( $this->provider->can_provide( 'singular' ) );
	}

	/**
	 * Test get_pieces returns article schema
	 */
	public function test_get_pieces_returns_article_schema(): void {
		// Create mock post
		$post = new stdClass();
		$post->ID = 123;
		$post->post_title = 'Test Article';
		$post->post_content = 'This is the article content.';
		$post->post_excerpt = 'Article excerpt';
		$post->post_author = 1;
		$post->post_date = '2024-01-15 10:00:00';
		$post->post_modified = '2024-01-16 11:00:00';
		$post->post_type = 'post';

		// Create mock author
		$author = new stdClass();
		$author->ID = 1;
		$author->display_name = 'John Doe';

		// Mock WordPress functions
		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 123 );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( 'https://example.com/test-article' );
		WP_Mock::userFunction( 'get_the_title' )->andReturn( 'Test Article' );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_the_excerpt' )->andReturn( 'Article excerpt' );
		WP_Mock::userFunction( 'get_the_post_thumbnail_url' )->andReturn( 'https://example.com/image.jpg' );
		WP_Mock::userFunction( 'wp_get_attachment_metadata' )->andReturn( [
			'width' => 1200,
			'height' => 630
		] );
		WP_Mock::userFunction( 'get_post_thumbnail_id' )->andReturn( 456 );
		WP_Mock::userFunction( 'get_userdata' )->andReturn( $author );
		WP_Mock::userFunction( 'get_author_posts_url' )->andReturn( 'https://example.com/author/john' );
		WP_Mock::userFunction( 'get_option' )->andReturn( 'Y-m-d\TH:i:sP' );
		WP_Mock::userFunction( 'get_post_time' )->andReturn( '2024-01-15T10:00:00+00:00' );
		WP_Mock::userFunction( 'get_the_modified_time' )->andReturn( '2024-01-16T11:00:00+00:00' );
		WP_Mock::userFunction( 'home_url' )->andReturn( 'https://example.com' );

		// Mock filter
		WP_Mock::expectFilter( 'wp_schema_framework_article_data', \WP_Mock\Functions::type( 'array' ), 'singular' );

		$pieces = $this->provider->get_pieces( 'singular' );

		$this->assertIsArray( $pieces );
		$this->assertCount( 1, $pieces );

		$article = $pieces[0];
		$this->assertEquals( 'Article', $article['@type'] );
		$this->assertEquals( 'https://example.com/test-article#article', $article['@id'] );
		$this->assertEquals( 'Test Article', $article['headline'] );
		$this->assertEquals( 'Article excerpt', $article['description'] );
		$this->assertArrayHasKey( 'author', $article );
		$this->assertArrayHasKey( 'datePublished', $article );
		$this->assertArrayHasKey( 'dateModified', $article );
		$this->assertArrayHasKey( 'image', $article );
	}

	/**
	 * Test get_pieces applies post type override filter
	 */
	public function test_get_pieces_applies_post_type_override(): void {
		// Create mock post
		$post = new stdClass();
		$post->ID = 123;
		$post->post_title = 'Test News';
		$post->post_type = 'post';
		$post->post_content = 'Content';
		$post->post_excerpt = 'Excerpt';
		$post->post_author = 1;
		$post->post_date = '2024-01-15 10:00:00';
		$post->post_modified = '2024-01-16 11:00:00';

		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_the_ID' )->andReturn( 123 );

		// Mock the override filter to change type to NewsArticle
		WP_Mock::onFilter( 'wp_schema_framework_post_type_override' )
			->with( 'Article', 123, 'post', $post )
			->reply( 'NewsArticle' );

		// Mock other required functions
		$this->setUpArticleMocks();

		WP_Mock::expectFilter( 'wp_schema_framework_article_data', \WP_Mock\Functions::type( 'array' ), 'singular' );

		$pieces = $this->provider->get_pieces( 'singular' );

		$this->assertNotEmpty( $pieces, 'Pieces array should not be empty' );
		$this->assertEquals( 'NewsArticle', $pieces[0]['@type'] );
	}

	/**
	 * Test get_priority returns correct priority
	 */
	public function test_get_priority_returns_correct_priority(): void {
		$this->assertEquals( 20, $this->provider->get_priority() );
	}

	/**
	 * Helper to set up common article mocks
	 */
	private function setUpArticleMocks(): void {
		WP_Mock::userFunction( 'get_permalink' )->andReturn( 'https://example.com/article' );
		WP_Mock::userFunction( 'get_the_title' )->andReturn( 'Title' );
		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_the_excerpt' )->andReturn( 'Excerpt' );
		WP_Mock::userFunction( 'get_the_post_thumbnail_url' )->andReturn( false );
		WP_Mock::userFunction( 'get_userdata' )->andReturn( false );
		WP_Mock::userFunction( 'get_option' )->andReturn( 'Y-m-d\TH:i:sP' );
		WP_Mock::userFunction( 'get_post_time' )->andReturn( '2024-01-15T10:00:00+00:00' );
		WP_Mock::userFunction( 'get_the_modified_time' )->andReturn( '2024-01-16T11:00:00+00:00' );
		WP_Mock::userFunction( 'home_url' )->andReturn( 'https://example.com' );
	}
}