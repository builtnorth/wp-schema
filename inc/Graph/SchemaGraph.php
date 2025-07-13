<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Graph;

/**
 * Schema Graph Builder
 * 
 * Builds a complete schema graph from atomic pieces.
 * Handles references, validation, and output formatting.
 * 
 * @since 3.0.0
 */
class SchemaGraph
{
    /** @var SchemaPiece[] */
    private array $pieces = [];
    
    /** @var array */
    private array $context_cache = [];
    
    /**
     * Add a schema piece to the graph
     */
    public function add_piece(SchemaPiece $piece): self
    {
        $this->pieces[$piece->get_id()] = $piece;
        return $this;
    }
    
    /**
     * Add multiple pieces
     * 
     * @param SchemaPiece[] $pieces
     */
    public function add_pieces(array $pieces): self
    {
        foreach ($pieces as $piece) {
            if ($piece instanceof SchemaPiece) {
                $this->add_piece($piece);
            }
        }
        return $this;
    }
    
    /**
     * Get a specific piece by ID
     */
    public function get_piece(string $id): ?SchemaPiece
    {
        return $this->pieces[$id] ?? null;
    }
    
    /**
     * Get all pieces
     * 
     * @return SchemaPiece[]
     */
    public function get_pieces(): array
    {
        return $this->pieces;
    }
    
    /**
     * Get pieces by type
     * 
     * @return SchemaPiece[]
     */
    public function get_pieces_by_type(string $type): array
    {
        return array_filter($this->pieces, fn($piece) => $piece->get_type() === $type);
    }
    
    /**
     * Remove a piece by ID
     */
    public function remove_piece(string $id): self
    {
        unset($this->pieces[$id]);
        return $this;
    }
    
    /**
     * Check if a piece exists
     */
    public function has_piece(string $id): bool
    {
        return isset($this->pieces[$id]);
    }
    
    /**
     * Apply filters to allow modification of pieces
     */
    public function apply_filters(string $context): self
    {
        // Allow global modification of all pieces
        $this->pieces = apply_filters('wp_schema_pieces', $this->pieces, $context);
        
        // Allow modification of specific piece types
        foreach ($this->pieces as $piece) {
            $type_filter = 'wp_schema_piece_' . strtolower($piece->get_type());
            $filtered_piece = apply_filters($type_filter, $piece, $context);
            
            if ($filtered_piece instanceof SchemaPiece) {
                $this->pieces[$piece->get_id()] = $filtered_piece;
            }
        }
        
        // Allow modification by piece ID
        foreach ($this->pieces as $piece) {
            $id = str_replace(['#', '/', ':'], ['', '_', '_'], $piece->get_id());
            $id_filter = 'wp_schema_piece_id_' . $id;
            $filtered_piece = apply_filters($id_filter, $piece, $context);
            
            if ($filtered_piece instanceof SchemaPiece) {
                $this->pieces[$piece->get_id()] = $filtered_piece;
            }
        }
        
        return $this;
    }
    
    /**
     * Validate references in the graph
     */
    public function validate_references(): array
    {
        $errors = [];
        
        foreach ($this->pieces as $piece) {
            foreach ($piece->get_references() as $ref_id) {
                if (!$this->has_piece($ref_id)) {
                    $errors[] = "Piece '{$piece->get_id()}' references missing piece '{$ref_id}'";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Build the final schema output
     */
    public function to_array(): array
    {
        $output = [];
        
        foreach ($this->pieces as $piece) {
            $data = $piece->to_array();
            
            // Add schema.org context if not present
            if (!isset($data['@context'])) {
                $data['@context'] = 'https://schema.org';
            }
            
            $output[] = $data;
        }
        
        return $output;
    }
    
    /**
     * Get schema as JSON-LD
     */
    public function to_json(): string
    {
        $schema_array = $this->to_array();
        
        return json_encode(
            $schema_array,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '';
    }
    
    /**
     * Clear all pieces
     */
    public function clear(): self
    {
        $this->pieces = [];
        $this->context_cache = [];
        return $this;
    }
    
    /**
     * Get pieces count
     */
    public function count(): int
    {
        return count($this->pieces);
    }
    
    /**
     * Check if graph is empty
     */
    public function is_empty(): bool
    {
        return empty($this->pieces);
    }
}