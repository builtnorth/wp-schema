<?php
/**
 * Tests for GraphInspector.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\GraphInspector;
use BuiltNorth\WPSchema\Tests\TestCase;

class GraphInspectorTest extends TestCase {

	private const PAGE = 'https://example.com/hello-world/';

	private GraphInspector $inspector;

	public function setUp(): void {
		parent::setUp();
		$this->inspector = new GraphInspector();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function graph( array ...$nodes ): array {
		return [
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		];
	}

	public function test_a_well_formed_singular_graph_has_no_findings(): void {
		$result = $this->inspector->inspect(
			$this->graph(
				[ '@type' => 'Organization', '@id' => 'https://example.com/#organization', 'name' => 'Acme' ],
				[ '@type' => 'WebSite', '@id' => 'https://example.com/#website', 'publisher' => [ '@id' => 'https://example.com/#organization' ] ],
				[ '@type' => 'WebPage', '@id' => self::PAGE, 'isPartOf' => [ '@id' => 'https://example.com/#website' ] ],
				[
					'@type'            => 'Article',
					'@id'              => self::PAGE . '#article',
					'isPartOf'         => [ '@id' => self::PAGE ],
					'mainEntityOfPage' => [ '@id' => self::PAGE ],
					'publisher'        => [ '@id' => 'https://example.com/#organization' ],
				]
			),
			'singular',
			self::PAGE
		);

		$this->assertSame( [], $result['errors'] );
		$this->assertSame( [], $result['warnings'] );
		$this->assertSame( 5, $result['references'] );
		$this->assertSame( [ 'Organization', 'WebSite', 'WebPage', 'Article' ], array_column( $result['nodes'], 'type' ) );
	}

	/**
	 * References written with set()/from_array() never pass through
	 * add_reference(), so they must be found by walking the output itself —
	 * however deeply nested.
	 */
	public function test_dangling_reference_is_reported_with_its_path(): void {
		$result = $this->inspector->inspect(
			$this->graph(
				[ '@type' => 'WebPage', '@id' => self::PAGE ],
				[
					'@type'  => 'Product',
					'@id'    => self::PAGE . '#product',
					'offers' => [ [ '@type' => 'Offer', 'seller' => [ '@id' => '#organization' ] ] ],
				]
			),
			'singular',
			self::PAGE
		);

		$this->assertSame(
			[ 'Product.offers[0].seller → "#organization" does not resolve to a node in this graph.' ],
			$result['errors']
		);
	}

	public function test_missing_page_node_is_an_error(): void {
		$result = $this->inspector->inspect(
			$this->graph( [ '@type' => 'Article', '@id' => self::PAGE . '#article' ] ),
			'singular',
			self::PAGE
		);

		$this->assertContains( 'No page node with @id "' . self::PAGE . '".', $result['errors'] );
	}

	/**
	 * Before the page/entity split, the "page" node carried the post's own
	 * type. Flag it so a regression is visible, but as a warning: the graph is
	 * still internally consistent.
	 */
	public function test_page_node_with_a_non_page_type_is_a_warning(): void {
		$result = $this->inspector->inspect(
			$this->graph( [ '@type' => 'Hotel', '@id' => self::PAGE ] ),
			'singular',
			self::PAGE
		);

		$this->assertSame( [], $result['errors'] );
		$this->assertSame( [ 'Page node "' . self::PAGE . '" is typed Hotel, not a *Page type.' ], $result['warnings'] );
	}

	public function test_archive_needs_some_page_typed_node(): void {
		$ok = $this->inspector->inspect(
			$this->graph( [ '@type' => 'CollectionPage', '@id' => 'https://example.com/locations/' ] ),
			'archive'
		);
		$this->assertSame( [], $ok['errors'] );

		$missing = $this->inspector->inspect(
			$this->graph( [ '@type' => 'ItemList', '@id' => 'https://example.com/locations/#list' ] ),
			'archive'
		);
		$this->assertSame( [ 'No *Page node for archive context.' ], $missing['errors'] );
	}

	public function test_multi_typed_nodes_are_labelled_and_recognised_as_pages(): void {
		$result = $this->inspector->inspect(
			$this->graph( [ '@type' => [ 'WebPage', 'FAQPage' ], '@id' => self::PAGE ] ),
			'singular',
			self::PAGE
		);

		$this->assertSame( [], $result['errors'] );
		$this->assertSame( 'WebPage/FAQPage', $result['nodes'][0]['type'] );
	}

	public function test_structural_problems_are_reported(): void {
		$result = $this->inspector->inspect(
			$this->graph(
				[ '@id' => 'https://example.com/#untyped' ],
				[ '@type' => 'Thing' ],
				[ '@type' => 'WebPage', '@id' => self::PAGE ],
				[ '@type' => 'Article', '@id' => self::PAGE ]
			),
			'singular',
			self::PAGE
		);

		$this->assertSame(
			[
				'Node #0 has no @type.',
				'Duplicate @id "' . self::PAGE . '" (WebPage and Article).',
			],
			$result['errors']
		);
		$this->assertSame( [ 'Node #1 (Thing) has no @id, so nothing can reference it.' ], $result['warnings'] );
	}

	public function test_empty_graph_is_an_error(): void {
		$result = $this->inspector->inspect( $this->graph(), 'home', 'https://example.com/' );

		$this->assertSame( [ 'Graph is empty.' ], $result['errors'] );
	}
}
