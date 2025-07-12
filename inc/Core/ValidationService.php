<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Core;

/**
 * Validation Service
 * 
 * Simple validation for schema data. Not overly complex - just basic checks
 * to ensure schema is valid and follows schema.org patterns.
 * 
 * @since 3.0.0
 */
class ValidationService
{
    /**
     * Validate a schema piece
     */
    public function validate_piece(array $piece): array
    {
        $errors = [];
        
        // Must have @type
        if (empty($piece['@type'])) {
            $errors[] = 'Missing required @type property';
        }
        
        // @type should be a string
        if (isset($piece['@type']) && !is_string($piece['@type'])) {
            $errors[] = '@type must be a string';
        }
        
        // @context should be schema.org if present
        if (isset($piece['@context']) && $piece['@context'] !== 'https://schema.org') {
            $errors[] = '@context should be "https://schema.org"';
        }
        
        // @id should be a valid URL if present
        if (isset($piece['@id']) && !filter_var($piece['@id'], FILTER_VALIDATE_URL)) {
            $errors[] = '@id must be a valid URL';
        }
        
        // Validate specific common fields
        $errors = array_merge($errors, $this->validate_common_fields($piece));
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'piece_type' => $piece['@type'] ?? 'unknown'
        ];
    }
    
    /**
     * Validate multiple schema pieces
     */
    public function validate_schema_array(array $schemas): array
    {
        $results = [
            'valid' => true,
            'total_pieces' => count($schemas),
            'piece_results' => [],
            'summary' => []
        ];
        
        foreach ($schemas as $i => $schema) {
            $piece_result = $this->validate_piece($schema);
            $results['piece_results'][$i] = $piece_result;
            
            if (!$piece_result['valid']) {
                $results['valid'] = false;
            }
            
            $type = $piece_result['piece_type'];
            if (!isset($results['summary'][$type])) {
                $results['summary'][$type] = ['count' => 0, 'errors' => 0];
            }
            $results['summary'][$type]['count']++;
            
            if (!$piece_result['valid']) {
                $results['summary'][$type]['errors']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Validate common schema fields
     */
    private function validate_common_fields(array $piece): array
    {
        $errors = [];
        
        // URL fields should be valid URLs
        $url_fields = ['url', 'image', 'logo', 'photo'];
        foreach ($url_fields as $field) {
            if (isset($piece[$field])) {
                $value = is_array($piece[$field]) ? ($piece[$field]['url'] ?? null) : $piece[$field];
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = "Field '{$field}' must be a valid URL";
                }
            }
        }
        
        // Email fields should be valid emails  
        if (isset($piece['email']) && !filter_var($piece['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email field must be a valid email address';
        }
        
        // Date fields should be valid dates (basic check)
        $date_fields = ['datePublished', 'dateModified', 'startDate', 'endDate'];
        foreach ($date_fields as $field) {
            if (isset($piece[$field]) && !strtotime($piece[$field])) {
                $errors[] = "Field '{$field}' should be a valid date";
            }
        }
        
        return $errors;
    }
    
    /**
     * Quick validation check - just returns true/false
     */
    public function is_valid_piece(array $piece): bool
    {
        $result = $this->validate_piece($piece);
        return $result['valid'];
    }
    
    /**
     * Get validation summary for debugging
     */
    public function get_validation_summary(array $schemas): string
    {
        $results = $this->validate_schema_array($schemas);
        
        $summary = "Schema Validation Summary:\n";
        $summary .= "Total pieces: {$results['total_pieces']}\n";
        $summary .= "Overall valid: " . ($results['valid'] ? 'Yes' : 'No') . "\n\n";
        
        if (!empty($results['summary'])) {
            $summary .= "By type:\n";
            foreach ($results['summary'] as $type => $stats) {
                $summary .= "- {$type}: {$stats['count']} pieces";
                if ($stats['errors'] > 0) {
                    $summary .= " ({$stats['errors']} with errors)";
                }
                $summary .= "\n";
            }
        }
        
        return $summary;
    }
}