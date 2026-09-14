<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\CLI;

use BuiltNorth\WPSchema\App;
use BuiltNorth\WPSchema\Services\GraphInspector;
use BuiltNorth\WPSchema\Services\SchemaIds;
use WP_CLI;
use WP_Post;

/**
 * Inspect the JSON-LD graph wp-schema would emit for a page.
 *
 * Builds the graph in-process by emulating a front-end request for the
 * path — through WordPress's own rewrite resolution — so the result is
 * exactly what wp_head would print, without an HTTP round trip and without
 * any page cache in the way.
 *
 * @since 1.4.0
 */
class SchemaCommand
{
    private const ALL_SKIP_POST_TYPES = [ 'attachment' ];

    /**
     * Check the schema graph for a page.
     *
     * Reports every node, confirms the page node is present, and fails on any
     * `{"@id": …}` reference that does not resolve to a node in the same graph.
     *
     * ## OPTIONS
     *
     * [<path>]
     * : Path relative to the home URL, e.g. `/about/` or `/?s=term`. Default `/`.
     * (A positional argument because `--url` and `--path` are WP-CLI globals.)
     *
     * [--post=<id>]
     * : Check a post by ID (its permalink is resolved for you).
     *
     * [--all]
     * : Check the home page, the newest published post of every public post
     * type, every post type archive, and the blog page.
     *
     * [--format=<format>]
     * : Output format.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp schema check /locations/acme-store/
     *     wp schema check --post=5151
     *     wp schema check --all
     *     wp schema check --all --format=json
     *
     * @param list<string>          $args
     * @param array<string, string> $assoc_args
     */
    public function check(array $args, array $assoc_args): void
    {
        $format  = $assoc_args['format'] ?? 'table';
        $targets = $this->resolve_targets($args, $assoc_args);
        $reports = [];
        $failed  = false;

        foreach ($targets as $path) {
            $report    = $this->check_path($path);
            $reports[] = $report;
            $failed    = $failed || $report['errors'] !== [];

            if ($format === 'table') {
                $this->print_report($report);
            }
        }

        if ($format === 'json') {
            WP_CLI::line((string) wp_json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif (! $failed) {
            WP_CLI::success(sprintf('%d page%s checked, no problems found.', count($reports), count($reports) === 1 ? '' : 's'));
        }

        if ($failed) {
            WP_CLI::halt(1);
        }
    }

    /**
     * Print the JSON-LD for a page, exactly as wp_head would emit it.
     *
     * Handy for pasting into the Rich Results Test or the Schema.org validator
     * when the site is not publicly reachable.
     *
     * ## OPTIONS
     *
     * [<path>]
     * : Path relative to the home URL. Default `/`.
     *
     * [--post=<id>]
     * : Dump a post by ID.
     *
     * ## EXAMPLES
     *
     *     wp schema dump /
     *     wp schema dump --post=5151 > location.json
     *
     * @param list<string>          $args
     * @param array<string, string> $assoc_args
     */
    public function dump(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['all'])) {
            WP_CLI::error('dump takes a single path or --post.');
        }

        $path    = $this->resolve_targets($args, $assoc_args)[0];
        $app     = App::initialize();
        $context = $this->emulate_request($path);

        if (! $app->get_context_detector()->should_generate_schema($context)) {
            WP_CLI::error(sprintf('%s resolved to "%s" context; no schema is emitted there.', $path, $context));
        }

        $graph = $app->get_graph_builder()->build_for_context($context);
        $data  = $app->get_output_service()->get_graph_data($graph);

        WP_CLI::line((string) wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{
     *     path: string,
     *     context: string,
     *     queried: string,
     *     nodes: list<array{type: string, id: string, name: string}>,
     *     references: int,
     *     errors: list<string>,
     *     warnings: list<string>
     * }
     */
    private function check_path(string $path): array
    {
        $app     = App::initialize();
        $context = $this->emulate_request($path);
        $queried = $this->describe_queried_object();

        if (! $app->get_context_detector()->should_generate_schema($context)) {
            return [
                'path'       => $path,
                'context'    => $context,
                'queried'    => $queried,
                'nodes'      => [],
                'references' => 0,
                'errors'     => [ sprintf('Resolved to "%s" context; no schema is emitted there.', $context) ],
                'warnings'   => [],
            ];
        }

        $graph  = $app->get_graph_builder()->build_for_context($context);
        $data   = $app->get_output_service()->get_graph_data($graph);
        $result = (new GraphInspector())->inspect($data, $context, $this->expected_page_id($context));

        return array_merge(
            [
                'path'    => $path,
                'context' => $context,
                'queried' => $queried,
            ],
            $result
        );
    }

    /**
     * Run WordPress's own request resolution for a path, so conditionals,
     * the queried object and the static front page all behave as on a real
     * hit. Mirrors WP::main() minus the header side effects.
     */
    private function emulate_request(string $path): string
    {
        global $wp, $wp_query, $wp_the_query;

        $parts = wp_parse_url($path);

        $_GET = [];
        if (! empty($parts['query'])) {
            wp_parse_str($parts['query'], $_GET);
        }
        $_SERVER['REQUEST_URI'] = $path;
        unset($_SERVER['PATH_INFO']);

        $wp->parse_request();
        $wp->query_posts();
        $wp->handle_404();
        $wp->register_globals();

        // Some plugins swap $wp_query; make sure conditionals read the main query.
        $wp_query = $wp_the_query;

        $this->resolve_template();

        return App::instance()->get_context_detector()->get_current_context();
    }

    /**
     * Run the template hierarchy for the resolved query.
     *
     * A provider may look at the template to decide what to emit — whether a
     * block is on the page, say. That lives in $_wp_current_template_content,
     * which only core's template resolution populates, and which
     * template-loader.php normally fills in *after* the query is set up and
     * before wp_head runs. Without this the command builds its graph against an
     * empty template and disagrees with the real page.
     *
     * Mirrors the tag-to-function table in template-loader.php; core exposes no
     * single helper for "resolve the template for the current query".
     */
    private function resolve_template(): void
    {
        if (! current_theme_supports('block-templates')) {
            return;
        }

        $tag_templates = [
            'is_embed'             => 'get_embed_template',
            'is_404'               => 'get_404_template',
            'is_search'            => 'get_search_template',
            'is_front_page'        => 'get_front_page_template',
            'is_home'              => 'get_home_template',
            'is_privacy_policy'    => 'get_privacy_policy_template',
            'is_post_type_archive' => 'get_post_type_archive_template',
            'is_tax'               => 'get_taxonomy_template',
            'is_attachment'        => 'get_attachment_template',
            'is_single'            => 'get_single_template',
            'is_page'              => 'get_page_template',
            'is_singular'          => 'get_singular_template',
            'is_category'          => 'get_category_template',
            'is_tag'               => 'get_tag_template',
            'is_author'            => 'get_author_template',
            'is_date'              => 'get_date_template',
            'is_archive'           => 'get_archive_template',
        ];

        foreach ($tag_templates as $tag => $callback) {
            if (function_exists($tag) && call_user_func($tag) && function_exists($callback)) {
                call_user_func($callback);

                return;
            }
        }

        if (function_exists('get_index_template')) {
            get_index_template();
        }
    }

    private function expected_page_id(string $context): string
    {
        if ($context === 'home') {
            return SchemaIds::home_webpage_id();
        }

        if ($context === 'singular') {
            $post = get_queried_object();

            return $post instanceof WP_Post ? SchemaIds::webpage_id($post) : '';
        }

        return '';
    }

    private function describe_queried_object(): string
    {
        $object = get_queried_object();

        if ($object instanceof WP_Post) {
            return sprintf('%s #%d', $object->post_type, $object->ID);
        }

        if ($object instanceof \WP_Term) {
            return sprintf('%s "%s"', $object->taxonomy, $object->name);
        }

        if ($object instanceof \WP_Post_Type) {
            return sprintf('%s archive', $object->name);
        }

        if ($object instanceof \WP_User) {
            return sprintf('author %s', $object->user_nicename);
        }

        return '';
    }

    /**
     * @param list<string>          $args       Positional: optional path.
     * @param array<string, string> $assoc_args
     * @return list<string> Paths relative to the home URL.
     */
    private function resolve_targets(array $args, array $assoc_args): array
    {
        if (isset($assoc_args['all'])) {
            return $this->all_targets();
        }

        if (isset($assoc_args['post'])) {
            $post = get_post((int) $assoc_args['post']);
            if (! $post instanceof WP_Post) {
                WP_CLI::error(sprintf('Post %s not found.', $assoc_args['post']));
            }
            if ($post->post_status !== 'publish') {
                WP_CLI::error(sprintf('Post %d is "%s", not published; it has no public page.', $post->ID, $post->post_status));
            }

            return [ $this->to_path((string) get_permalink($post)) ];
        }

        return [ $this->to_path((string) ($args[0] ?? '/')) ];
    }

    /**
     * @return list<string>
     */
    private function all_targets(): array
    {
        $paths = [ '/' ];

        $blog_page = (int) get_option('page_for_posts');
        if ($blog_page > 0 && get_option('show_on_front') === 'page') {
            $paths[] = $this->to_path((string) get_permalink($blog_page));
        }

        foreach (get_post_types([ 'public' => true ], 'objects') as $post_type) {
            if (in_array($post_type->name, self::ALL_SKIP_POST_TYPES, true)) {
                continue;
            }

            $ids = get_posts([
                'post_type'      => $post_type->name,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);
            if ($ids !== []) {
                $paths[] = $this->to_path((string) get_permalink((int) $ids[0]));
            }

            if ($post_type->has_archive) {
                $archive = get_post_type_archive_link($post_type->name);
                if (is_string($archive) && $archive !== '') {
                    $paths[] = $this->to_path($archive);
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Absolute URL or bare path → path relative to the home URL, as WP's
     * request parser expects it.
     */
    private function to_path(string $url): string
    {
        $home = untrailingslashit(home_url());
        if (str_starts_with($url, $home)) {
            $url = substr($url, strlen($home));
        }

        $url = $url === '' ? '/' : $url;

        return str_starts_with($url, '/') ? $url : '/' . $url;
    }

    /**
     * @param array{
     *     path: string,
     *     context: string,
     *     queried: string,
     *     nodes: list<array{type: string, id: string, name: string}>,
     *     references: int,
     *     errors: list<string>,
     *     warnings: list<string>
     * } $report
     */
    private function print_report(array $report): void
    {
        $status  = $report['errors'] === [] ? '%G✔%n' : '%R✘%n';
        $queried = $report['queried'] !== '' ? ' (' . $report['queried'] . ')' : '';

        WP_CLI::line(WP_CLI::colorize(sprintf('%s %s  context=%s%s', $status, $report['path'], $report['context'], $queried)));

        if ($report['nodes'] !== []) {
            $types = array_count_values(array_column($report['nodes'], 'type'));
            $parts = [];
            foreach ($types as $type => $count) {
                $parts[] = $count > 1 ? $type . '×' . $count : $type;
            }
            WP_CLI::line(sprintf('    %d nodes: %s', count($report['nodes']), implode(', ', $parts)));
            WP_CLI::line(sprintf('    %d references, all resolve', $report['references']));
        }

        foreach ($report['warnings'] as $warning) {
            WP_CLI::line(WP_CLI::colorize('    %Ywarning:%n ' . $warning));
        }

        foreach ($report['errors'] as $error) {
            WP_CLI::line(WP_CLI::colorize('    %Rerror:%n ' . $error));
        }

        WP_CLI::line('');
    }
}
