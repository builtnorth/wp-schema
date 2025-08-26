<?php

declare(strict_types=1);

/**
 * Verify Conflict Detection Implementation
 * 
 * This script verifies that conflict detection methods are properly implemented
 * in the ProductProvider and EventProvider classes.
 */

echo "\n=== Verifying Conflict Detection Implementation ===\n\n";

$providers_to_check = [
    'ProductProvider' => [
        'file' => __DIR__ . '/../inc/Providers/ProductProvider.php',
        'methods' => ['is_woocommerce_schema_enabled'],
        'conflict_check' => 'if ($this->is_woocommerce_schema_enabled())',
    ],
    'EventProvider' => [
        'file' => __DIR__ . '/../inc/Providers/EventProvider.php', 
        'methods' => ['is_tribe_events_schema_enabled'],
        'conflict_check' => 'if ($this->is_tribe_events_schema_enabled())',
    ],
];

$all_verified = true;

foreach ($providers_to_check as $provider => $config) {
    echo "Checking $provider...\n";
    
    if (!file_exists($config['file'])) {
        echo "  ❌ File not found: {$config['file']}\n";
        $all_verified = false;
        continue;
    }
    
    $content = file_get_contents($config['file']);
    
    // Check for conflict detection methods
    foreach ($config['methods'] as $method) {
        if (strpos($content, "private function $method") !== false) {
            echo "  ✓ Has $method() method\n";
        } else {
            echo "  ❌ Missing $method() method\n";
            $all_verified = false;
        }
    }
    
    // Check that conflict detection is used in can_provide
    if (strpos($content, $config['conflict_check']) !== false) {
        echo "  ✓ Uses conflict detection in can_provide()\n";
    } else {
        echo "  ❌ Not using conflict detection in can_provide()\n";
        $all_verified = false;
    }
    
    // Check for filter to allow override
    if (strpos($content, 'apply_filters') !== false) {
        echo "  ✓ Has filters for customization\n";
    } else {
        echo "  ❌ Missing filters for customization\n";
        $all_verified = false;
    }
    
    echo "\n";
}

// Verify that both providers return false when plugin schema is active
echo "Checking conflict prevention logic...\n";

$product_content = file_get_contents($providers_to_check['ProductProvider']['file']);
if (strpos($product_content, 'return false; // Let WooCommerce handle it') !== false) {
    echo "  ✓ ProductProvider prevents conflicts with WooCommerce\n";
} else {
    echo "  ❌ ProductProvider missing WooCommerce conflict prevention\n";
    $all_verified = false;
}

$event_content = file_get_contents($providers_to_check['EventProvider']['file']);
if (strpos($event_content, 'return false; // Let The Events Calendar handle it') !== false) {
    echo "  ✓ EventProvider prevents conflicts with The Events Calendar\n";
} else {
    echo "  ❌ EventProvider missing The Events Calendar conflict prevention\n";
    $all_verified = false;
}

echo "\n";

if ($all_verified) {
    echo "✅ All conflict detection implementations verified successfully!\n";
    echo "\nConflict detection ensures:\n";
    echo "- No duplicate Product schema when WooCommerce is active\n";
    echo "- No duplicate Event schema when The Events Calendar is active\n";
    echo "- Developers can override via filters if needed\n";
} else {
    echo "❌ Some conflict detection implementations need attention\n";
}

echo "\n";