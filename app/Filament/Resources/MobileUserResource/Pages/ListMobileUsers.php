<?php

namespace App\Filament\Resources\MobileUserResource\Pages;

use App\Filament\Resources\MobileUserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMobileUsers extends ListRecords
{
    protected static string $resource = MobileUserResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users'),
            'individual' => Tab::make('Individual (FREE)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_type', 'individual'))
                ->badge(fn () => \App\Models\User::individuals()->count()),
            'business' => Tab::make('Business')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_type', 'business'))
                ->badge(fn () => \App\Models\User::businessUsers()->count()),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => \App\Models\User::mobileUsers()->active()->count()),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => \App\Models\User::mobileUsers()->where('is_active', false)->count()),
        ];
    }
}
