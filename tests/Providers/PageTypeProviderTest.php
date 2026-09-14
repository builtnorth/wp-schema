<?php
/**
 * Tests for PageTypeProvider decorating the page node.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Providers\PageTypeProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class PageTypeProviderTest extends TestCase {

	private PageTypeProvider $provider;

	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( 'add_filter' )->andReturn( true );
		$this->provider = new PageTypeProvider();
		$this->setUpCommonMocks();
	}

	private function queried_post(): void {
		$post = new \WP_Post(
			[
				'ID'          => 42,
				'post_type'   => 'page',
				'post_name'   => 'contact-us',
				'post_author' => 7,
			]
		);

		WP_Mock::userFunction( 'get_post' )->andReturn( $post );
		WP_Mock::userFunction( 'get_permalink' )->andReturn( 'https://example.com/contact-us/' );

		// Reached by the PrivacyPolicyPage/TermsOfServicePage setters.
		WP_Mock::userFunction( 'get_the_modified_date' )->andReturn( '2026-01-02T00:00:00+00:00' );
		WP_Mock::userFunction( 'get_locale' )->andReturn( 'en_US' );
	}

	/**
	 * This provider contributes to WebPageProvider's node rather than emitting
	 * its own — two nodes describing one URL is exactly what the combined
	 * @type shape exists to avoid.
	 */
	public function test_emits_no_piece_of_its_own(): void {
		$this->assertFalse( $this->provider->can_provide( 'singular' ) );
		$this->assertSame( [], $this->provider->get_pieces( 'singular' ) );
	}

	/**
	 * The subtype is read off the node's @type, so this provider and
	 * WebPageProvider can never disagree about which page this is.
	 */
	public function test_decorates_a_node_carrying_a_supported_subtype(): void {
		$this->queried_post();

		$page = new SchemaPiece( 'https://example.com/contact-us/', [ 'WebPage', 'ContactPage' ], [], 'webpage' );

		$decorated = $this->provider->decorate_page_node( $page, 'singular' );
		$data      = $decorated->to_array();

		$this->assertSame( 'CommunicateAction', $data['potentialAction']['@type'] );
		$this->assertSame( 'https://example.com/#organization', $data['contactPoint']['@id'] );
	}

	/**
	 * An ordinary WebPage must come back untouched.
	 */
	public function test_leaves_a_plain_page_node_alone(): void {
		$this->queried_post();

		$page = new SchemaPiece( 'https://example.com/contact-us/', 'WebPage', [], 'webpage' );

		$data = $this->provider->decorate_page_node( $page, 'singular' )->to_array();

		$this->assertArrayNotHasKey( 'potentialAction', $data );
		$this->assertArrayNotHasKey( 'contactPoint', $data );
	}

	/**
	 * url/name/dates/author/publisher belong to WebPageProvider. Re-setting them
	 * here would overwrite its values — and for `author` would replace the
	 * reference to the #author node with an inline blob, severing the link.
	 */
	public function test_does_not_overwrite_properties_owned_by_webpage_provider(): void {
		$this->queried_post();

		$page = new SchemaPiece(
			'https://example.com/contact-us/',
			[ 'WebPage', 'ContactPage' ],
			[
				'name'   => 'Owned by WebPageProvider',
				'author' => [ '@id' => '#author' ],
			],
			'webpage'
		);

		$data = $this->provider->decorate_page_node( $page, 'singular' )->to_array();

		$this->assertSame( 'Owned by WebPageProvider', $data['name'] );
		$this->assertSame( [ '@id' => '#author' ], $data['author'] );
	}

	/**
	 * A consumer filtering wp_schema_framework_webpage_data may point the page
	 * at a specific entity before this provider runs — e.g. a primary storefront
	 * location, whose business data lives on the organization node rather than a
	 * LocalBusiness node of its own. That decision is made with more context
	 * than this provider has, so it must survive decoration.
	 */
	public function test_does_not_overwrite_an_about_set_by_a_consumer(): void {
		$this->queried_post();

		$page = new SchemaPiece(
			'https://example.com/contact-us/',
			[ 'WebPage', 'AboutPage' ],
			[ 'about' => [ '@id' => 'https://example.com/#some-other-entity' ] ],
			'webpage'
		);

		$data = $this->provider->decorate_page_node( $page, 'singular' )->to_array();

		$this->assertSame( [ '@id' => 'https://example.com/#some-other-entity' ], $data['about'] );
	}

	/**
	 * With nothing claiming it, AboutPage still points at the organization.
	 */
	public function test_sets_about_when_nothing_claimed_it(): void {
		$this->queried_post();

		$page = new SchemaPiece( 'https://example.com/contact-us/', [ 'WebPage', 'AboutPage' ], [], 'webpage' );

		$data = $this->provider->decorate_page_node( $page, 'singular' )->to_array();

		$this->assertSame( [ '@id' => 'https://example.com/#organization' ], $data['about'] );
	}

	/**
	 * WebPageProvider sets inLanguage from get_bloginfo('language'); the subtype
	 * setters use get_locale(), a different value. Whoever owns the node wins.
	 */
	public function test_does_not_overwrite_in_language(): void {
		$this->queried_post();

		$page = new SchemaPiece(
			'https://example.com/contact-us/',
			[ 'WebPage', 'PrivacyPolicyPage' ],
			[ 'inLanguage' => 'en-GB' ],
			'webpage'
		);

		$data = $this->provider->decorate_page_node( $page, 'singular' )->to_array();

		$this->assertSame( 'en-GB', $data['inLanguage'] );
	}

	/**
	 * Nothing in this package emits a #main-content node, so referencing one
	 * guaranteed a dangling reference on every specialized page.
	 */
	public function test_never_references_main_content(): void {
		$this->queried_post();

		$page = new SchemaPiece( 'https://example.com/contact-us/', [ 'WebPage', 'ContactPage' ], [], 'webpage' );

		$data = $this->provider->decorate_page_node( $page, 'singular' )->to_array();

		$this->assertArrayNotHasKey( 'mainEntity', $data );
	}

	/**
	 * The filter contract passes whatever is in the pieces array; a non-piece
	 * must come straight back rather than fatal.
	 */
	public function test_passes_through_a_non_piece(): void {
		$this->assertNull( $this->provider->decorate_page_node( null, 'singular' ) );
	}
}
