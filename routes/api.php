<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
| Prefix: api/v1
| All mobile app endpoints are defined here
|--------------------------------------------------------------------------
|
| AUTHENTICATION:
|   All protected routes require: Authorization: Bearer {token}
|
| BRANCH CONTEXT (Business Users Only):
|   Header: X-Branch-ID: {branch_id}
|   - Optional header to specify which branch to operate on
|   - If not provided, defaults to main branch
|   - Validated against user's business ownership
|   - Invalid/inactive branch returns 400 error
|
| WRITE-BLOCKED ERRORS (Business Users):
|   When subscription.write middleware blocks a request:
|   {
|     "blocked": true,
|     "reason": "trial_expired|subscription_expired|no_subscription",
|     "action": "upgrade_required|renew_required|subscribe_required",
|     "can_read": true,
|     "can_write": false
|   }
|
| USER TYPES:
|   - individual: FREE, full access, no subscription needed
|   - business: Subscription-based, trial/paid states
|   - client: Admin panel users (not for mobile)
|
| ACTIVITY LOGGING:
|   All write actions automatically log to activity feed:
|   - Income create/delete
|   - Expense create/delete
|   - Customer create/debit/credit
|   - Vendor create/debit/credit
|   - Stock create/increase/decrease
|   - Account create/update
|
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Public Routes (No Authentication Required)
    |--------------------------------------------------------------------------
    */
    
    // Plans (for Business signup)
    Route::get('plans', [\App\Http\Controllers\Api\V1\PlanController::class, 'index']);
    
    // Support (WhatsApp widget config for mobile app)
    Route::get('support/whatsapp', [\App\Http\Controllers\Api\V1\SupportController::class, 'whatsapp']);

    // Localization payload for mobile runtime (no auth required)
    Route::prefix('localization')->group(function () {
        Route::get('languages', [\App\Http\Controllers\Api\V1\LocalizationController::class, 'languages']);
        Route::get('translations', [\App\Http\Controllers\Api\V1\LocalizationController::class, 'translations']);
    });
    
    // Locations (for registration - public access)
    Route::prefix('locations')->group(function () {
        Route::get('countries', [\App\Http\Controllers\Api\V1\LocationController::class, 'countries']);
        Route::get('countries/{countryId}/regions', [\App\Http\Controllers\Api\V1\LocationController::class, 'regions']);
        Route::get('regions/{regionId}/districts', [\App\Http\Controllers\Api\V1\LocationController::class, 'districts']);
        Route::get('hierarchy', [\App\Http\Controllers\Api\V1\LocationController::class, 'hierarchy']);
        Route::get('search', [\App\Http\Controllers\Api\V1\LocationController::class, 'search']);
    });
    
    // Authentication (Public)
    Route::prefix('auth')->group(function () {
        Route::post('register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register']);
        Route::post('login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
        // Password Reset Flow
        Route::post('forgot-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword']);
        Route::post('verify-reset-code', [\App\Http\Controllers\Api\V1\AuthController::class, 'verifyResetCode']);
        Route::post('reset-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Authentication Required)
    |--------------------------------------------------------------------------
    */
    
    Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
        
        // Auth (Protected)
        Route::prefix('auth')->group(function () {
            Route::post('logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
            Route::get('me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
            Route::post('change-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'changePassword']);
        });
        
        // Apps/Features Discovery (CRITICAL for mobile)
        Route::get('apps', [\App\Http\Controllers\Api\V1\AppController::class, 'index']);
        
        // Unified Subscription Status (works for BOTH Individual & Business)
        Route::get('subscription/status', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'status']);
        
        // Profile
        Route::put('profile', [\App\Http\Controllers\Api\V1\ProfileController::class, 'update']);
        Route::get('profile/preferences', [\App\Http\Controllers\Api\V1\ProfileController::class, 'preferences']);
        Route::put('profile/preferences', [\App\Http\Controllers\Api\V1\ProfileController::class, 'updatePreferences']);
        
        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index']);
        
        // Activity (branch-aware for business users, no-op for individual users)
        Route::middleware('branch.context')->get('activity', [\App\Http\Controllers\Api\V1\ActivityController::class, 'index']);
        
        /*
        |--------------------------------------------------------------------------
        | Individual & Business Shared Routes
        |--------------------------------------------------------------------------
        */
        
        // Accounts
        Route::apiResource('accounts', \App\Http\Controllers\Api\V1\AccountController::class);
        Route::post('accounts/{account}/deactivate', [\App\Http\Controllers\Api\V1\AccountController::class, 'deactivate']);
        Route::post('accounts/{account}/activate', [\App\Http\Controllers\Api\V1\AccountController::class, 'activate']);
        Route::get('accounts/{account}/ledger', [\App\Http\Controllers\Api\V1\AccountController::class, 'ledger']);
        
        // Global Search
        Route::get('search', [\App\Http\Controllers\Api\V1\SearchController::class, 'index']);
        
        // Income (with subscription check for business)
        Route::middleware('subscription.write')->group(function () {
            Route::apiResource('income', \App\Http\Controllers\Api\V1\IncomeController::class);
            Route::apiResource('expenses', \App\Http\Controllers\Api\V1\ExpenseController::class);
        });
        
        // Read-only income/expense for expired trials
        Route::get('income', [\App\Http\Controllers\Api\V1\IncomeController::class, 'index']);
        Route::get('income/{income}', [\App\Http\Controllers\Api\V1\IncomeController::class, 'show']);
        Route::get('expenses', [\App\Http\Controllers\Api\V1\ExpenseController::class, 'index']);
        Route::get('expenses/{expense}', [\App\Http\Controllers\Api\V1\ExpenseController::class, 'show']);
        
        /*
        |--------------------------------------------------------------------------
        | Business Only Routes
        | Header: X-Branch-ID (optional, defaults to main branch)
        |--------------------------------------------------------------------------
        */
        
        Route::middleware(['user.type:business', 'branch.context', 'subscription.write'])->prefix('business')->group(function () {
            
            // Business Setup
            Route::post('setup', [\App\Http\Controllers\Api\V1\Business\SetupController::class, 'store']);
            Route::get('profile', [\App\Http\Controllers\Api\V1\Business\SetupController::class, 'show']);
            Route::put('profile', [\App\Http\Controllers\Api\V1\Business\SetupController::class, 'update']);
            
            // Dashboard
            Route::get('dashboard', [\App\Http\Controllers\Api\V1\Business\DashboardController::class, 'index']);
            
            // Branches (with feature guard)
            Route::middleware('feature.enabled:branches')->group(function () {
                Route::apiResource('branches', \App\Http\Controllers\Api\V1\Business\BranchController::class);
            });
            
            // Customers (Receivables) - with feature guard
            Route::middleware('feature.enabled:customers')->group(function () {
                Route::apiResource('customers', \App\Http\Controllers\Api\V1\Business\CustomerController::class);
                Route::post('customers/{customer}/debit', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'debit']);
                Route::post('customers/{customer}/credit', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'credit']);
                Route::post('customers/{customer}/deactivate', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'deactivate']);
                Route::post('customers/{customer}/activate', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'activate']);
                Route::get('customers/{customer}/transactions', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'transactions']);
            });
            
            // Receivables Summary
            Route::get('receivables/summary', [\App\Http\Controllers\Api\V1\Business\SummaryController::class, 'receivables']);
            
            // Vendors (Payables) - with feature guard
            Route::middleware('feature.enabled:vendors')->group(function () {
                Route::apiResource('vendors', \App\Http\Controllers\Api\V1\Business\VendorController::class);
                Route::post('vendors/{vendor}/credit', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'credit']);
                Route::post('vendors/{vendor}/debit', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'debit']);
                Route::post('vendors/{vendor}/deactivate', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'deactivate']);
                Route::post('vendors/{vendor}/activate', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'activate']);
                Route::get('vendors/{vendor}/transactions', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'transactions']);
            });
            
            // Payables Summary
            Route::get('payables/summary', [\App\Http\Controllers\Api\V1\Business\SummaryController::class, 'payables']);
            
            // Stock - with feature guard
            Route::middleware('feature.enabled:stock')->group(function () {
                Route::apiResource('stock', \App\Http\Controllers\Api\V1\Business\StockController::class);
                Route::post('stock/{stock}/increase', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'increase']);
                Route::post('stock/{stock}/decrease', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'decrease']);
                Route::post('stock/{stock}/deactivate', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'deactivate']);
                Route::post('stock/{stock}/activate', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'activate']);
                Route::get('stock/{stock}/movements', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'movements']);
            });
            
            // Profit & Loss (Read-Only) - with feature guard
            Route::middleware('feature.enabled:profit_loss')->group(function () {
                Route::get('profit-loss', [\App\Http\Controllers\Api\V1\Business\ProfitLossController::class, 'index']);
            });
            
            // Users/Staff Management - with feature guard
            Route::middleware('feature.enabled:users')->group(function () {
                Route::get('users/permissions', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'permissions']);
                Route::get('users', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'index']);
                Route::post('users', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'store']);
                Route::get('users/{businessUser}', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'show']);
                Route::put('users/{businessUser}', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'update']);
                Route::delete('users/{businessUser}', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'destroy']);
                Route::post('users/{businessUser}/deactivate', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'deactivate']);
                Route::post('users/{businessUser}/activate', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'activate']);
                Route::post('users/{businessUser}/resend-invitation', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'resendInvitation']);
                Route::post('users/{businessUser}/reset-password', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'resetPassword']);
            });
        });
        
        // Business read-only routes (for expired trials) - with feature guards
        Route::middleware(['user.type:business', 'branch.context'])->prefix('business')->group(function () {
            Route::get('reports/export', [\App\Http\Controllers\Api\V1\Business\ReportExportController::class, 'export']);

            Route::middleware('feature.enabled:customers')->group(function () {
                Route::get('customers', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'index']);
                Route::get('customers/{customer}', [\App\Http\Controllers\Api\V1\Business\CustomerController::class, 'show']);
            });
            Route::middleware('feature.enabled:vendors')->group(function () {
                Route::get('vendors', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'index']);
                Route::get('vendors/{vendor}', [\App\Http\Controllers\Api\V1\Business\VendorController::class, 'show']);
            });
            Route::middleware('feature.enabled:stock')->group(function () {
                Route::get('stock', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'index']);
                Route::get('stock/{stock}', [\App\Http\Controllers\Api\V1\Business\StockController::class, 'show']);
            });
            Route::middleware('feature.enabled:profit_loss')->group(function () {
                Route::get('profit-loss', [\App\Http\Controllers\Api\V1\Business\ProfitLossController::class, 'index']);
            });
            Route::middleware('feature.enabled:users')->group(function () {
                Route::get('users', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'index']);
                Route::get('users/permissions', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'permissions']);
                Route::get('users/{businessUser}', [\App\Http\Controllers\Api\V1\Business\BusinessUserController::class, 'show']);
            });
        });
        
        /*
        |--------------------------------------------------------------------------
        | Push Notification Routes
        |--------------------------------------------------------------------------
        */
        
        Route::prefix('notifications')->group(function () {
            Route::post('register-token', [\App\Http\Controllers\Api\V1\NotificationController::class, 'registerToken']);
            Route::post('unregister-token', [\App\Http\Controllers\Api\V1\NotificationController::class, 'unregisterToken']);
            Route::get('history', [\App\Http\Controllers\Api\V1\NotificationController::class, 'history']);
        });
        
        /*
        |--------------------------------------------------------------------------
        | Subscription Routes (Business Only)
        |--------------------------------------------------------------------------
        */
        
        Route::middleware('user.type:business')->prefix('subscription')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'show']);
            Route::get('upgrade-options', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'upgradeOptions']);
            Route::get('payment-methods', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'paymentMethods']);
            Route::post('subscribe', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'subscribe']);
            Route::post('renew', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'renew']);
        });
        
        /*
        |--------------------------------------------------------------------------
        | Payment Routes (WaafiPay & Offline Payment Integration)
        |--------------------------------------------------------------------------
        */
        
        Route::prefix('payment')->group(function () {
            Route::get('methods', [\App\Http\Controllers\Api\PaymentController::class, 'methods']);
            Route::post('initiate', [\App\Http\Controllers\Api\PaymentController::class, 'initiate']);
            Route::post('status', [\App\Http\Controllers\Api\PaymentController::class, 'status']);
            Route::get('history', [\App\Http\Controllers\Api\PaymentController::class, 'history']);
            
            // Offline Payment Routes
            Route::prefix('offline')->group(function () {
                Route::post('/', [\App\Http\Controllers\Api\PaymentController::class, 'initiateOffline']);
                Route::post('status', [\App\Http\Controllers\Api\PaymentController::class, 'offlineStatus']);
                Route::get('instructions', [\App\Http\Controllers\Api\PaymentController::class, 'offlineInstructions']);
            });
        });
    });
    
    /*
    |--------------------------------------------------------------------------
    | WaafiPay Webhook (Public Route - No Authentication)
    |--------------------------------------------------------------------------
    */
    
    Route::post('waafipay/webhook', [\App\Http\Controllers\Api\PaymentController::class, 'webhook']);
});
