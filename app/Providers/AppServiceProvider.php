<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamic CSS disabled - using original Filament defaults
        // FilamentView::registerRenderHook(
        //     'panels::styles.before',
        //     fn (): string => $this->getDynamicThemeStyles()
        // );
    }
    
    /**
     * Generate dynamic theme styles from database settings
     */
    protected function getDynamicThemeStyles(): string
    {
        $primaryColor = Setting::get('theme_primary_color', '#0a6679');
        $secondaryColor = Setting::get('theme_secondary_color', '#1f2937');
        
        // Lite mode colors
        $liteNavbarBg = Setting::get('lite_navbar_bg', '#ffffff');
        $liteSidebarBg = Setting::get('lite_sidebar_bg', '#ffffff');
        $liteNavbarText = Setting::get('lite_navbar_text', '#090909');
        $liteSidebarText = Setting::get('lite_sidebar_text', '#090909');
        
        // Dark mode colors
        $darkNavbarBg = Setting::get('dark_navbar_bg', '#171f2e');
        $darkSidebarBg = Setting::get('dark_sidebar_bg', '#171f2e');
        $darkNavbarText = Setting::get('dark_navbar_text', '#ffffff');
        $darkSidebarText = Setting::get('dark_sidebar_text', '#ffffff');
        
        // RGB values for opacity variations
        $primaryRgb = $this->hexToRgb($primaryColor);
        $secondaryRgb = $this->hexToRgb($secondaryColor);
        $liteTextRgb = $this->hexToRgb($liteSidebarText);
        $darkTextRgb = $this->hexToRgb($darkSidebarText);
        
        return <<<CSS
        <style>
            :root {
                --theme-primary: {$primaryColor};
                --theme-primary-rgb: {$primaryRgb};
                --theme-secondary: {$secondaryColor};
                --theme-secondary-rgb: {$secondaryRgb};
            }
            
            /* ========== LIGHT MODE ========== */
            .fi-body:not(.dark) .fi-topbar {
                background-color: {$liteNavbarBg} !important;
                color: {$liteNavbarText} !important;
            }
            
            .fi-body:not(.dark) .fi-topbar-item button,
            .fi-body:not(.dark) .fi-topbar-item a {
                color: {$liteNavbarText} !important;
            }
            
            .fi-body:not(.dark) .fi-topbar-item button:hover {
                background-color: rgba({$liteTextRgb}, 0.05) !important;
            }
            
            .fi-body:not(.dark) .fi-sidebar {
                background-color: {$liteSidebarBg} !important;
            }
            
            .fi-body:not(.dark) .fi-sidebar-nav a,
            .fi-body:not(.dark) .fi-sidebar-nav button {
                color: {$liteSidebarText} !important;
            }
            
            .fi-body:not(.dark) .fi-sidebar-item-button:hover {
                background-color: rgba({$liteTextRgb}, 0.05) !important;
            }
            
            /* Sidebar Active Item - Multiple selectors for compatibility */
            .fi-body:not(.dark) .fi-sidebar-item.fi-active,
            .fi-body:not(.dark) .fi-sidebar-item[aria-current="page"],
            .fi-body:not(.dark) li.fi-active > a,
            .fi-body:not(.dark) li.fi-active > button,
            .fi-body:not(.dark) .fi-sidebar-nav [data-active="true"] {
                background-color: rgba({$primaryRgb}, 0.15) !important;
            }
            
            .fi-body:not(.dark) .fi-sidebar-item.fi-active *,
            .fi-body:not(.dark) .fi-sidebar-item[aria-current="page"] *,
            .fi-body:not(.dark) li.fi-active *,
            .fi-body:not(.dark) .fi-sidebar-nav [data-active="true"] * {
                color: {$primaryColor} !important;
                font-weight: 600 !important;
            }
            
            /* Buttons & Actions - Comprehensive Light Mode */
            .fi-body:not(.dark) .fi-btn-primary,
            .fi-body:not(.dark) button[type="submit"],
            .fi-body:not(.dark) .fi-header-actions button,
            .fi-body:not(.dark) [class*="fi-header"] button,
            .fi-body:not(.dark) .fi-ac-header-action button,
            .fi-body:not(.dark) a.fi-btn-primary,
            .fi-body:not(.dark) a[class*="fi-btn"],
            .fi-body:not(.dark) button[class*="fi-btn"]:not([class*="outlined"]),
            html:not(.dark) button[type="submit"],
            html:not(.dark) .fi-btn-primary,
            html:not(.dark) [class*="fi-header"] button {
                background-color: {$primaryColor} !important;
                border-color: {$primaryColor} !important;
                color: white !important;
            }
            
            .fi-body:not(.dark) .fi-btn-primary:hover,
            .fi-body:not(.dark) button[type="submit"]:hover,
            .fi-body:not(.dark) .fi-header-actions button:hover,
            .fi-body:not(.dark) [class*="fi-header"] button:hover,
            html:not(.dark) button[type="submit"]:hover,
            html:not(.dark) .fi-btn-primary:hover {
                background-color: rgba({$primaryRgb}, 0.85) !important;
                border-color: rgba({$primaryRgb}, 0.85) !important;
            }
            
            .fi-body:not(.dark) a:not(.fi-btn):not([class*="fi-topbar"]) {
                color: {$primaryColor} !important;
            }
            
            /* Tabs - Multiple selectors */
            .fi-body:not(.dark) .fi-tabs-item[aria-selected="true"],
            .fi-body:not(.dark) button[role="tab"][aria-selected="true"],
            .fi-body:not(.dark) [data-state="active"] {
                color: {$primaryColor} !important;
                border-bottom-color: {$primaryColor} !important;
                font-weight: 600 !important;
            }
            
            .fi-body:not(.dark) .fi-tabs-item[aria-selected="true"] *,
            .fi-body:not(.dark) button[role="tab"][aria-selected="true"] * {
                color: {$primaryColor} !important;
            }
            
            /* Forms */
            .fi-body:not(.dark) input:focus,
            .fi-body:not(.dark) textarea:focus,
            .fi-body:not(.dark) select:focus {
                border-color: {$primaryColor} !important;
                --tw-ring-color: rgba({$primaryRgb}, 0.2) !important;
            }
            
            /* Checkboxes & Radios */
            .fi-body:not(.dark) input[type="checkbox"]:checked,
            .fi-body:not(.dark) input[type="radio"]:checked {
                background-color: {$primaryColor} !important;
                border-color: {$primaryColor} !important;
            }
            
            /* Toggle Switch */
            .fi-body:not(.dark) .fi-fo-toggle input:checked + div {
                background-color: {$primaryColor} !important;
            }
            
            /* Table Hover */
            .fi-body:not(.dark) .fi-ta-row:hover {
                background-color: rgba({$primaryRgb}, 0.05) !important;
            }
            
            /* Action Buttons */
            .fi-body:not(.dark) .fi-ac-btn-action {
                color: {$primaryColor} !important;
            }
            
            .fi-body:not(.dark) .fi-ac-btn-action:hover {
                background-color: rgba({$primaryRgb}, 0.1) !important;
            }
            
            /* ========== DARK MODE ========== */
            .fi-body.dark .fi-topbar {
                background-color: {$darkNavbarBg} !important;
                color: {$darkNavbarText} !important;
            }
            
            .fi-body.dark .fi-topbar-item button,
            .fi-body.dark .fi-topbar-item a {
                color: {$darkNavbarText} !important;
            }
            
            .fi-body.dark .fi-topbar-item button:hover {
                background-color: rgba({$darkTextRgb}, 0.1) !important;
            }
            
            .fi-body.dark .fi-sidebar {
                background-color: {$darkSidebarBg} !important;
            }
            
            .fi-body.dark .fi-sidebar-nav a,
            .fi-body.dark .fi-sidebar-nav button {
                color: {$darkSidebarText} !important;
            }
            
            .fi-body.dark .fi-sidebar-item-button:hover {
                background-color: rgba({$darkTextRgb}, 0.1) !important;
            }
            
            /* Sidebar Active Item - Dark Mode */
            .fi-body.dark .fi-sidebar-item.fi-active,
            .fi-body.dark .fi-sidebar-item[aria-current="page"],
            .fi-body.dark li.fi-active > a,
            .fi-body.dark li.fi-active > button,
            .fi-body.dark .fi-sidebar-nav [data-active="true"] {
                background-color: rgba({$primaryRgb}, 0.25) !important;
            }
            
            .fi-body.dark .fi-sidebar-item.fi-active *,
            .fi-body.dark .fi-sidebar-item[aria-current="page"] *,
            .fi-body.dark li.fi-active *,
            .fi-body.dark .fi-sidebar-nav [data-active="true"] * {
                color: {$primaryColor} !important;
                font-weight: 600 !important;
            }
            
            /* Buttons & Actions - Comprehensive Dark Mode */
            .fi-body.dark .fi-btn-primary,
            .fi-body.dark button[type="submit"],
            .fi-body.dark .fi-header-actions button,
            .fi-body.dark [class*="fi-header"] button,
            .fi-body.dark .fi-ac-header-action button,
            .fi-body.dark a.fi-btn-primary,
            .fi-body.dark a[class*="fi-btn"],
            .fi-body.dark button[class*="fi-btn"]:not([class*="outlined"]),
            html.dark button[type="submit"],
            html.dark .fi-btn-primary,
            html.dark [class*="fi-header"] button {
                background-color: {$primaryColor} !important;
                border-color: {$primaryColor} !important;
                color: white !important;
            }
            
            .fi-body.dark .fi-btn-primary:hover,
            .fi-body.dark button[type="submit"]:hover,
            .fi-body.dark .fi-header-actions button:hover,
            .fi-body.dark [class*="fi-header"] button:hover,
            html.dark button[type="submit"]:hover,
            html.dark .fi-btn-primary:hover {
                background-color: rgba({$primaryRgb}, 0.85) !important;
                border-color: rgba({$primaryRgb}, 0.85) !important;
            }
            
            .fi-body.dark a:not(.fi-btn):not([class*="fi-topbar"]) {
                color: {$primaryColor} !important;
            }
            
            /* Tabs - Dark Mode */
            .fi-body.dark .fi-tabs-item[aria-selected="true"],
            .fi-body.dark button[role="tab"][aria-selected="true"],
            .fi-body.dark [data-state="active"] {
                color: {$primaryColor} !important;
                border-bottom-color: {$primaryColor} !important;
                font-weight: 600 !important;
            }
            
            .fi-body.dark .fi-tabs-item[aria-selected="true"] *,
            .fi-body.dark button[role="tab"][aria-selected="true"] * {
                color: {$primaryColor} !important;
            }
            
            /* Forms */
            .fi-body.dark input:focus,
            .fi-body.dark textarea:focus,
            .fi-body.dark select:focus {
                border-color: {$primaryColor} !important;
                --tw-ring-color: rgba({$primaryRgb}, 0.3) !important;
            }
            
            /* Checkboxes & Radios */
            .fi-body.dark input[type="checkbox"]:checked,
            .fi-body.dark input[type="radio"]:checked {
                background-color: {$primaryColor} !important;
                border-color: {$primaryColor} !important;
            }
            
            /* Toggle Switch */
            .fi-body.dark .fi-fo-toggle input:checked + div {
                background-color: {$primaryColor} !important;
            }
            
            /* Table Hover */
            .fi-body.dark .fi-ta-row:hover {
                background-color: rgba({$primaryRgb}, 0.05) !important;
            }
            
            /* Action Buttons */
            .fi-body.dark .fi-ac-btn-action {
                color: {$primaryColor} !important;
            }
            
            .fi-body.dark .fi-ac-btn-action:hover {
                background-color: rgba({$primaryRgb}, 0.2) !important;
            }
            
            /* ========== UNIVERSAL (Both Modes) ========== */
            
            /* Badges */
            .fi-badge {
                color: #ffffff !important;
                font-weight: 500 !important;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                padding: 0.25rem 0.625rem !important;
            }
            
            /* Pagination Active */
            .fi-pagination-item-active {
                background-color: {$primaryColor} !important;
                border-color: {$primaryColor} !important;
                color: white !important;
            }
            
            /* Loading Spinner */
            .fi-spinner {
                border-top-color: {$primaryColor} !important;
            }
            
            /* Progress Bar */
            .fi-progress-bar-fill {
                background-color: {$primaryColor} !important;
            }
            
            /* Dropdown Selected */
            [role="option"][aria-selected="true"] {
                background-color: rgba({$primaryRgb}, 0.1) !important;
                color: {$primaryColor} !important;
            }
            
            /* Notification Success */
            .fi-no-notification-success {
                border-left-color: {$primaryColor} !important;
            }
            
            /* Section Headers */
            .fi-section-header-heading {
                color: {$primaryColor} !important;
            }
            
            /* Stats Cards */
            .fi-stats-overview-stat {
                border-left-color: {$primaryColor} !important;
            }
            
            /* ========== FIX LAYOUT GAPS/SPACING ========== */
            
            /* Remove excessive page height causing black gaps */
            .fi-main {
                min-height: calc(100vh - 4rem) !important;
            }
            
            /* Fix page container */
            .fi-page {
                min-height: auto !important;
            }
            
            /* Reduce bottom padding */
            .fi-main-content {
                padding-bottom: 1.5rem !important;
            }
            
            /* Compact table margins */
            .fi-ta-ctn {
                margin-bottom: 0 !important;
            }
            
            /* Fix excessive spacing after tables */
            .fi-ta {
                margin-bottom: 1rem !important;
            }
            
            /* Compact page content */
            .fi-page-content {
                padding-bottom: 1rem !important;
            }
            
            /* Remove extra body padding */
            .fi-body {
                padding-bottom: 0 !important;
            }
        </style>
        CSS;
    }
    
    /**
     * Convert HEX color to RGB format (r, g, b)
     */
    protected function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }
}
