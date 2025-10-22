<?php

namespace App\View\Composers;

use App\Models\Setting;
use Illuminate\View\View;

class ThemeComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $themeColors = [
            'primary_color' => Setting::get('theme_primary_color', '#0a6679'),
            'secondary_color' => Setting::get('theme_secondary_color', '#1f2937'),
            'default_mode' => Setting::get('default_mode', 'lite'),
            
            // Lite mode colors
            'lite_navbar_bg' => Setting::get('lite_navbar_bg', '#ffffff'),
            'lite_sidebar_bg' => Setting::get('lite_sidebar_bg', '#ffffff'),
            'lite_navbar_text' => Setting::get('lite_navbar_text', '#090909'),
            'lite_sidebar_text' => Setting::get('lite_sidebar_text', '#090909'),
            
            // Dark mode colors
            'dark_navbar_bg' => Setting::get('dark_navbar_bg', '#171f2e'),
            'dark_sidebar_bg' => Setting::get('dark_sidebar_bg', '#171f2e'),
            'dark_navbar_text' => Setting::get('dark_navbar_text', '#ffffff'),
            'dark_sidebar_text' => Setting::get('dark_sidebar_text', '#ffffff'),
        ];
        
        $view->with('themeColors', $themeColors);
    }
}
