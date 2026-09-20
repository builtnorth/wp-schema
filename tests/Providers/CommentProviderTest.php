<?php
/**
 * Tests for CommentProvider text truncation.
 *
 * @package BuiltNorth\WPSchema\Tests
 */

namespace BuiltNorth\WPSchema\Tests\Providers;

use BuiltNorth\WPSchema\Providers\CommentProvider;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

class CommentProviderTest extends TestCase {

	public function test_truncate_short_text_unchanged(): void {
		$this->assertSame( 'Hello world', CommentProvider::truncate_comment_text( 'Hello world' ) );
	}

	public function test_truncate_long_text_adds_ellipsis(): void {
		$long = str_repeat( 'a', 300 );
		$out  = CommentProvider::truncate_comment_text( $long );

		$this->assertSame( 281, mb_strlen( $out ) ); // 280 + ellipsis
		$this->assertStringEndsWith( '…', $out );
		$this->assertStringStartsWith( str_repeat( 'a', 280 ), $out );
	}

	public function test_truncate_collapses_whitespace(): void {
		$this->assertSame( 'a b c', CommentProvider::truncate_comment_text( "a\n\n  b\t c" ) );
	}

	public function test_truncate_max_length_filter_zero_keeps_full(): void {
		WP_Mock::onFilter( 'wp_schema_framework_comment_text_max_length' )
			->with( 280 )
			->reply( 0 );

		$long = str_repeat( 'b', 500 );
		$this->assertSame( $long, CommentProvider::truncate_comment_text( $long ) );
	}
}
