# Conflict detection

When another plugin already emits `Product` or `Event` JSON-LD, two nodes for the
same thing end up on the page and search engines see duplicate structured data.
wp-schema's product and event providers can detect that case and stand down.

**Coverage is narrower than it looks.** Only two integrations actually stand
down. Every other supported commerce and event integration is a *data source*
only — wp-schema reads its data and emits its own node, with no duplicate check.

| Integration | Detected as a data source | Stands down on conflict |
|---|---|---|
| WooCommerce | yes | **yes** |
| The Events Calendar | yes | **yes** |
| Easy Digital Downloads | yes | no |
| BigCommerce | yes | no |
| Events Manager | yes | no |
| Modern Events Calendar | yes | no |
| Event Organiser | yes | no |
| All in One Event Calendar | detection only | no |
| GatherPress | yes | no |

For the integrations in the "no" column, wp-schema emits its node regardless. If
that plugin also emits its own schema, the page carries both. Suppress one side
with `wp_schema_framework_is_product` / `wp_schema_framework_is_event` returning
`false`, or with that plugin's own setting.

## How the two guarded paths decide

### Product

`Providers/ProductProvider.php:336-351`, reached only for the `product` post type
when WooCommerce's class is loaded (`:28-35`):

1. If `woocommerce_structured_data_disable` filters to true, WooCommerce is not
   emitting — wp-schema **provides**.
2. If its `WC_Structured_Data` class does not exist, wp-schema **provides**.
3. Otherwise it is assumed active, and the result of
   `wp_schema_framework_woocommerce_schema_active` (default `true`) decides.
   True means wp-schema **stands down**.

### Event

`Providers/EventProvider.php:677-695`, reached only for the `tribe_events` post
type when that plugin's main class is loaded (`:28-34`):

1. If the plugin's own `disable_jsonld` option is set, wp-schema **provides**.
2. If its JSON-LD class does not exist, wp-schema **provides**.
3. Otherwise `wp_schema_framework_tribe_events_schema_active` (default `true`)
   decides. True means wp-schema **stands down**.

Note both guards check a *class* rather than a version or a setting, so a plugin
that loads the class but has schema disabled through some other mechanism is
still treated as active. The filters exist for exactly that case.

## Forcing wp-schema to own the output

```php
// Product: emit ours even when WooCommerce is active.
add_filter('wp_schema_framework_woocommerce_schema_active', '__return_false');

// Event: same, for the calendar plugin.
add_filter('wp_schema_framework_tribe_events_schema_active', '__return_false');
```

Disabling the integrating plugin's output at its source is usually the better fix,
since these filters only stop wp-schema from yielding — they cannot stop the
other plugin from printing its own markup:

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
every built-in data path (`ProductProvider.php:154-157`,
`EventProvider.php:168-171`):

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

Recognized `location` shapes are `Place` (the default) and `VirtualLocation`,
which uses `url` instead of an address (`EventProvider.php:520-563`).

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

The pattern both built-in guards follow: check whether the other source is
emitting, allow an override filter, and return `false` from `can_provide()` so no
piece is created.

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
