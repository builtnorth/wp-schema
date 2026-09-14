<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Graph;

/**
 * Schema Piece
 * 
 * Represents an atomic schema.org structured data piece.
 * Pieces can reference each other to build a complete graph.
 * 
 * @since 3.0.0
 */
class SchemaPiece
{
    private string $id;
    /** @var string|string[] */
    private $type;
    private string $name;
    private array $data;
    private array $references = [];

    /**
     * @param string          $id   The JSON-LD @id. Prefer an absolute IRI.
     * @param string|string[] $type The schema.org @type. An array expresses a
     *                              multi-typed node — schema.org's way of saying
     *                              one thing is both, e.g. ['WebPage','FAQPage']
     *                              for a page that is also an FAQ. The first
     *                              entry is the primary type (see get_type()).
     * @param array           $data Initial properties.
     * @param string|null     $name Short, site-independent handle used to build hook
     *                              names (e.g. "organization"). Derived from $id when
     *                              omitted — see derive_name().
     */
    public function __construct(string $id, $type, array $data = [], ?string $name = null)
    {
        $this->id = $id;
        $this->type = self::normalize_type($type);
        $this->name = $name !== null && $name !== '' ? $name : self::derive_name($id);
        $this->data = $data;

        // Always include @type and @id
        $this->data['@type'] = $this->type;
        $this->data['@id'] = $id;
    }

    /**
     * Collapse a single-entry list to a plain string so the common case keeps
     * emitting `"@type": "WebPage"` rather than `"@type": ["WebPage"]`. Both are
     * valid JSON-LD, but consumers (and our own tests) expect the scalar form.
     *
     * @param string|string[] $type
     * @return string|string[]
     */
    private static function normalize_type($type)
    {
        if (!is_array($type)) {
            return $type;
        }

        $types = array_values(array_unique(array_filter($type, 'is_string')));

        if ($types === []) {
            return '';
        }

        return count($types) === 1 ? $types[0] : $types;
    }

    /**
     * Get piece ID
     */
    public function get_id(): string
    {
        return $this->id;
    }

    /**
     * Short handle for this piece, used for hook names.
     *
     * Separate from the @id because @ids are absolute IRIs and therefore
     * site-specific: deriving a hook name straight from one yields
     * `..._https___example.com_organization`, which no plugin can target
     * portably. Mirrors the short identifiers Yoast (`wpseo_schema_organization`)
     * and Rank Math use alongside their URL-based @ids.
     */
    public function get_name(): string
    {
        return $this->name;
    }

    /**
     * Reduce an @id to a site-independent handle.
     *
     * Strips the home URL so an absolute IRI and a bare fragment for the same
     * node agree: both "https://example.com/#organization" and "#organization"
     * become "organization".
     */
    private static function derive_name(string $id): string
    {
        if (function_exists('home_url')) {
            $home = \rtrim((string) \home_url('/'), '/') . '/';
            if (\str_starts_with($id, $home)) {
                $id = \substr($id, \strlen($home));
            }
        }

        $id = \ltrim($id, '#/');

        return \strtolower(\preg_replace('/[^A-Za-z0-9_-]+/', '_', $id) ?? $id);
    }
    
    /**
     * The primary schema.org type, always a string.
     *
     * Multi-typed nodes return their first type. Callers use this to build hook
     * names (`wp_schema_framework_piece_{type}`) and to compare types, both of
     * which need a stable scalar — a node becoming multi-typed must not silently
     * rename its hook or break string comparison. Use get_types() for the full
     * list and to_array()['@type'] for what is actually emitted.
     */
    public function get_type(): string
    {
        return is_array($this->type) ? (string) ($this->type[0] ?? '') : $this->type;
    }

    /**
     * Every schema.org type on this node, primary first.
     *
     * @return string[]
     */
    public function get_types(): array
    {
        return is_array($this->type) ? $this->type : ($this->type === '' ? [] : [$this->type]);
    }

    /**
     * Add a schema.org type to this node, making it multi-typed.
     *
     * schema.org's model for "this page is also an FAQ" is one node with
     * `"@type": ["WebPage","FAQPage"]` — not a second node. Adding a type this
     * way keeps the primary type (and therefore the node's hook name) stable.
     */
    public function add_type(string $type): self
    {
        if ($type === '' || in_array($type, $this->get_types(), true)) {
            return $this;
        }

        $this->type = self::normalize_type(array_merge($this->get_types(), [$type]));
        $this->data['@type'] = $this->type;

        return $this;
    }
    
    /**
     * Set a property value
     */
    public function set(string $property, $value): self
    {
        $this->data[$property] = $value;
        return $this;
    }
    
    /**
     * Get a property value
     */
    public function get(string $property)
    {
        return $this->data[$property] ?? null;
    }
    
    /**
     * Add a reference to another piece
     */
    public function add_reference(string $property, string $piece_id): self
    {
        $this->data[$property] = ['@id' => $piece_id];
        $this->references[] = $piece_id;
        return $this;
    }
    
    /**
     * Get all references this piece makes
     */
    public function get_references(): array
    {
        return $this->references;
    }
    
    /**
     * Get the complete data array
     */
    public function to_array(): array
    {
        return $this->data;
    }
    
    /**
     * Check if piece has a specific property
     */
    public function has(string $property): bool
    {
        return isset($this->data[$property]);
    }
    
    /**
     * Remove a property
     */
    public function remove(string $property): self
    {
        unset($this->data[$property]);
        return $this;
    }
    
    /**
     * Merge data from another array
     */
    public function merge(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
    
    /**
     * Update this piece from array data
     */
    public function from_array(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
    
    /**
     * Create a piece from existing schema data
     */
    public static function create_from_array(array $data): self
    {
        $id = $data['@id'] ?? '#unknown';
        $type = $data['@type'] ?? 'Thing';
        
        return new self($id, $type, $data);
    }
}