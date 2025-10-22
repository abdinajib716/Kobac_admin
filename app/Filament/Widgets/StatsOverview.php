<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, User::count()]),
            
            Stat::make('Total Roles', Role::count())
                ->description('User roles configured')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info')
                ->chart([2, 3, 4, 5, Role::count()]),
            
            Stat::make('Total Permissions', Permission::count())
                ->description('Permissions in the system')
                ->descriptionIcon('heroicon-m-key')
                ->color('warning')
                ->chart([10, 15, 20, 25, 30, Permission::count()]),
            
            Stat::make('Active Sessions', User::whereNotNull('email_verified_at')->count())
                ->description('Users with verified emails')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),
        ];
    }
}
