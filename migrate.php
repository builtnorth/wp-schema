<?php

/**
 * Migration script to move schema code from wp-utility to wp-schema
 * 
 * This script copies the schema-related files from wp-utility to wp-schema
 * and updates the namespaces accordingly.
 */

// Source and destination paths
$source_dir = __DIR__ . '/../wp-utility/inc/Utilities/SchemaGenerator';
$dest_dir = __DIR__ . '/inc';

// Create destination directories
$directories = [
    $dest_dir,
    $dest_dir . '/Extractors',
    $dest_dir . '/Generators',
    $dest_dir . '/Detectors',
    $dest_dir . '/Utilities'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Files to copy with namespace updates
$files_to_copy = [
    // Main SchemaGenerator
    [
        'source' => $source_dir . '/../SchemaGenerator.php',
        'dest' => $dest_dir . '/SchemaGenerator.php',
        'namespace' => 'BuiltNorth\\Schema'
    ],
    
    // Extractors
    [
        'source' => $source_dir . '/Extractors/ContentExtractor.php',
        'dest' => $dest_dir . '/Extractors/ContentExtractor.php',
        'namespace' => 'BuiltNorth\\Schema\\Extractors'
    ],
    
    // Generators
    [
        'source' => $source_dir . '/Generators/BaseGenerator.php',
        'dest' => $dest_dir . '/Generators/BaseGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/OrganizationGenerator.php',
        'dest' => $dest_dir . '/Generators/OrganizationGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/LocalBusinessGenerator.php',
        'dest' => $dest_dir . '/Generators/LocalBusinessGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/WebSiteGenerator.php',
        'dest' => $dest_dir . '/Generators/WebSiteGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/ArticleGenerator.php',
        'dest' => $dest_dir . '/Generators/ArticleGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/ProductGenerator.php',
        'dest' => $dest_dir . '/Generators/ProductGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/FaqGenerator.php',
        'dest' => $dest_dir . '/Generators/FaqGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/PersonGenerator.php',
        'dest' => $dest_dir . '/Generators/PersonGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/AggregateRatingGenerator.php',
        'dest' => $dest_dir . '/Generators/AggregateRatingGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    [
        'source' => $source_dir . '/Generators/ReviewGenerator.php',
        'dest' => $dest_dir . '/Generators/ReviewGenerator.php',
        'namespace' => 'BuiltNorth\\Schema\\Generators'
    ],
    
    // Detectors
    [
        'source' => $source_dir . '/Detectors/ContentTypeDetector.php',
        'dest' => $dest_dir . '/Detectors/ContentTypeDetector.php',
        'namespace' => 'BuiltNorth\\Schema\\Detectors'
    ],
    [
        'source' => $source_dir . '/Detectors/PatternDetector.php',
        'dest' => $dest_dir . '/Detectors/PatternDetector.php',
        'namespace' => 'BuiltNorth\\Schema\\Detectors'
    ],
    [
        'source' => $source_dir . '/Detectors/PostTypeDetector.php',
        'dest' => $dest_dir . '/Detectors/PostTypeDetector.php',
        'namespace' => 'BuiltNorth\\Schema\\Detectors'
    ]
];

// Copy files and update namespaces
foreach ($files_to_copy as $file) {
    if (file_exists($file['source'])) {
        $content = file_get_contents($file['source']);
        
        // Update namespace
        $content = str_replace(
            'BuiltNorth\\Utility\\Utilities\\SchemaGenerator',
            $file['namespace'],
            $content
        );
        
        // Update use statements
        $content = str_replace(
            'BuiltNorth\\Utility\\Utilities\\SchemaGenerator\\',
            'BuiltNorth\\Schema\\',
            $content
        );
        
        // Write to destination
        file_put_contents($file['dest'], $content);
        echo "Copied and updated: " . basename($file['source']) . "\n";
    } else {
        echo "Source file not found: " . $file['source'] . "\n";
    }
}

// Copy README
if (file_exists($source_dir . '/README.md')) {
    copy($source_dir . '/README.md', $dest_dir . '/README.md');
    echo "Copied README.md\n";
}

echo "\nMigration completed!\n";
echo "Next steps:\n";
echo "1. Update wp-utility to use the new wp-schema package\n";
echo "2. Remove the old SchemaGenerator code from wp-utility\n";
echo "3. Test the new package functionality\n"; 