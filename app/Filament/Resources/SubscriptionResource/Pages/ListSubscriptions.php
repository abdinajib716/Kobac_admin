<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'trial' => Tab::make('Trial')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'trial'))
                ->badge(fn () => \App\Models\Subscription::where('status', 'trial')->count()),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => \App\Models\Subscription::where('status', 'active')->count()),
            'expired' => Tab::make('Expired')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'expired'))
                ->badge(fn () => \App\Models\Subscription::where('status', 'expired')->count()),
        ];
    }
}
