<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ActivityChart extends ChartWidget
{
    protected static ?string $heading = 'User Registrations (Last 7 Days)';
    
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '300px';

    public function getDescription(): ?string
    {
        return 'Daily user registration activity for the past week';
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        // Get last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            $data[] = User::whereDate('created_at', $date)->count();
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
