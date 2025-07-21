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
    private string $type;
    private array $data;
    private array $references = [];
    
    public function __construct(string $id, string $type, array $data = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
        
        // Always include @type and @id
        $this->data['@type'] = $type;
        $this->data['@id'] = $id;
    }
    
    /**
     * Get piece ID
     */
    public function get_id(): string
    {
        return $this->id;
    }
    
    /**
     * Get piece type
     */
    public function get_type(): string
    {
        return $this->type;
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