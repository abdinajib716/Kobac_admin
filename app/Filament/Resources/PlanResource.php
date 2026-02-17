<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Subscription Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Business Plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Information')
                    ->description('Business plans for subscription management. Individual users are FREE.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Plan Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
                    
                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->default(0.01)
                                    ->helperText('Minimum price: $0.01'),
                                Forms\Components\Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => 'USD - US Dollar',
                                        'EUR' => 'EUR - Euro',
                                        'GBP' => 'GBP - British Pound',
                                        'SOS' => 'SOS - Somali Shilling',
                                    ])
                                    ->default('USD')
                                    ->required(),
                                Forms\Components\Select::make('billing_cycle')
                                    ->label('Billing Cycle')
                                    ->options([
                                        'weekly' => 'Weekly (7 days)',
                                        'monthly' => 'Monthly (30 days)',
                                        'quarterly' => 'Quarterly (90 days)',
                                        'yearly' => 'Yearly (365 days)',
                                        'lifetime' => 'Lifetime',
                                        'custom' => 'Custom (Set days)',
                                    ])
                                    ->default('monthly')
                                    ->required()
                                    ->live(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('billing_days')
                                    ->label('Custom Billing Days')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(3650)
                                    ->helperText('Override billing cycle with custom days (e.g., 15, 45, 90)')
                                    ->visible(fn ($get) => $get('billing_cycle') === 'custom')
                                    ->required(fn ($get) => $get('billing_cycle') === 'custom'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Trial Configuration')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('trial_enabled')
                                    ->label('Enable Trial Period')
                                    ->default(false)
                                    ->live(),
                                Forms\Components\TextInput::make('trial_days')
                                    ->label('Trial Days')
                                    ->numeric()
                                    ->default(14)
                                    ->minValue(1)
                                    ->maxValue(90)
                                    ->visible(fn ($get) => $get('trial_enabled')),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Features & Modules')
                    ->description('Enable/disable features for this plan. These control what business users can access.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('features.accounts')
                                    ->label('Accounts')
                                    ->helperText('Cash, Bank, Mobile Money')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.income')
                                    ->label('Income')
                                    ->helperText('Record income transactions')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.expense')
                                    ->label('Expense')
                                    ->helperText('Record expense transactions')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.customers')
                                    ->label('Customers (Receivables)')
                                    ->helperText('Track customer balances')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.vendors')
                                    ->label('Vendors (Payables)')
                                    ->helperText('Track vendor balances')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.stock')
                                    ->label('Stock Management')
                                    ->helperText('Track inventory items')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.branches')
                                    ->label('Multi-Branch')
                                    ->helperText('Multiple business locations')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.profit_loss')
                                    ->label('Profit & Loss Reports')
                                    ->helperText('P&L analysis')
                                    ->default(true),
                                Forms\Components\Toggle::make('features.dashboard')
                                    ->label('Dashboard')
                                    ->helperText('Business dashboard')
                                    ->default(true),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Plan')
                                    ->helperText('Only one plan can be default')
                                    ->default(false),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plan Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Cycle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'monthly' => 'info',
                        'yearly' => 'success',
                        'lifetime' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('trial_enabled')
                    ->label('Trial')
                    ->boolean(),
                Tables\Columns\TextColumn::make('trial_days')
                    ->label('Trial Days')
                    ->suffix(' days')
                    ->visible(fn ($record) => $record?->trial_enabled),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Subscribers')
                    ->counts('subscriptions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('trial_enabled')
                    ->label('Trial Enabled'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('setDefault')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !$record->is_default && $record->is_active)
                    ->action(function ($record) {
                        Plan::where('is_default', true)->update(['is_default' => false]);
                        $record->update(['is_default' => true]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        if ($record->subscriptions()->exists()) {
                            throw new \Exception('Cannot delete plan with active subscriptions.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
