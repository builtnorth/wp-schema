<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Navigation Provider
 * 
 * Provides SiteNavigationElement schema for navigation menus.
 * Works with both FSE themes (core/navigation blocks) and classic themes.
 * 
 * @since 3.0.0
 */
class NavigationProvider implements SchemaProviderInterface
{
    public function __construct()
    {
        // WebsiteProvider is priority 5 and this provider is 15, so the WebSite
        // node is built before any navigation piece exists. Supply the ids from
        // the menus themselves rather than from built pieces, and register at
        // construction so the filter is in place before any provider runs.
        add_filter('wp_schema_framework_website_navigation_ids', [$this, 'filter_website_navigation_ids']);

        foreach ([ 'wp_update_nav_menu', 'wp_update_nav_menu_item', 'save_post_wp_navigation' ] as $hook) {
            add_action($hook, [ self::class, 'flush_cache' ]);
        }
    }

    /**
     * The @ids of the navigation nodes this provider will emit.
     *
     * Derived from the menus directly, so it does not depend on get_pieces()
     * having run — see the ordering note in the constructor.
     *
     * @param array<int, string> $ids
     * @return array<int, string>
     */
    public function filter_website_navigation_ids(array $ids): array
    {
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            foreach (get_posts([
                'post_type'   => 'wp_navigation',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]) as $navigation) {
                $slug  = sanitize_title($navigation->post_title ?: 'navigation-' . $navigation->ID);
                $ids[] = self::navigation_id($slug);
            }
        }

        $seen = [];

        foreach (get_nav_menu_locations() as $location => $menu_id) {
            $menu_id = (int) $menu_id;

            if ($menu_id <= 0 || isset($seen[$menu_id])) {
                continue;
            }

            $seen[$menu_id] = true;
            $ids[]          = self::navigation_id((string) $location);
        }

        return array_values(array_unique($ids));
    }

    public function can_provide(string $context): bool
    {
        // Navigation can appear on any page
        return true;
    }
    
    /**
     * Transient key. Versioned so a cache written by an older release — whose
     * payload shape may differ — is ignored rather than unserialised.
     */
    private const CACHE_KEY = 'wp_schema_nav_pieces_v2';

    /**
     * Menus rarely change and every page rebuilds them, so the result is
     * cached — but only as plain data. Caching SchemaPiece objects serialises a
     * class into the options table, so any later change to that class turns
     * stale rows into __PHP_Incomplete_Class on the next read.
     */
    public function get_pieces(string $context): array
    {
        $cached = get_transient(self::CACHE_KEY);

        if (is_array($cached)) {
            return $this->pieces_from_cache($cached);
        }

        $pieces = [];

        // Check if block theme
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            // For FSE themes, get wp_navigation posts
            $navigations = get_posts([
                'post_type' => 'wp_navigation',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]);

            foreach ($navigations as $navigation) {
                $piece = $this->create_navigation_from_post($navigation);
                if ($piece) {
                    $pieces[] = $piece;
                }
            }
        }

        // Classic / hybrid: only menus assigned to a theme location (not every unused menu).
        $locations = get_nav_menu_locations();
        $registered_menus = get_registered_nav_menus();
        $seen_menu_ids = [];

        foreach ($locations as $location => $menu_id) {
            $menu_id = (int) $menu_id;
            if ($menu_id <= 0 || isset($seen_menu_ids[ $menu_id ])) {
                continue;
            }
            $seen_menu_ids[ $menu_id ] = true;

            $menu = wp_get_nav_menu_object($menu_id);
            if (!$menu) {
                continue;
            }

            $menu_items = wp_get_nav_menu_items($menu_id);
            if (!$menu_items) {
                continue;
            }

            $piece = new SchemaPiece(self::navigation_id($location), 'SiteNavigationElement', [], "navigation-{$location}");
            $name = $registered_menus[$location] ?? ucfirst(str_replace('_', ' ', $location));
            $piece->set('name', $name);

            $schema_items = [];
            // Scoped per menu: the same destination appearing in two different
            // menus is meaningful, the same one twice in one menu is not.
            $seen_items = [];
            foreach ($menu_items as $item) {
                if ($item->menu_item_parent == 0 || $item->menu_item_parent == '0') {
                    $title = trim((string) $item->title);
                    if ($title === '') {
                        continue;
                    }
                    $url = self::absolute_url((string) $item->url);

                    // A menu may legitimately repeat a destination (a "Blog"
                    // link in two groups, say); repeating it in the graph adds
                    // no information and reads as noise to a consumer.
                    $seen_key = $title . '|' . $url;

                    if (isset($seen_items[$seen_key])) {
                        continue;
                    }

                    $seen_items[$seen_key] = true;

                    $schema_items[] = [
                        '@type' => 'SiteNavigationElement',
                        'name' => $title,
                        'url' => $url,
                    ];
                }
            }

            if (!empty($schema_items)) {
                $piece->set('hasPart', $schema_items);
                $pieces[] = $piece;
            }
        }

        set_transient(self::CACHE_KEY, $this->pieces_to_cache($pieces), 15 * MINUTE_IN_SECONDS);

        return $pieces;
    }

    /**
     * The @id for a navigation node.
     *
     * Site-scoped, like #organization and #website: one menu describes the whole
     * site, not the page it happens to be read from. A bare "#navigation-primary"
     * would resolve against each page's own URL and so name a different node on
     * every page.
     */
    public static function navigation_id(string $slug): string
    {
        return trailingslashit(home_url('/')) . '#navigation-' . $slug;
    }

    /**
     * Resolve a menu URL against the site root.
     *
     * Menu items may store a root-relative path ("/about"). In JSON-LD that
     * resolves against the document it appears in, so the same menu would point
     * at different targets depending on which page emitted it.
     */
    private static function absolute_url(string $url): string
    {
        $url = trim($url);

        if ($url === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '?')) {
            return $url;
        }

        return (string) home_url('/' . ltrim($url, '/'));
    }

    /**
     * @param SchemaPiece[] $pieces
     * @return array<int, array{id: string, name: string, data: array<string, mixed>}>
     */
    private function pieces_to_cache(array $pieces): array
    {
        $rows = [];

        foreach ($pieces as $piece) {
            $rows[] = [
                'id'   => $piece->get_id(),
                'name' => $piece->get_name(),
                'data' => $piece->to_array(),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, mixed> $rows
     * @return SchemaPiece[]
     */
    private function pieces_from_cache(array $rows): array
    {
        $pieces = [];

        foreach ($rows as $row) {
            // Anything not matching the current shape is treated as a miss
            // rather than trusted — the cache is a convenience, not a contract.
            if (!is_array($row) || !isset($row['id'], $row['data']) || !is_array($row['data'])) {
                return [];
            }

            $pieces[] = new SchemaPiece(
                (string) $row['id'],
                'SiteNavigationElement',
                $row['data'],
                isset($row['name']) ? (string) $row['name'] : null
            );
        }

        return $pieces;
    }

    /**
     * Drop the cache when menus change, so an edit is not invisible to search
     * engines for up to the cache lifetime.
     */
    public static function flush_cache(): void
    {
        delete_transient(self::CACHE_KEY);
    }
    
    public function get_priority(): int
    {
        return 15; // Lower priority than content
    }
    
    /**
     * Create navigation schema from wp_navigation post
     */
    private function create_navigation_from_post(\WP_Post $navigation): ?SchemaPiece
    {
        // Parse the navigation blocks
        $blocks = parse_blocks($navigation->post_content);
        
        if (empty($blocks)) {
            return null;
        }
        
        $menu_slug = sanitize_title($navigation->post_title ?: 'navigation-' . $navigation->ID);
        $piece = new SchemaPiece(self::navigation_id($menu_slug), 'SiteNavigationElement', [], "navigation-{$menu_slug}");
        $piece->set('name', $navigation->post_title ?: 'Navigation');
        
        // Extract menu items from blocks
        $schema_items = $this->extract_navigation_items($blocks);
        
        if (!empty($schema_items)) {
            $piece->set('hasPart', $schema_items);
            return $piece;
        }
        
        return null;
    }
    
    /**
     * Extract navigation items from blocks
     */
    private function extract_navigation_items(array $blocks): array
    {
        return self::dedupe_items($this->collect_navigation_items($blocks));
    }

    /**
     * Drop repeated destinations within one menu.
     *
     * A menu may list the same link twice (duplicated during editing, or the
     * same page reachable from two groups). Repeating it in the graph adds no
     * information, so keep the first occurrence only.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private static function dedupe_items(array $items): array
    {
        $seen = [];
        $out  = [];

        foreach ($items as $item) {
            $key = (string) ($item['name'] ?? '') . '|' . (string) ($item['url'] ?? '');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[]      = $item;
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @return array<int, array<string, mixed>>
     */
    private function collect_navigation_items(array $blocks): array
    {
        $items = [];
        
        foreach ($blocks as $block) {
            // Handle various navigation block types
            switch ($block['blockName']) {
                case 'core/navigation-link':
                    // Standard navigation link
                    $attrs = $block['attrs'] ?? [];
                    $label = $attrs['label'] ?? '';
                    $url = $attrs['url'] ?? '';
                    
                    if ($label && $url) {
                        $items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $label,
                            'url' => self::absolute_url((string) $url),
                        ];
                    }
                    break;

                case 'core/navigation-submenu':
                    // Submenu block - extract the parent item
                    $attrs = $block['attrs'] ?? [];
                    $label = $attrs['label'] ?? '';
                    $url = $attrs['url'] ?? '';
                    $type = $attrs['type'] ?? 'custom';
                    $id = $attrs['id'] ?? null;
                    
                    // Get URL based on type
                    if (empty($url) && $id) {
                        switch ($type) {
                            case 'post':
                            case 'page':
                                $url = get_permalink($id);
                                break;
                            case 'category':
                            case 'tag':
                            case 'taxonomy':
                                $url = get_term_link($id);
                                break;
                        }
                    }
                    
                    if ($label && $url && !is_wp_error($url)) {
                        $items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $label,
                            'url' => self::absolute_url((string) $url),
                        ];
                    }
                    
                    // Don't process innerBlocks for submenus - we only want top-level items
                    break;
                    
                case 'core/home-link':
                    // Home link
                    $items[] = [
                        '@type' => 'SiteNavigationElement',
                        'name' => 'Home',
                        'url' => home_url('/'),
                    ];
                    break;
                    
                case 'core/page-list':
                    // Automatic page list
                    $pages = get_pages([
                        'sort_column' => 'menu_order,post_title',
                        'parent' => 0, // Only top-level pages
                    ]);
                    
                    foreach ($pages as $page) {
                        $title = trim($page->post_title);
                        if (empty($title)) {
                            continue;
                        }
                        
                        $items[] = [
                            '@type' => 'SiteNavigationElement',
                            'name' => $title,
                            'url' => get_permalink($page),
                        ];
                    }
                    break;
                    
                default:
                    // For other blocks, check inner blocks
                    if (!empty($block['innerBlocks'])) {
                        // Recurse into the collector, not the public entry
                        // point: dedupe runs once over the flattened result.
                        $items = array_merge($items, $this->collect_navigation_items($block['innerBlocks']));
                    }
                    break;
            }
        }
        
        return $items;
    }
    
}