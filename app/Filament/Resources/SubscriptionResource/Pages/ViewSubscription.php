<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Name'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('user.phone')
                            ->label('Phone'),
                        TextEntry::make('user.user_type')
                            ->label('User Type')
                            ->badge(),
                    ])->columns(2),
                    
                Section::make('Plan Details')
                    ->schema([
                        TextEntry::make('plan.name')
                            ->label('Plan Name')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('plan.price')
                            ->label('Price')
                            ->money('USD'),
                        TextEntry::make('plan.billing_cycle')
                            ->label('Billing Cycle')
                            ->badge(),
                    ])->columns(3),
                    
                Section::make('Subscription Status')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'trial' => 'warning',
                                'active' => 'success',
                                'expired' => 'danger',
                                'cancelled' => 'gray',
                                default => 'info',
                            }),
                        TextEntry::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->dateTime(),
                        TextEntry::make('starts_at')
                            ->label('Subscription Starts')
                            ->dateTime(),
                        TextEntry::make('ends_at')
                            ->label('Subscription Ends')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])->columns(3),
            ]);
    }
}
