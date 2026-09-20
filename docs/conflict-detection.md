# Conflict detection

When another plugin already emits `Product` or `Event` JSON-LD, two nodes for the
same thing end up on the page and search engines see duplicate structured data.
wp-schema's product and event providers detect that case where possible and stand
down.

| Integration | Detected as a data source | Stands down on conflict |
|---|---|---|
| WooCommerce | yes | **yes** (when `WC_Structured_Data` exists) |
| Easy Digital Downloads | yes | **yes** (when `EDD_Structured_Data` exists) |
| The Events Calendar | yes | **yes** (when TEC JSON-LD is enabled) |
| BigCommerce | yes | opt-in (`wp_schema_framework_bigcommerce_schema_active`, default `false`) |
| Events Manager | yes | opt-in (`wp_schema_framework_events_manager_schema_active`, default `false`) |
| Modern Events Calendar | yes | opt-in (`wp_schema_framework_mec_schema_active`, default `false`) |
| Event Organiser | yes | opt-in (`wp_schema_framework_event_organiser_schema_active`, default `false`) |
| All in One Event Calendar | detection only | opt-in (`wp_schema_framework_ai1ec_schema_active`, default `false`) |
| GatherPress | yes | opt-in (`wp_schema_framework_gatherpress_schema_active`, default `false`) |

**Auto stand-down** (Woo / EDD / TEC): those plugins ship JSON-LD themselves, so
when their schema class (or TEC setting) says they are emitting, wp-schema yields.

**Opt-in stand-down** (everyone else): the plugin usually does *not* emit JSON-LD
out of the box (theme microdata or a third-party schema plugin might). Default is
that wp-schema **provides**. Set the matching `*_schema_active` filter to `true`
when you know another source already prints Product/Event markup.

You can also suppress wp-schema with `wp_schema_framework_is_product` /
`wp_schema_framework_is_event` returning `false`, or disable the other plugin's
output at its source.

## How the auto-guarded paths decide

### Product — WooCommerce

Reached for the `product` post type when WooCommerce's class is loaded:

1. If `woocommerce_structured_data_disable` filters to true, WooCommerce is not
   emitting — wp-schema **provides**.
2. If `WC_Structured_Data` does not exist, wp-schema **provides**.
3. Otherwise `wp_schema_framework_woocommerce_schema_active` (default `true`)
   decides. True means wp-schema **stands down**.

### Product — Easy Digital Downloads

Reached for the `download` post type when EDD is loaded:

1. If `EDD_Structured_Data` does not exist (pre-3.0 / schema off), wp-schema
   **provides**.
2. Otherwise `wp_schema_framework_edd_schema_active` (default `true`) decides.
   True means wp-schema **stands down**.

### Event — The Events Calendar

Reached for the `tribe_events` post type when that plugin's main class is loaded:

1. If the plugin's own `disable_jsonld` option is set, wp-schema **provides**.
2. If its JSON-LD class does not exist, wp-schema **provides**.
3. Otherwise `wp_schema_framework_tribe_events_schema_active` (default `true`)
   decides. True means wp-schema **stands down**.

Note the auto guards check a *class* (or TEC option) rather than every possible
setting — a plugin that loads the class but disables schema another way is still
treated as active. The filters exist for exactly that case.

## Forcing wp-schema to own the output

```php
// Product: emit ours even when WooCommerce / EDD schema classes are present.
add_filter('wp_schema_framework_woocommerce_schema_active', '__return_false');
add_filter('wp_schema_framework_edd_schema_active', '__return_false');

// Event: same, for The Events Calendar.
add_filter('wp_schema_framework_tribe_events_schema_active', '__return_false');
```

## Standing down when a non-shipping plugin / theme already emits

```php
// Example: Events Manager + a theme that prints Event JSON-LD.
add_filter('wp_schema_framework_events_manager_schema_active', '__return_true');

// BigCommerce site with another Product schema source.
add_filter('wp_schema_framework_bigcommerce_schema_active', '__return_true');
```

Disabling the integrating plugin's output at its source is usually the better fix
when that plugin exposes a switch — these filters only stop wp-schema from
yielding:

```php
add_filter('woocommerce_structured_data_disable', '__return_true');
```

## Custom post types

Detection filters let an unrecognized post type be treated as a product or event.
Both default to `false` and are the last check in `can_provide()`, so they cannot
override a stand-down decision made earlier.

```php
add_filter('wp_schema_framework_is_product', function ($is_product, $post_id, $context) {
    return get_post_type($post_id) === 'my_product_type' ? true : $is_product;
}, 10, 3);

add_filter('wp_schema_framework_is_event', function ($is_event, $post_id, $context) {
    return get_post_type($post_id) === 'my_event_type' ? true : $is_event;
}, 10, 3);
```

Supply the data with `wp_schema_framework_get_product_data` or
`wp_schema_framework_get_event_data`. Returning an array from either short-circuits
every built-in data path:

```php
add_filter('wp_schema_framework_get_event_data', function ($data, $post_id) {
    if (get_post_type($post_id) !== 'my_event_type') {
        return $data;
    }

    return [
        'name'      => get_the_title($post_id),
        'startDate' => get_post_meta($post_id, 'event_start', true), // ISO 8601
        'endDate'   => get_post_meta($post_id, 'event_end', true),
        'location'  => [
            'name'    => get_post_meta($post_id, 'venue_name', true),
            'type'    => 'Place',
            'address' => [
                'streetAddress'   => get_post_meta($post_id, 'venue_address', true),
                'addressLocality' => get_post_meta($post_id, 'venue_city', true),
            ],
        ],
    ];
}, 10, 2);
```

## Graph shape of these two nodes

Both nodes follow the `SchemaIds` conventions described in the README: the `@id`
is permalink-scoped (`{permalink}#product`, `{permalink}#event`) and each node
links up to its page via `isPartOf` and `mainEntityOfPage`. If the current post
has no resolvable permalink, neither provider emits anything — a page-scoped
entity with no page node would only produce a dangling reference.

## Verifying

```bash
wp schema check --post=<id>
```

This builds the graph in-process and lists every node, so a duplicated `Product`
or `Event` shows up directly. To see what the other plugin adds on top, compare
against the rendered page — `wp schema check` only sees wp-schema's own output.

## Writing a provider that yields

The pattern built-in guards follow: check whether the other source is emitting,
allow an override filter, and return `false` from `can_provide()` so no piece is
created.

```php
private function other_source_is_active(): bool
{
    if (!class_exists('Some_Plugin_Schema_Class')) {
        return false;
    }

    return apply_filters('my_plugin_other_schema_active', true);
}

public function can_provide(string $context): bool
{
    if ($context !== 'singular') {
        return false;
    }

    return !$this->other_source_is_active();
}
```

Yielding in `can_provide()` is preferable to emitting a piece and removing it
later — the graph never sees a node that has to be cleaned up, and no `@id`
collision or `_doing_it_wrong` notice can occur.
