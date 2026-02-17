<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SystemFeatures extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Features List';
    
    protected static ?string $title = 'System Features';

    protected static string $view = 'filament.pages.system-features';

    public function getFeatures(): array
    {
        return [
            [
                'category' => 'Mobile App - Individual Users (FREE)',
                'icon' => 'heroicon-o-user',
                'color' => 'success',
                'features' => [
                    ['name' => 'Accounts', 'description' => 'Cash, Bank, Mobile Money accounts', 'status' => 'active'],
                    ['name' => 'Income', 'description' => 'Record income transactions', 'status' => 'active'],
                    ['name' => 'Expense', 'description' => 'Record expense transactions', 'status' => 'active'],
                    ['name' => 'Dashboard', 'description' => 'Personal finance overview', 'status' => 'active'],
                    ['name' => 'Activity', 'description' => 'Transaction timeline', 'status' => 'active'],
                    ['name' => 'Profile', 'description' => 'User profile management', 'status' => 'active'],
                ],
            ],
            [
                'category' => 'Mobile App - Business Users (Subscription)',
                'icon' => 'heroicon-o-building-office',
                'color' => 'info',
                'features' => [
                    ['name' => 'All Individual Features', 'description' => 'Includes all individual user features', 'status' => 'active'],
                    ['name' => 'Business Setup', 'description' => 'Company profile and settings', 'status' => 'active'],
                    ['name' => 'Multi-Branch', 'description' => 'Multiple business locations', 'status' => 'active'],
                    ['name' => 'Customers (Receivables)', 'description' => 'Track customer balances and debits/credits', 'status' => 'active'],
                    ['name' => 'Vendors (Payables)', 'description' => 'Track vendor balances and debits/credits', 'status' => 'active'],
                    ['name' => 'Stock Management', 'description' => 'Inventory tracking with increase/decrease', 'status' => 'active'],
                    ['name' => 'Profit & Loss', 'description' => 'P&L reports by category and date range', 'status' => 'active'],
                    ['name' => 'Business Dashboard', 'description' => 'Complete business overview', 'status' => 'active'],
                ],
            ],
            [
                'category' => 'Admin Panel',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'warning',
                'features' => [
                    ['name' => 'Dashboard', 'description' => 'System overview with widgets', 'status' => 'active'],
                    ['name' => 'Business Plans', 'description' => 'Create and manage subscription plans', 'status' => 'active'],
                    ['name' => 'Mobile Users', 'description' => 'View and manage mobile app users', 'status' => 'active'],
                    ['name' => 'Subscriptions', 'description' => 'View all user subscriptions', 'status' => 'active'],
                    ['name' => 'Business Overview', 'description' => 'Read-only view of business data', 'status' => 'active'],
                    ['name' => 'Admin Users', 'description' => 'Manage admin panel users', 'status' => 'active'],
                    ['name' => 'Roles & Permissions', 'description' => 'Access control management', 'status' => 'active'],
                    ['name' => 'Activity Logs', 'description' => 'System audit trail', 'status' => 'active'],
                    ['name' => 'Settings', 'description' => 'Site configuration', 'status' => 'active'],
                ],
            ],
            [
                'category' => 'API Endpoints',
                'icon' => 'heroicon-o-code-bracket',
                'color' => 'primary',
                'features' => [
                    ['name' => 'Authentication API', 'description' => 'Register, Login, Logout, Profile', 'status' => 'active'],
                    ['name' => 'Feature Discovery API', 'description' => 'GET /apps - Returns enabled features per user', 'status' => 'active'],
                    ['name' => 'Subscription Status API', 'description' => 'GET /subscription/status - Unified for all users', 'status' => 'active'],
                    ['name' => 'Plans API', 'description' => 'GET /plans - Available subscription plans', 'status' => 'active'],
                    ['name' => 'Accounts API', 'description' => 'CRUD for financial accounts', 'status' => 'active'],
                    ['name' => 'Income API', 'description' => 'CRUD for income transactions', 'status' => 'active'],
                    ['name' => 'Expense API', 'description' => 'CRUD for expense transactions', 'status' => 'active'],
                    ['name' => 'Business API', 'description' => 'Setup, branches, customers, vendors, stock, P&L', 'status' => 'active'],
                ],
            ],
            [
                'category' => 'Security & Middleware',
                'icon' => 'heroicon-o-shield-check',
                'color' => 'danger',
                'features' => [
                    ['name' => 'Sanctum Auth', 'description' => 'Token-based API authentication', 'status' => 'active'],
                    ['name' => 'User Active Check', 'description' => 'Blocks deactivated users', 'status' => 'active'],
                    ['name' => 'Subscription Write Lock', 'description' => 'Blocks writes for expired subscriptions', 'status' => 'active'],
                    ['name' => 'User Type Guard', 'description' => 'Restricts routes by user type', 'status' => 'active'],
                    ['name' => 'Feature Guard', 'description' => 'Blocks access to disabled features', 'status' => 'active'],
                    ['name' => 'Branch Context', 'description' => 'X-Branch-ID header enforcement', 'status' => 'active'],
                    ['name' => 'Activity Logging', 'description' => 'All write actions logged', 'status' => 'active'],
                ],
            ],
        ];
    }

    public function getStats(): array
    {
        return [
            'total_features' => 40,
            'api_endpoints' => 58,
            'middleware' => 5,
            'admin_resources' => 7,
        ];
    }
}
