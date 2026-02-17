<?php

namespace App\Filament\Resources\BusinessContextResource\Pages;

use App\Filament\Resources\BusinessContextResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewBusinessContext extends ViewRecord
{
    protected static string $resource = BusinessContextResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Owner Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Owner Name'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('user.phone')
                            ->label('Phone'),
                        TextEntry::make('user.subscription.status')
                            ->label('Subscription Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'trial' => 'warning',
                                'active' => 'success',
                                'expired' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(4),
                    
                Section::make('Business Details')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Business Name'),
                        TextEntry::make('legal_name')
                            ->label('Legal Name'),
                        TextEntry::make('phone')
                            ->label('Phone'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('address')
                            ->label('Address')
                            ->columnSpanFull(),
                        TextEntry::make('currency')
                            ->label('Currency')
                            ->badge(),
                    ])->columns(4),
                    
                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('branches_count')
                            ->label('Total Branches')
                            ->getStateUsing(fn ($record) => $record->branches()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('customers_count')
                            ->label('Total Customers')
                            ->getStateUsing(fn ($record) => $record->customers()->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('vendors_count')
                            ->label('Total Vendors')
                            ->getStateUsing(fn ($record) => $record->vendors()->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('stock_items_count')
                            ->label('Stock Items')
                            ->getStateUsing(fn ($record) => $record->stockItems()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('income_total')
                            ->label('Total Income')
                            ->getStateUsing(fn ($record) => '$' . number_format(\App\Models\IncomeTransaction::where('business_id', $record->id)->sum('amount'), 2))
                            ->badge()
                            ->color('success'),
                        TextEntry::make('expense_total')
                            ->label('Total Expense')
                            ->getStateUsing(fn ($record) => '$' . number_format(\App\Models\ExpenseTransaction::where('business_id', $record->id)->sum('amount'), 2))
                            ->badge()
                            ->color('danger'),
                    ])->columns(6),
                    
                Section::make('Timeline')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
