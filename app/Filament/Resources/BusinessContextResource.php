<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessContextResource\Pages;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusinessContextResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Subscription Management';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Business Overview';
    
    protected static ?string $modelLabel = 'Business';
    
    protected static ?string $pluralModelLabel = 'Businesses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Business Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('legal_name')
                            ->label('Legal Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('phone')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->disabled(),
                        Forms\Components\Textarea::make('address')
                            ->disabled(),
                        Forms\Components\TextInput::make('currency')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Owner Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Business Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Branches')
                    ->counts('branches')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('income_count')
                    ->label('Income Entries')
                    ->getStateUsing(function ($record) {
                        return \App\Models\IncomeTransaction::where('business_id', $record->id)->count();
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('expense_count')
                    ->label('Expense Entries')
                    ->getStateUsing(function ($record) {
                        return \App\Models\ExpenseTransaction::where('business_id', $record->id)->count();
                    })
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('customers_count')
                    ->label('Customers')
                    ->counts('customers')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('vendors_count')
                    ->label('Vendors')
                    ->counts('vendors')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('stock_items_count')
                    ->label('Stock Items')
                    ->counts('stockItems')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('user.subscription.status')
                    ->label('Subscription')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'trial' => 'warning',
                        'active' => 'success',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->label('Subscription Status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'expired' => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            return $query->whereHas('user.subscription', function ($q) use ($data) {
                                $q->where('status', $data['value']);
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessContexts::route('/'),
            'view' => Pages\ViewBusinessContext::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
}
