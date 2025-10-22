#!/usr/bin/env php
<?php

/**
 * Quick test script to verify dynamic theme colors are loading correctly
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "\n";
echo "===========================================\n";
echo "   DYNAMIC THEME COLOR VERIFICATION TEST\n";
echo "===========================================\n\n";

$colors = [
    'theme_primary_color' => 'Theme Primary Color',
    'theme_secondary_color' => 'Theme Secondary Color',
    'default_mode' => 'Default Mode',
    'lite_navbar_bg' => 'Light Mode - Navbar Background',
    'lite_sidebar_bg' => 'Light Mode - Sidebar Background',
    'lite_navbar_text' => 'Light Mode - Navbar Text',
    'lite_sidebar_text' => 'Light Mode - Sidebar Text',
    'dark_navbar_bg' => 'Dark Mode - Navbar Background',
    'dark_sidebar_bg' => 'Dark Mode - Sidebar Background',
    'dark_navbar_text' => 'Dark Mode - Navbar Text',
    'dark_sidebar_text' => 'Dark Mode - Sidebar Text',
];

$allFound = true;

foreach ($colors as $key => $label) {
    $value = Setting::get($key);
    
    if ($value) {
        echo "✅ {$label}: {$value}\n";
    } else {
        echo "❌ {$label}: NOT SET\n";
        $allFound = false;
    }
}

echo "\n===========================================\n";

if ($allFound) {
    echo "✅ ALL THEME COLORS LOADED SUCCESSFULLY!\n";
    echo "   Your dynamic theme system is working.\n";
} else {
    echo "⚠️  Some colors are missing.\n";
    echo "   Please save settings in the admin panel.\n";
}

echo "===========================================\n\n";

exit($allFound ? 0 : 1);
