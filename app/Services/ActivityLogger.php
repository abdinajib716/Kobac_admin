<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;

class ActivityLogger
{
    /**
     * Log a user activity for the activity feed
     * 
     * @param string $type Type of activity (income, expense, customer, vendor, stock, etc.)
     * @param string $action Action performed (created, updated, deleted, debit, credit, increase, decrease)
     * @param mixed $subject The model that was affected
     * @param array $properties Additional properties to log
     * @param mixed $causer The user who performed the action (defaults to auth user)
     */
    public static function log(
        string $type,
        string $action,
        $subject = null,
        array $properties = [],
        $causer = null
    ): ?Activity {
        try {
            $causer = $causer ?? auth()->user();
            
            $description = self::buildDescription($type, $action, $subject, $properties);
            
            $activity = activity($type)
                ->causedBy($causer)
                ->withProperties(array_merge([
                    'type' => $type,
                    'action' => $action,
                ], $properties));
            
            if ($subject) {
                $activity->performedOn($subject);
            }
            
            return $activity->log($description);
        } catch (\Exception $e) {
            // Log error silently to prevent API failures
            \Log::warning('ActivityLogger failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build human-readable description
     */
    private static function buildDescription(string $type, string $action, $subject, array $properties): string
    {
        $amount = isset($properties['amount']) ? '$' . number_format($properties['amount'], 2) : '';
        $name = $properties['name'] ?? ($subject->name ?? '');
        
        return match($type) {
            'income' => match($action) {
                'created' => "Recorded income of {$amount}",
                'updated' => "Updated income record",
                'deleted' => "Deleted income of {$amount}",
                default => "Income {$action}",
            },
            'expense' => match($action) {
                'created' => "Recorded expense of {$amount}",
                'updated' => "Updated expense record",
                'deleted' => "Deleted expense of {$amount}",
                default => "Expense {$action}",
            },
            'customer' => match($action) {
                'created' => "Created customer: {$name}",
                'updated' => "Updated customer: {$name}",
                'deleted' => "Deleted customer: {$name}",
                'debit' => "Customer {$name} debit: {$amount}",
                'credit' => "Customer {$name} credit: {$amount}",
                default => "Customer {$action}",
            },
            'vendor' => match($action) {
                'created' => "Created vendor: {$name}",
                'updated' => "Updated vendor: {$name}",
                'deleted' => "Deleted vendor: {$name}",
                'credit' => "Vendor {$name} credit: {$amount}",
                'debit' => "Vendor {$name} debit: {$amount}",
                default => "Vendor {$action}",
            },
            'stock' => match($action) {
                'created' => "Created stock item: {$name}",
                'updated' => "Updated stock item: {$name}",
                'deleted' => "Deleted stock item: {$name}",
                'increase' => "Increased stock: {$name} by " . ($properties['quantity'] ?? 0),
                'decrease' => "Decreased stock: {$name} by " . ($properties['quantity'] ?? 0),
                default => "Stock {$action}",
            },
            'account' => match($action) {
                'created' => "Created account: {$name}",
                'updated' => "Updated account: {$name}",
                'deleted' => "Deleted account: {$name}",
                default => "Account {$action}",
            },
            'branch' => match($action) {
                'created' => "Created branch: {$name}",
                'updated' => "Updated branch: {$name}",
                'deleted' => "Deleted branch: {$name}",
                default => "Branch {$action}",
            },
            'business' => match($action) {
                'created' => "Business setup completed: {$name}",
                'updated' => "Business profile updated",
                default => "Business {$action}",
            },
            default => ucfirst($type) . " " . $action,
        };
    }

    /**
     * Quick log methods
     */
    public static function income(string $action, $subject, array $properties = []): ?Activity
    {
        return self::log('income', $action, $subject, $properties);
    }

    public static function expense(string $action, $subject, array $properties = []): ?Activity
    {
        return self::log('expense', $action, $subject, $properties);
    }

    public static function customer(string $action, $subject, array $properties = []): ?Activity
    {
        return self::log('customer', $action, $subject, $properties);
    }

    public static function vendor(string $action, $subject, array $properties = []): ?Activity
    {
        return self::log('vendor', $action, $subject, $properties);
    }

    public static function stock(string $action, $subject, array $properties = []): ?Activity
    {
        return self::log('stock', $action, $subject, $properties);
    }

    public static function account(string $action, $subject, array $properties = []): ?Activity
    {
        return self::log('account', $action, $subject, $properties);
    }
}
