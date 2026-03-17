<?php

namespace App\Filament\Widgets;

use App\Models\PaymentTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class FinancialSummaryWidget extends BaseWidget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $successfulStatuses = ['success', 'approved'];
        $pendingStatuses = ['pending', 'processing', 'pending_approval'];
        $failedStatuses = ['failed', 'rejected'];

        $totalSubscriptionIncome = PaymentTransaction::whereIn('status', $successfulStatuses)
            ->sum('amount');

        $totalSubscriptionTransactions = PaymentTransaction::whereIn('status', $successfulStatuses)
            ->count();

        $monthlySubscriptionIncome = PaymentTransaction::whereIn('status', $successfulStatuses)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $monthlySubscriptionTransactions = PaymentTransaction::whereIn('status', $successfulStatuses)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $pendingSubscriptionTransactions = PaymentTransaction::whereIn('status', $pendingStatuses)
            ->count();

        $failedSubscriptionTransactions = PaymentTransaction::whereIn('status', $failedStatuses)
            ->count();

        return [
            Stat::make('Total Subscription Revenue (All Time)', '$' . number_format($totalSubscriptionIncome, 2))
                ->description('Successful subscription payments')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getSubscriptionIncomeChart()),
            
            Stat::make('Successful Subscription Transactions', $totalSubscriptionTransactions)
                ->description('All-time successful transactions')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),
            
            Stat::make('This Month Subscription Revenue', '$' . number_format($monthlySubscriptionIncome, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
            
            Stat::make('This Month Subscription Transactions', $monthlySubscriptionTransactions)
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            
            Stat::make('Pending Subscription Transactions', $pendingSubscriptionTransactions)
                ->description('Awaiting completion or approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Failed/Rejected Transactions', $failedSubscriptionTransactions)
                ->description('Need retry or review')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    protected function getSubscriptionIncomeChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = PaymentTransaction::whereIn('status', ['success', 'approved'])
                ->whereDate('created_at', $date)
                ->sum('amount');
        }
        return $data;
    }
}
