<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\ContextDetector;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;

/**
 * Tests for ContextDetector service
 */
class ContextDetectorTest extends TestCase
{
    private ContextDetector $detector;

    public function setUp(): void
    {
        parent::setUp();
        $this->detector = new ContextDetector();
    }

    /**
     * Test attachment context is returned for attachment pages (before singular)
     */
    public function test_attachment_context_detected(): void
    {
        WP_Mock::userFunction('is_front_page')->andReturn(false);
        WP_Mock::userFunction('is_attachment')->andReturn(true);
        WP_Mock::onFilter('wp_schema_framework_context')
            ->with('attachment')
            ->reply('attachment');

        $context = $this->detector->get_current_context();

        $this->assertSame('attachment', $context);
    }

    /**
     * Test singular context is not returned for attachments (attachment takes priority)
     */
    public function test_attachment_does_not_fall_through_to_singular(): void
    {
        WP_Mock::userFunction('is_front_page')->andReturn(false);
        WP_Mock::userFunction('is_attachment')->andReturn(true);
        WP_Mock::onFilter('wp_schema_framework_context')
            ->with('attachment')
            ->reply('attachment');

        $context = $this->detector->get_current_context();

        $this->assertNotSame('singular', $context);
    }

    /**
     * Test singular context for non-attachment singular pages
     */
    public function test_singular_context_for_posts(): void
    {
        WP_Mock::userFunction('is_front_page')->andReturn(false);
        WP_Mock::userFunction('is_attachment')->andReturn(false);
        WP_Mock::userFunction('is_singular')->andReturn(true);
        WP_Mock::onFilter('wp_schema_framework_context')
            ->with('singular')
            ->reply('singular');

        $context = $this->detector->get_current_context();

        $this->assertSame('singular', $context);
    }

    /**
     * Test that wp_schema_framework_context filter is applied
     */
    public function test_context_filter_is_applied(): void
    {
        WP_Mock::userFunction('is_front_page')->andReturn(true);
        WP_Mock::onFilter('wp_schema_framework_context')
            ->with('home')
            ->reply('custom_context');

        $context = $this->detector->get_current_context();

        $this->assertSame('custom_context', $context);
    }

    /**
     * Test that context filter receives the detected context as its argument
     */
    public function test_context_filter_receives_detected_context(): void
    {
        WP_Mock::userFunction('is_front_page')->andReturn(false);
        WP_Mock::userFunction('is_attachment')->andReturn(false);
        WP_Mock::userFunction('is_singular')->andReturn(false);
        WP_Mock::userFunction('is_archive')->andReturn(false);
        WP_Mock::userFunction('is_home')->andReturn(false);
        WP_Mock::userFunction('is_search')->andReturn(false);
        WP_Mock::userFunction('is_404')->andReturn(false);

        WP_Mock::onFilter('wp_schema_framework_context')
            ->with('unknown')
            ->reply('unknown');

        $context = $this->detector->get_current_context();

        $this->assertSame('unknown', $context);
    }

    /**
     * Test 404 context
     */
    public function test_404_context(): void
    {
        WP_Mock::userFunction('is_front_page')->andReturn(false);
        WP_Mock::userFunction('is_attachment')->andReturn(false);
        WP_Mock::userFunction('is_singular')->andReturn(false);
        WP_Mock::userFunction('is_archive')->andReturn(false);
        WP_Mock::userFunction('is_home')->andReturn(false);
        WP_Mock::userFunction('is_search')->andReturn(false);
        WP_Mock::userFunction('is_404')->andReturn(true);
        WP_Mock::onFilter('wp_schema_framework_context')
            ->with('404')
            ->reply('404');

        $context = $this->detector->get_current_context();

        $this->assertSame('404', $context);
    }

    /**
     * Test should_generate_schema returns false for 404 and unknown
     */
    public function test_should_not_generate_schema_for_404(): void
    {
        WP_Mock::onFilter('wp_schema_framework_output_enabled')
            ->with(true)
            ->reply(true);
        WP_Mock::userFunction('is_admin')->andReturn(false);
        WP_Mock::userFunction('is_feed')->andReturn(false);
        WP_Mock::userFunction('is_robots')->andReturn(false);
        WP_Mock::userFunction('is_trackback')->andReturn(false);

        $this->assertFalse($this->detector->should_generate_schema('404'));
        $this->assertFalse($this->detector->should_generate_schema('unknown'));
    }

    /**
     * Test should_generate_schema returns true for attachment context
     */
    public function test_should_generate_schema_for_attachment(): void
    {
        WP_Mock::onFilter('wp_schema_framework_output_enabled')
            ->with(true)
            ->reply(true);
        WP_Mock::userFunction('is_admin')->andReturn(false);
        WP_Mock::userFunction('is_feed')->andReturn(false);
        WP_Mock::userFunction('is_robots')->andReturn(false);
        WP_Mock::userFunction('is_trackback')->andReturn(false);

        $this->assertTrue($this->detector->should_generate_schema('attachment'));
    }
}
