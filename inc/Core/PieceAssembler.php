<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

/**
 * Piece Assembler
 * 
 * Assembles schema pieces into a Yoast-style graph with references.
 * Handles deduplication and reference resolution.
 * 
 * @since 3.0.0
 */
class PieceAssembler
{
    /**
     * Assemble pieces into a complete schema graph
     * 
     * Takes raw pieces from providers and creates a clean graph with:
     * - Deduplicated pieces by @id
     * - Proper @context handling
     * - Reference validation
     *
     * @param array $pieces Raw schema pieces from providers
     * @return array Assembled schema graph
     */
    public function assemble(array $pieces): array
    {
        if (empty($pieces)) {
            return [];
        }
        
        $assembled = [];
        $id_map = [];
        
        // First pass: collect all pieces and deduplicate by @id
        foreach ($pieces as $piece) {
            if (!is_array($piece) || empty($piece['@type'])) {
                continue; // Skip invalid pieces
            }
            
            $piece = $this->normalize_piece($piece);
            $id = $piece['@id'] ?? null;
            
            if ($id) {
                // Deduplicate by @id - first one wins
                if (!isset($id_map[$id])) {
                    $id_map[$id] = $piece;
                }
            } else {
                // No @id = standalone piece, always include
                $assembled[] = $piece;
            }
        }
        
        // Add all unique ID pieces
        $assembled = array_merge($assembled, array_values($id_map));
        
        // Sort pieces for consistent output (Organization first, then others)
        $assembled = $this->sort_pieces($assembled);
        
        return $assembled;
    }
    
    /**
     * Normalize a schema piece
     * 
     * Ensures proper @context and @type formatting
     *
     * @param array $piece Raw piece
     * @return array Normalized piece
     */
    private function normalize_piece(array $piece): array
    {
        // Ensure @context is present
        if (!isset($piece['@context'])) {
            $piece['@context'] = 'https://schema.org';
        }
        
        // Ensure @type is properly formatted
        if (isset($piece['@type'])) {
            $piece['@type'] = ucfirst($piece['@type']);
        }
        
        // Generate @id if missing but type suggests it should have one
        if (!isset($piece['@id']) && $this->should_have_id($piece['@type'])) {
            $piece['@id'] = $this->generate_id($piece);
        }
        
        return $piece;
    }
    
    /**
     * Check if a schema type should have an @id
     *
     * @param string $type Schema type
     * @return bool True if should have ID
     */
    private function should_have_id(string $type): bool
    {
        $types_with_ids = [
            'Organization',
            'LocalBusiness',
            'WebSite',
            'Person',
            'Place'
        ];
        
        return in_array($type, $types_with_ids, true);
    }
    
    /**
     * Generate an @id for a piece that should have one
     *
     * @param array $piece Schema piece
     * @return string Generated ID
     */
    private function generate_id(array $piece): string
    {
        $type = strtolower($piece['@type']);
        $base_url = home_url('/');
        
        // Generate meaningful IDs based on type
        switch ($type) {
            case 'organization':
            case 'localbusiness':
                return $base_url . '#organization';
            case 'website':
                return $base_url . '#website';
            case 'webpage':
                return get_permalink() . '#webpage';
            default:
                return $base_url . '#' . $type;
        }
    }
    
    /**
     * Sort pieces for consistent output
     * 
     * Organization and foundational pieces first, then content, then supplementary
     *
     * @param array $pieces Schema pieces
     * @return array Sorted pieces
     */
    private function sort_pieces(array $pieces): array
    {
        $priority_map = [
            'Organization' => 1,
            'LocalBusiness' => 1,
            'Restaurant' => 1,
            'Corporation' => 1,
            'WebSite' => 2,
            'WebPage' => 3,
            'Article' => 4,
            'BlogPosting' => 4,
            'Product' => 4,
            'Event' => 4,
            'SiteNavigationElement' => 5,
            'BreadcrumbList' => 5,
        ];
        
        usort($pieces, function($a, $b) use ($priority_map) {
            $priority_a = $priority_map[$a['@type']] ?? 99;
            $priority_b = $priority_map[$b['@type']] ?? 99;
            
            return $priority_a <=> $priority_b;
        });
        
        return $pieces;
    }
    
    /**
     * Validate that all references in pieces exist
     * 
     * Optional validation to ensure @id references point to existing pieces
     *
     * @param array $pieces Assembled pieces
     * @return array Validation results
     */
    public function validate_references(array $pieces): array
    {
        $existing_ids = [];
        $broken_references = [];
        
        // Collect all existing @ids
        foreach ($pieces as $piece) {
            if (isset($piece['@id'])) {
                $existing_ids[] = $piece['@id'];
            }
        }
        
        // Check for broken references
        foreach ($pieces as $piece) {
            $references = $this->extract_references($piece);
            foreach ($references as $ref) {
                if (!in_array($ref, $existing_ids, true)) {
                    $broken_references[] = [
                        'piece_type' => $piece['@type'],
                        'piece_id' => $piece['@id'] ?? 'no-id',
                        'broken_reference' => $ref
                    ];
                }
            }
        }
        
        return [
            'total_pieces' => count($pieces),
            'total_ids' => count($existing_ids),
            'broken_references' => $broken_references,
            'is_valid' => empty($broken_references)
        ];
    }
    
    /**
     * Extract all @id references from a piece
     *
     * @param array $piece Schema piece
     * @return array Array of referenced @ids
     */
    private function extract_references(array $piece): array
    {
        $references = [];
        
        foreach ($piece as $key => $value) {
            if ($key === '@id') {
                continue; // Skip own ID
            }
            
            if (is_array($value) && isset($value['@id'])) {
                $references[] = $value['@id'];
            } elseif (is_array($value)) {
                // Recursively check nested arrays
                $references = array_merge($references, $this->extract_references($value));
            }
        }
        
        return $references;
    }
}