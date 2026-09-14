<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

/**
 * Canonical @id conventions for graph pieces.
 *
 * Mirrors Yoast's Schema_IDs: the WebPage node is identified by the bare
 * permalink, and page-scoped pieces (article, breadcrumb, entity) hang off
 * that permalink as fragments. Entity nodes link *up* to the page via
 * isPartOf + mainEntityOfPage; the page never references the entity.
 *
 * @since 1.4.0
 */
final class SchemaIds
{
    public const ARTICLE_FRAGMENT    = '#article';
    public const BREADCRUMB_FRAGMENT = '#breadcrumb';
    public const WEBSITE_FRAGMENT    = '#website';
    public const ORGANIZATION_FRAGMENT = '#organization';

    /**
     * The site-wide Organization @id. Absolute, because it is referenced from
     * every page: a bare "#organization" would resolve against each page's own
     * URL and never match the node.
     */
    public static function organization_id(): string
    {
        return trailingslashit(home_url('/')) . '#organization';
    }

    /**
     * The site-wide WebSite @id — absolute for the same reason as organization_id().
     */
    public static function website_id(): string
    {
        return trailingslashit(home_url('/')) . '#website';
    }

    /**
     * The WebPage @id for a post: its permalink, no fragment.
     */
    public static function webpage_id(\WP_Post|int $post): string
    {
        $permalink = get_permalink($post);

        return $permalink ? (string) $permalink : '';
    }

    /**
     * The WebPage @id for the front page.
     */
    public static function home_webpage_id(): string
    {
        return (string) home_url('/');
    }

    /**
     * A page-scoped entity @id: permalink + fragment.
     *
     * @param string $fragment e.g. "#article", "#localbusiness".
     */
    public static function entity_id(\WP_Post|int $post, string $fragment): string
    {
        $permalink = self::webpage_id($post);
        if ($permalink === '') {
            return '';
        }

        return trailingslashit($permalink) . '#' . ltrim($fragment, '#');
    }

    /**
     * The @id of the page currently being requested, in any context.
     *
     * Unlike webpage_id(), which needs a post, this resolves from the current
     * query — so archive, search and attachment requests get a real page URL
     * rather than a bare fragment. Mirrors ContextDetector's ordering:
     * front page first (a static front page also satisfies is_singular()),
     * then attachment, then singular, with the request URL as the fallback.
     */
    public static function current_page_id(): string
    {
        if (function_exists('is_front_page') && is_front_page()) {
            return self::home_webpage_id();
        }

        if (function_exists('is_singular') && is_singular()) {
            $permalink = get_permalink();

            if ($permalink) {
                return (string) $permalink;
            }
        }

        global $wp;

        if (isset($wp->request)) {
            $search = (function_exists('is_search') && is_search()) ? get_search_query() : '';

            return $search !== ''
                ? (string) home_url(add_query_arg([ 's' => $search ], $wp->request))
                : (string) home_url(add_query_arg([], $wp->request));
        }

        return self::home_webpage_id();
    }

    /**
     * The BreadcrumbList @id for the current page.
     *
     * Page-scoped for the same reason entity ids are: a bare "#breadcrumb"
     * resolves against whatever URL the consumer reads the graph from, so the
     * emitting package and the referencing page node can silently disagree.
     * Both sides call this, so the string agrees by construction.
     */
    public static function breadcrumb_id(): string
    {
        $page = self::current_page_id();

        if ($page === '') {
            return self::BREADCRUMB_FRAGMENT;
        }

        return trailingslashit($page) . '#' . ltrim(self::BREADCRUMB_FRAGMENT, '#');
    }

    /**
     * The two properties an entity node uses to point at the page it lives on.
     *
     * @return array<string, array{"@id": string}>
     */
    public static function page_links(\WP_Post|int $post): array
    {
        $id = self::webpage_id($post);
        if ($id === '') {
            return [];
        }

        return [
            'isPartOf'         => [ '@id' => $id ],
            'mainEntityOfPage' => [ '@id' => $id ],
        ];
    }
}
