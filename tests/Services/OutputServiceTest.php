<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests\Services;

use BuiltNorth\WPSchema\Services\OutputService;
use BuiltNorth\WPSchema\Services\GraphBuilder;
use BuiltNorth\WPSchema\Services\ContextDetector;
use BuiltNorth\WPSchema\Graph\SchemaGraph;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use BuiltNorth\WPSchema\Tests\TestCase;
use WP_Mock;
use Mockery;

/**
 * Tests for OutputService — focused on JSON-LD escaping
 */
class OutputServiceTest extends TestCase
{
    /**
     * Test JSON output escapes </script> sequences (JSON_HEX_TAG)
     */
    public function test_json_output_escapes_script_closing_tag(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        $piece->set('name', 'Title with </script> tag');

        $graph = Mockery::mock(SchemaGraph::class);
        $graph->shouldReceive('is_empty')->andReturn(false);
        $graph->shouldReceive('get_pieces')->andReturn([$piece]);

        $graph_builder = Mockery::mock(GraphBuilder::class);
        $graph_builder->shouldReceive('build_for_context')->andReturn($graph);

        $context_detector = Mockery::mock(ContextDetector::class);
        $context_detector->shouldReceive('get_current_context')->andReturn('singular');
        $context_detector->shouldReceive('should_generate_schema')->with('singular')->andReturn(true);

        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('apply_filters')
            ->andReturnUsing(function ($hook, $value) {
                return $value;
            });

        $service = new OutputService($graph_builder, $context_detector);

        ob_start();
        $service->output_schema();
        $output = ob_get_clean();

        // Extract just the JSON content (between the script tags, before the closing </script>)
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);
        $json_content = $matches[1] ?? '';

        // The literal </script> sequence must not appear unescaped inside the JSON content
        $this->assertStringNotContainsString('</script>', $json_content, 'Raw </script> must be escaped inside JSON-LD content');
        // JSON_HEX_TAG encodes < as < and > as >
        $this->assertStringContainsString('\u003C', $json_content, 'JSON_HEX_TAG should encode < as \u003C');
    }

    /**
     * Test JSON output escapes ampersands (JSON_HEX_AMP)
     */
    public function test_json_output_escapes_ampersands(): void
    {
        $piece = new SchemaPiece('#test', 'Organization');
        $piece->set('name', 'Foo & Bar');

        $graph = Mockery::mock(SchemaGraph::class);
        $graph->shouldReceive('is_empty')->andReturn(false);
        $graph->shouldReceive('get_pieces')->andReturn([$piece]);

        $graph_builder = Mockery::mock(GraphBuilder::class);
        $graph_builder->shouldReceive('build_for_context')->andReturn($graph);

        $context_detector = Mockery::mock(ContextDetector::class);
        $context_detector->shouldReceive('get_current_context')->andReturn('home');
        $context_detector->shouldReceive('should_generate_schema')->with('home')->andReturn(true);

        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('apply_filters')
            ->andReturnUsing(function ($hook, $value) {
                return $value;
            });

        $service = new OutputService($graph_builder, $context_detector);

        ob_start();
        $service->output_schema();
        $output = ob_get_clean();

        $this->assertStringContainsString('\u0026', $output, 'JSON_HEX_AMP should encode & as \u0026');
    }

    /**
     * Test output is wrapped in correct script tag
     */
    public function test_output_wrapped_in_ld_json_script_tag(): void
    {
        $piece = new SchemaPiece('#test', 'WebSite');
        $piece->set('name', 'Test');

        $graph = Mockery::mock(SchemaGraph::class);
        $graph->shouldReceive('is_empty')->andReturn(false);
        $graph->shouldReceive('get_pieces')->andReturn([$piece]);

        $graph_builder = Mockery::mock(GraphBuilder::class);
        $graph_builder->shouldReceive('build_for_context')->andReturn($graph);

        $context_detector = Mockery::mock(ContextDetector::class);
        $context_detector->shouldReceive('get_current_context')->andReturn('home');
        $context_detector->shouldReceive('should_generate_schema')->with('home')->andReturn(true);

        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('apply_filters')
            ->andReturnUsing(function ($hook, $value) {
                return $value;
            });

        $service = new OutputService($graph_builder, $context_detector);

        ob_start();
        $service->output_schema();
        $output = ob_get_clean();

        $this->assertStringContainsString('<script type="application/ld+json">', $output);
        $this->assertStringContainsString('</script>', $output);
    }

    /**
     * Test nothing is output when graph is empty
     */
    public function test_no_output_when_graph_empty(): void
    {
        $graph = Mockery::mock(SchemaGraph::class);
        $graph->shouldReceive('is_empty')->andReturn(true);

        $graph_builder = Mockery::mock(GraphBuilder::class);
        $graph_builder->shouldReceive('build_for_context')->andReturn($graph);

        $context_detector = Mockery::mock(ContextDetector::class);
        $context_detector->shouldReceive('get_current_context')->andReturn('home');
        $context_detector->shouldReceive('should_generate_schema')->with('home')->andReturn(true);

        WP_Mock::userFunction('do_action')->andReturn(null);
        WP_Mock::userFunction('apply_filters')->andReturn(true);

        $service = new OutputService($graph_builder, $context_detector);

        ob_start();
        $service->output_schema();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
