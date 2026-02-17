<?php

namespace App\Filament\Resources\MobileUserResource\Pages;

use App\Filament\Resources\MobileUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;

class ViewMobileUser extends ViewRecord
{
    protected static string $resource = MobileUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => !$this->record->is_active)
                ->requiresConfirmation()
                ->action(fn () => $this->record->activate()),
            Actions\Action::make('deactivate')
                ->label('Deactivate')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->is_active)
                ->requiresConfirmation()
                ->action(fn () => $this->record->deactivate(auth()->id())),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name'),
                                TextEntry::make('email')
                                    ->label('Email'),
                                TextEntry::make('phone')
                                    ->label('Phone'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user_type')
                                    ->label('User Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'individual' => 'success',
                                        'business' => 'info',
                                        default => 'gray',
                                    }),
                                IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('created_at')
                                    ->label('Signup Date')
                                    ->dateTime(),
                            ]),
                    ]),
                    
                Section::make('Location')
                    ->visible(fn ($record) => $record->country_id || $record->region_id || $record->district_id)
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('country.name')
                                    ->label('Country')
                                    ->icon('heroicon-o-globe-alt')
                                    ->placeholder('Not set'),
                                TextEntry::make('region.name')
                                    ->label('Region')
                                    ->icon('heroicon-o-map')
                                    ->placeholder('Not set'),
                                TextEntry::make('district.name')
                                    ->label('District')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('Not set'),
                                TextEntry::make('address')
                                    ->label('Address')
                                    ->icon('heroicon-o-home')
                                    ->placeholder('Not set'),
                            ]),
                    ]),
                    
                Section::make('Subscription Details')
                    ->visible(fn ($record) => $record->isBusiness())
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('subscription.plan.name')
                                    ->label('Plan'),
                                TextEntry::make('subscription.status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'trial' => 'warning',
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('subscription.trial_ends_at')
                                    ->label('Trial Ends')
                                    ->dateTime(),
                                TextEntry::make('subscription.days_remaining')
                                    ->label('Days Remaining')
                                    ->suffix(' days'),
                            ]),
                    ]),
                    
                Section::make('Individual Account')
                    ->visible(fn ($record) => $record->isIndividual())
                    ->schema([
                        TextEntry::make('free_status')
                            ->label('Status')
                            ->state('FREE - Full Access')
                            ->badge()
                            ->color('success'),
                    ]),
                    
                Section::make('Business Context')
                    ->visible(fn ($record) => $record->isBusiness() && $record->business)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('business.name')
                                    ->label('Business Name'),
                                TextEntry::make('business.branches_count')
                                    ->label('Branches')
                                    ->state(fn ($record) => $record->business?->branches()->count() ?? 0),
                                TextEntry::make('business.currency')
                                    ->label('Currency'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('income_count')
                                    ->label('Income Entries')
                                    ->state(fn ($record) => $record->business?->incomeTransactions()->count() ?? 0),
                                TextEntry::make('expense_count')
                                    ->label('Expense Entries')
                                    ->state(fn ($record) => $record->business?->expenseTransactions()->count() ?? 0),
                                TextEntry::make('customers_count')
                                    ->label('Customers')
                                    ->state(fn ($record) => $record->business?->customers()->count() ?? 0),
                                TextEntry::make('stock_count')
                                    ->label('Stock Items')
                                    ->state(fn ($record) => $record->business?->stockItems()->count() ?? 0),
                            ]),
                    ]),
            ]);
    }
}
