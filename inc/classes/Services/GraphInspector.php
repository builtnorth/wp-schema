<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

/**
 * Structural checks over a finished `@graph` array.
 *
 * Pure: takes the array OutputService would print and returns findings. No
 * WordPress calls, so it is unit-testable and safe to run from WP-CLI.
 *
 * Stricter than SchemaGraph::validate_references(), which only knows about
 * ids registered through SchemaPiece::add_reference(). This walks the whole
 * output, so references written via set()/from_array() (page links,
 * parentOrganization, nested offers…) are checked too.
 *
 * @since 1.4.0
 */
final class GraphInspector
{
    /**
     * @param array<string, mixed> $graph_data       `['@context' => …, '@graph' => [...]]`.
     * @param string               $context          wp-schema context (home, singular, archive, search…).
     * @param string               $expected_page_id The WebPage @id this context should carry ('' to skip).
     *
     * @return array{
     *     nodes: list<array{type: string, id: string, name: string}>,
     *     references: int,
     *     errors: list<string>,
     *     warnings: list<string>
     * }
     */
    public function inspect(array $graph_data, string $context, string $expected_page_id = ''): array
    {
        $nodes    = is_array($graph_data['@graph'] ?? null) ? $graph_data['@graph'] : [];
        $errors   = [];
        $warnings = [];

        if ($nodes === []) {
            return [
                'nodes'      => [],
                'references' => 0,
                'errors'     => [ 'Graph is empty.' ],
                'warnings'   => [],
            ];
        }

        $inventory = [];
        $ids       = [];

        foreach ($nodes as $index => $node) {
            $type = $this->type_label($node['@type'] ?? null);
            $id   = is_string($node['@id'] ?? null) ? $node['@id'] : '';
            $name = is_string($node['name'] ?? null) ? $node['name'] : '';

            if ($type === '') {
                $errors[] = sprintf('Node #%d has no @type.', $index);
            }

            if ($id === '') {
                $warnings[] = sprintf('Node #%d (%s) has no @id, so nothing can reference it.', $index, $type ?: 'untyped');
            } elseif (isset($ids[ $id ])) {
                $errors[] = sprintf('Duplicate @id "%s" (%s and %s).', $id, $ids[ $id ], $type);
            } else {
                $ids[ $id ] = $type;
            }

            $inventory[] = [
                'type' => $type,
                'id'   => $id,
                'name' => $name,
            ];
        }

        $this->check_page_node($nodes, $ids, $context, $expected_page_id, $errors, $warnings);

        $reference_count = 0;
        foreach ($nodes as $index => $node) {
            $label = ($inventory[ $index ]['type'] ?: '#' . $index);
            $this->walk_references($node, $label, $ids, $reference_count, $errors);
        }

        return [
            'nodes'      => $inventory,
            'references' => $reference_count,
            'errors'     => $errors,
            'warnings'   => $warnings,
        ];
    }

    /**
     * Home and singular carry a page node keyed by the bare URL; archive and
     * search carry some *Page node (CollectionPage, SearchResultsPage…).
     *
     * @param list<array<string, mixed>> $nodes
     * @param array<string, string>      $ids
     * @param list<string>               $errors
     * @param list<string>               $warnings
     */
    private function check_page_node(
        array $nodes,
        array $ids,
        string $context,
        string $expected_page_id,
        array &$errors,
        array &$warnings
    ): void {
        if (in_array($context, [ 'home', 'singular' ], true)) {
            if ($expected_page_id === '') {
                return;
            }

            if (! isset($ids[ $expected_page_id ])) {
                $errors[] = sprintf('No page node with @id "%s".', $expected_page_id);
                return;
            }

            if (! $this->is_page_type($ids[ $expected_page_id ])) {
                $warnings[] = sprintf(
                    'Page node "%s" is typed %s, not a *Page type.',
                    $expected_page_id,
                    $ids[ $expected_page_id ]
                );
            }

            return;
        }

        if (in_array($context, [ 'archive', 'search' ], true)) {
            foreach ($nodes as $node) {
                if ($this->is_page_type($this->type_label($node['@type'] ?? null))) {
                    return;
                }
            }

            $errors[] = sprintf('No *Page node for %s context.', $context);
        }
    }

    /**
     * Any object whose only key is "@id" is a reference and must resolve to a
     * top-level node in the same graph.
     *
     * @param mixed                 $value
     * @param array<string, string> $ids
     * @param list<string>          $errors
     */
    private function walk_references($value, string $path, array $ids, int &$count, array &$errors): void
    {
        if (! is_array($value)) {
            return;
        }

        if (count($value) === 1 && isset($value['@id']) && is_string($value['@id'])) {
            $count++;
            if (! isset($ids[ $value['@id'] ])) {
                $errors[] = sprintf('%s → "%s" does not resolve to a node in this graph.', $path, $value['@id']);
            }

            return;
        }

        foreach ($value as $key => $child) {
            if (! is_array($child)) {
                continue;
            }

            $segment = is_int($key) ? '[' . $key . ']' : '.' . $key;
            $this->walk_references($child, $path . $segment, $ids, $count, $errors);
        }
    }

    /**
     * @param mixed $type String, list of strings, or absent.
     */
    private function type_label($type): string
    {
        if (is_string($type)) {
            return $type;
        }

        if (is_array($type)) {
            $strings = array_values(array_filter($type, 'is_string'));

            return implode('/', $strings);
        }

        return '';
    }

    private function is_page_type(string $type_label): bool
    {
        foreach (explode('/', $type_label) as $type) {
            if ($type !== '' && str_ends_with($type, 'Page')) {
                return true;
            }
        }

        return false;
    }
}
