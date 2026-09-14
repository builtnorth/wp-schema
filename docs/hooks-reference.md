# Hooks reference

Every hook wp-schema fires, verified against source. Parameter counts are the
ones the `apply_filters()`/`do_action()` call site actually passes — `add_filter`
with a higher `$accepted_args` than listed here receives `null`.

Hook names use the `wp_schema_framework_` prefix throughout. This is the
package's established public API; it predates the current naming convention and
is not changed here, since every name below is a live integration point.

## Actions

### `wp_schema_framework_register_providers`
`App.php:74` — fires during `init()`, after core providers are registered and
after `$initialized` is set true, so `register_provider()` works from callbacks.

| # | Param | Type |
|---|-------|------|
| 1 | `$app` | `App` |

Prefer `Schema::registerProvider()` over hooking this directly; it handles the
case where the action has already fired.

### `wp_schema_framework_ready`
`App.php:77` — fires immediately after the registration action.

| # | Param | Type |
|---|-------|------|
| 1 | `$app` | `App` |

### `wp_schema_framework_before_output`
`Services/OutputService.php:47` — after the context check passes, before the
graph is built.

| # | Param | Type |
|---|-------|------|
| 1 | `$context` | `string` |

### `wp_schema_framework_after_output`
`Services/OutputService.php:58` — fires only when a non-empty graph was printed.
An empty graph returns early (`OutputService.php:51-53`), so this does **not**
fire on every request where `before_output` did.

| # | Param | Type |
|---|-------|------|
| 1 | `$context` | `string` |
| 2 | `$graph` | `SchemaGraph` |

## Global control

### `wp_schema_framework_output_enabled`
`Services/ContextDetector.php:46` — return false to suppress all output.

| # | Param | Type |
|---|-------|------|
| 1 | `$enabled` | `bool` (default `true`) |

### `wp_schema_framework_context`
`Services/ContextDetector.php:37` — override the detected context.

| # | Param | Type |
|---|-------|------|
| 1 | `$context` | `string` |

Returning a value outside the known set effectively disables output: providers
compare `$context` against literal strings, and `should_generate_schema()`
rejects `404` and `unknown`.

## Graph and piece filters

Applied in the order listed (`Graph/SchemaGraph.php:118-144`, then
`Services/OutputService.php:83-101`).

### `wp_schema_framework_pieces`
`Graph/SchemaGraph.php:121`

| # | Param | Type |
|---|-------|------|
| 1 | `$pieces` | `SchemaPiece[]`, keyed by `@id` |
| 2 | `$context` | `string` |

**The array holds `SchemaPiece` objects, not arrays,** and is keyed by `@id`. The
result is immediately iterated with `get_type()` called on each element, so a
plain array injected here is a fatal error. Append with
`$pieces[$piece->get_id()] = $piece;`.

### `wp_schema_framework_piece_{type}`
`Graph/SchemaGraph.php:125-126` — `{type}` is the node's `@type`, lowercased.
Hence `wp_schema_framework_piece_article`, `..._webpage`, `..._organization`,
`..._localbusiness`.

| # | Param | Type |
|---|-------|------|
| 1 | `$piece` | `SchemaPiece` |
| 2 | `$context` | `string` |

Return the piece. A return value that is not a `SchemaPiece` is ignored and the
original is kept (`SchemaGraph.php:128-130`).

### `wp_schema_framework_piece_id_{name}`
`Graph/SchemaGraph.php:135-136` — `{name}` is the piece's handle, **not** its
`@id`. See the README's "Piece names" section for how the handle is derived.

| # | Param | Type |
|---|-------|------|
| 1 | `$piece` | `SchemaPiece` |
| 2 | `$context` | `string` |

This is the supported way to add properties to a node another provider owns.

### `wp_schema_framework_graph`
`Services/OutputService.php:83` — the complete `['@context' => …, '@graph' => …]`
array. Per-node `@context` keys have already been stripped.

| # | Param | Type |
|---|-------|------|
| 1 | `$graph_data` | `array` |

Output is skipped when `$graph_data['@graph']` comes back empty
(`OutputService.php:93-95`).

### `wp_schema_framework_json_output`
`Services/OutputService.php:101` — the encoded string, immediately before it is
echoed inside the `<script>` tag.

| # | Param | Type |
|---|-------|------|
| 1 | `$json` | `string` |
| 2 | `$graph_data` | `array` |

The encode uses `JSON_HEX_TAG` and friends to prevent `</script>` breakout. If
you re-encode here, preserve those flags.

## Provider data filters

Each fires near the end of its provider's `get_pieces()`, receiving the node as
an array. Note the parameter signatures are **not** uniform — some pass
`$context`, others pass `$post_id`/`$post`.

| Hook | Params | Source |
|---|---|---|
| `wp_schema_framework_organization_data` | `$data`, `$context` | `Providers/OrganizationProvider.php:42` |
| `wp_schema_framework_website_data` | `$data`, `$context` | `Providers/WebsiteProvider.php:75` |
| `wp_schema_framework_article_data` | `$data`, `$post_id`, `$post` | `Providers/ArticleProvider.php:192` |
| `wp_schema_framework_webpage_data` | `$data`, `$post_id`, `$post` | `Providers/WebPageProvider.php:178` |
| `wp_schema_framework_author_data` | `$data`, `$author_id`, `$context` | `Providers/AuthorProvider.php:74` |
| `wp_schema_framework_person_data` | `$data`, `$post_id`, `$post` | `Providers/PersonProvider.php:82` |
| `wp_schema_framework_service_data` | `$data`, `$post_id`, `$post` | `Providers/ServiceProvider.php:76` |
| `wp_schema_framework_offer_data` | `$data`, `$post_id`, `$post` | `Providers/OfferProvider.php:74` |
| `wp_schema_framework_product_data` | `$data`, `$context`, `$post_id` | `Providers/ProductProvider.php:137` |
| `wp_schema_framework_event_data` | `$data`, `$context`, `$post_id` | `Providers/EventProvider.php:151` |
| `wp_schema_framework_archive_data` | `$data`, `$context` | `Providers/ArchiveProvider.php:57` |
| `wp_schema_framework_search_results_data` | `$data`, `$context`, `$search_query` | `Providers/SearchResultsProvider.php:91` |
| `wp_schema_framework_media_data` | `$data`, `$context`, `$attachment_id` | `Providers/MediaProvider.php:103` |
| `wp_schema_framework_page_type_data` | `$data`, `$context`, `$schema_type` | `Providers/PageTypeProvider.php:115` |
| `wp_schema_framework_generic_data` | `$data`, `$post_id`, `$post` | `Providers/GenericSchemaProvider.php:217` |

On the home context, `ArticleProvider`, `WebPageProvider`, and
`GenericSchemaProvider` pass `0` and `null` for `$post_id`/`$post`
(`ArticleProvider.php:107`, `WebPageProvider.php:112`,
`GenericSchemaProvider.php:148`). Guard against a null `$post`.

### `wp_schema_framework_{lowercase_type}_data`
`Providers/GenericSchemaProvider.php:149, 218` — a dynamic filter built from the
resolved schema type, lowercased: `wp_schema_framework_recipe_data`,
`wp_schema_framework_videoobject_data`, and so on. Fires only for types the
generic fallback handles.

| # | Param | Type |
|---|-------|------|
| 1 | `$data` | `array` |
| 2 | `$post_id` | `int` (`0` on home) |
| 3 | `$post` | `WP_Post|null` |

### `wp_schema_framework_organization_type`
`Providers/WebsiteProvider.php:30`

| # | Param | Type |
|---|-------|------|
| 1 | `$type` | `string` (default `'Organization'`) |

Returning `'WebSite'` makes `WebsiteProvider` stand down entirely to avoid a
duplicate node. It does **not** change the `Organization` node's own `@type` —
`OrganizationProvider` never reads this filter. To retype that node, filter
`wp_schema_framework_organization_data` or the piece directly.

### `wp_schema_framework_website_can_provide`
`Providers/WebsiteProvider.php:23`

| # | Param | Type |
|---|-------|------|
| 1 | `$can_provide` | `bool` |
| 2 | `$context` | `string` |

## Post type and content resolution

### `wp_schema_framework_post_type_override`
The main lever for deciding a post's schema type. Called by `ArticleProvider:41`,
`WebPageProvider:45`, `GenericSchemaProvider:75, 163`, `PersonProvider:97`,
`OfferProvider:89`, `ServiceProvider:91`.

| # | Param | Type |
|---|-------|------|
| 1 | `$schema_type` | `string` |
| 2 | `$post_id` | `int` |
| 3 | `$post_type` | `string` |
| 4 | `$post` | `WP_Post` |

Because several providers call this independently to decide `can_provide()`, the
filter must be **deterministic** for a given post — returning different values
across calls yields a graph with missing or duplicated nodes.

Every caller passes its own mapped default, so a filter that returns the incoming
value unchanged is always a no-op. `WebPageProvider` is the single caller that
decides whether a page is a specialized subtype; `PageTypeProvider` reads the
resolved type off the node's `@type` rather than calling this filter again, so
the two can never disagree.

### `wp_schema_framework_post_type_mapping`
Per-post-type default, consulted before the override above. `ArticleProvider:214`,
`WebPageProvider:216`, `GenericSchemaProvider:235`, `PersonProvider:95`,
`OfferProvider:87`, `ServiceProvider:89`.

| # | Param | Type |
|---|-------|------|
| 1 | `$schema_type` | `string` (often `''`) |
| 2 | `$post_type` | `string` |

Each provider seeds its own defaults — `ArticleProvider` knows `post`, `news`,
`blog_post`; `WebPageProvider` knows `page`; the others pass `''`. This filter is
**not** fed by `SchemaTypeRegistry::get_post_type_mappings()`; that list is a
separate UI-facing map. Wiring the two together is the consuming plugin's job.

### `wp_schema_framework_post_type_mappings`
`Services/SchemaTypeRegistry.php:383` — the registry's post-type map, used by
`Schema::getPostTypeMappings()` and `getSchemaTypeForPostType()`. Note the
trailing `s` distinguishing it from the per-provider filter above.

| # | Param | Type |
|---|-------|------|
| 1 | `$mappings` | `array<string, string>` |

### `wp_schema_framework_generic_skip_post_types`
`Providers/GenericSchemaProvider.php:68` — post types whose entity node a
dedicated provider emits, so the fallback does not emit a second one.

| # | Param | Type |
|---|-------|------|
| 1 | `$post_types` | `string[]` |

### `wp_schema_framework_post_description`
`ArticleProvider:149`, `WebPageProvider:148`, `PersonProvider:51`,
`OfferProvider:51`, `ServiceProvider:53`. A non-empty return wins over the post
excerpt.

| # | Param | Type |
|---|-------|------|
| 1 | `$description` | `string` |
| 2 | `$post_id` | `int` |
| 3 | `$post` | `WP_Post` |

### `wp_schema_framework_homepage_type`
`ArticleProvider:26, 52`, `GenericSchemaProvider:46, 87`. Seeded from
`get_option('polaris_seo_settings')['home']['default_schema_type']`, defaulting to
`'WebPage'`.

| # | Param | Type |
|---|-------|------|
| 1 | `$type` | `string` |

**This package reads that option but never writes it** — it is owned by the SEO
plugin. Sites without that plugin should use this filter rather than seeding the
option.

### `wp_schema_framework_homepage_data`
`ArticleProvider:106`, `WebPageProvider:111`, `GenericSchemaProvider:147`.
Applied *before* the corresponding `*_data` filter on the same node.

| # | Param | Type |
|---|-------|------|
| 1 | `$data` | `array` |

### `wp_schema_framework_has_breadcrumb`
`Providers/WebPageProvider.php:106, 173` — defaults to `false`.

| # | Param | Type |
|---|-------|------|
| 1 | `$has_breadcrumb` | `bool` |

Every node that references `#breadcrumb` adds it **only** when this returns true,
because a reference to a `BreadcrumbList` that nothing emits is a dangling
reference. A package that emits a `#breadcrumb` node must return true here.

This gate is consistent across all page nodes — `WebPageProvider:106, 173`,
`ArchiveProvider:40`, `SearchResultsProvider:40`, and `MediaProvider:100`. None
of them reference a breadcrumb node that is not in the graph.

## Archive and search

| Hook | Params | Source |
|---|---|---|
| `wp_schema_framework_archive_item_type` | `$type`, `$post_type` | `Providers/ArchiveProvider.php:249` |
| `wp_schema_framework_search_item_type` | `$type`, `$post_type` | `Providers/SearchResultsProvider.php:203` |

Both default to `''`; a non-empty return is used verbatim as the list item's
`@type`.

## Specialized page types

Consulted by `PageTypeProvider` for its corresponding schema type only. The
resulting properties land on the multi-typed page node (`["WebPage","FAQPage"]`),
not on a node of their own.

| Hook | Params | Source |
|---|---|---|
| `wp_schema_framework_faq_items` | `$items`, `$post_id` | `PageTypeProvider.php:254` |
| `wp_schema_framework_collection_items` | `$items`, `$post_id` | `PageTypeProvider.php:270` |
| `wp_schema_framework_gallery_items` | `$items`, `$post_id` | `PageTypeProvider.php:283` |
| `wp_schema_framework_gallery_image_count` | `$count`, `$post_id` | `PageTypeProvider.php:290` |

FAQ items land on `mainEntity`; collection and gallery items land on `hasPart`.

## Commerce and event integration

Detection and data hooks for custom implementations. See
[conflict-detection.md](conflict-detection.md) for when the built-in providers
stand down.

| Hook | Params | Source |
|---|---|---|
| `wp_schema_framework_is_product` | `$is_product`, `$post_id`, `$context` | `ProductProvider.php:48` |
| `wp_schema_framework_get_product_data` | `$data`, `$post_id` | `ProductProvider.php:154` |
| `wp_schema_framework_is_event` | `$is_event`, `$post_id`, `$context` | `EventProvider.php:62` |
| `wp_schema_framework_get_event_data` | `$data`, `$post_id` | `EventProvider.php:168` |

`get_product_data` / `get_event_data` default to `null` and take priority over
every built-in detection path when they return an array. Returning an empty
array means "no data" and the provider emits nothing.

Plugin-specific data filters, each firing only when that integration supplies the
data: `wp_schema_framework_woocommerce_product_data` (`ProductProvider.php:262`),
`wp_schema_framework_edd_product_data` (`:312`),
`wp_schema_framework_bigcommerce_product_data` (`:330`),
`wp_schema_framework_tribe_events_data` (`EventProvider.php:297`),
`wp_schema_framework_events_manager_data` (`:358`),
`wp_schema_framework_mec_data` (`:406`),
`wp_schema_framework_event_organiser_data` (`:447`),
`wp_schema_framework_gatherpress_data` (`:514`).

Conflict overrides: `wp_schema_framework_woocommerce_schema_active`
(`ProductProvider.php:350`) and `wp_schema_framework_tribe_events_schema_active`
(`EventProvider.php:691`), both `bool`.

## Type registry

| Hook | Params | Source |
|---|---|---|
| `wp_schema_framework_type_registry_types` | `$types` | `Services/SchemaTypeRegistry.php:349` |
| `wp_schema_framework_available_types` | `$types` | `App.php:80` |

`App::init()` registers `SchemaTypeRegistry::get_available_types()` onto
`wp_schema_framework_available_types` at default priority. Because that callback
**ignores the incoming value** and returns the full list, anything hooked at an
earlier priority is discarded. Filter at priority > 10, or use
`wp_schema_framework_type_registry_types` to shape the list at its source.

## Dangling references

The package emits no unconditional references to nodes it does not supply. The
one cross-package reference, `#breadcrumb`, is gated everywhere behind
`wp_schema_framework_has_breadcrumb`, so it appears only when a package has
declared that it emits a `BreadcrumbList`.

Verify a page's graph with:

```bash
wp schema check --post=<id>
```

which reports any `{"@id": …}` that does not resolve to a node in the same graph.
