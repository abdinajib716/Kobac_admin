<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Spatie\Permission\Models\Role;

class UsersChart extends ChartWidget
{
    protected static ?string $heading = 'Users by Role';
    
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '300px';

    public function getDescription(): ?string
    {
        return 'Distribution of users across different roles';
    }

    protected function getData(): array
    {
        $roles = Role::withCount('users')->get();
        
        return [
            'datasets' => [
                [
                    'label' => 'Users per role',
                    'data' => $roles->pluck('users_count')->toArray(),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',  // Blue
                        'rgb(16, 185, 129)',  // Green
                        'rgb(251, 146, 60)',  // Orange
                        'rgb(168, 85, 247)',  // Purple
                        'rgb(236, 72, 153)',  // Pink
                    ],
                ],
            ],
            'labels' => $roles->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 2,
        ];
    }
}
