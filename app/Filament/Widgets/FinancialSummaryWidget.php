<?php

namespace App\Filament\Widgets;

use App\Models\IncomeTransaction;
use App\Models\ExpenseTransaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Forms\Components\DatePicker;
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
        $totalIncome = IncomeTransaction::sum('amount');
        $totalExpense = ExpenseTransaction::sum('amount');
        $netPosition = $totalIncome - $totalExpense;
        
        $monthlyIncome = IncomeTransaction::whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');
            
        $monthlyExpense = ExpenseTransaction::whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        $individualUsers = User::individuals()->count();
        $businessUsers = User::businessUsers()->count();

        return [
            Stat::make('Total Income (All Time)', '$' . number_format($totalIncome, 2))
                ->description('System-wide income')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getIncomeChart()),
            
            Stat::make('Total Expense (All Time)', '$' . number_format($totalExpense, 2))
                ->description('System-wide expense')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart($this->getExpenseChart()),
            
            Stat::make('Net Position', '$' . number_format($netPosition, 2))
                ->description($netPosition >= 0 ? 'Positive' : 'Negative')
                ->descriptionIcon($netPosition >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($netPosition >= 0 ? 'success' : 'danger'),
            
            Stat::make('This Month Income', '$' . number_format($monthlyIncome, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            
            Stat::make('Individual Users', $individualUsers)
                ->description('FREE users')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),
            
            Stat::make('Business Users', $businessUsers)
                ->description('Subscription-based')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }

    protected function getIncomeChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = IncomeTransaction::whereDate('transaction_date', $date)->sum('amount');
        }
        return $data;
    }

    protected function getExpenseChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = ExpenseTransaction::whereDate('transaction_date', $date)->sum('amount');
        }
        return $data;
    }
}
