#!/usr/bin/env php
<?php

/**
 * Verify dynamic colors are working correctly
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "\n";
echo "========================================\n";
echo "  DYNAMIC COLOR VERIFICATION CHECK\n";
echo "========================================\n\n";

// Get all color settings
$colors = [
    'Primary Color' => Setting::get('theme_primary_color', 'NOT SET'),
    'Secondary Color' => Setting::get('theme_secondary_color', 'NOT SET'),
    'Light Navbar BG' => Setting::get('lite_navbar_bg', 'NOT SET'),
    'Light Sidebar BG' => Setting::get('lite_sidebar_bg', 'NOT SET'),
    'Light Navbar Text' => Setting::get('lite_navbar_text', 'NOT SET'),
    'Light Sidebar Text' => Setting::get('lite_sidebar_text', 'NOT SET'),
    'Dark Navbar BG' => Setting::get('dark_navbar_bg', 'NOT SET'),
    'Dark Sidebar BG' => Setting::get('dark_sidebar_bg', 'NOT SET'),
    'Dark Navbar Text' => Setting::get('dark_navbar_text', 'NOT SET'),
    'Dark Sidebar Text' => Setting::get('dark_sidebar_text', 'NOT SET'),
];

echo "📊 CURRENT COLOR CONFIGURATION:\n";
echo "--------------------------------\n";
foreach ($colors as $label => $value) {
    $status = ($value !== 'NOT SET') ? '✅' : '❌';
    echo "{$status} {$label}: {$value}\n";
}

echo "\n";
echo "🔍 BUTTON COLOR PREVIEW:\n";
echo "--------------------------------\n";
$primary = Setting::get('theme_primary_color', '#0a6679');
echo "Your buttons will appear in: {$primary}\n";
echo "This color is applied to:\n";
echo "  • New/Create buttons\n";
echo "  • Submit buttons\n";
echo "  • Primary action buttons\n";
echo "  • Edit/View/Delete actions\n";
echo "  • Active menu items\n";
echo "  • Form focus borders\n";
echo "  • Checkboxes (when checked)\n";
echo "  • Table row hover\n";
echo "  • Tabs (active state)\n";
echo "  • Pagination (active page)\n";
echo "  • Links throughout the app\n";

echo "\n";
echo "🧪 WHAT TO CHECK IN YOUR BROWSER:\n";
echo "--------------------------------\n";
echo "1. Go to: /admin/users\n";
echo "2. Look at the 'New User' button (top right)\n";
echo "3. It should be colored: {$primary}\n";
echo "4. Hover over table rows - should show subtle tint\n";
echo "5. Click any input field - border should turn {$primary}\n";
echo "6. Toggle dark mode - colors should adapt\n";

echo "\n";
echo "✅ If you see {$primary} on buttons, colors are working!\n";
echo "🔄 If not, do a hard refresh: Ctrl+F5 (or Cmd+Shift+R)\n";

echo "\n";
echo "========================================\n\n";

exit(0);
