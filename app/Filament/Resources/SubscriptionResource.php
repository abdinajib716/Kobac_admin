<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationGroup = 'Subscription Management';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('User')
                            ->disabled(),
                        Forms\Components\TextInput::make('plan.name')
                            ->label('Plan')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Starts')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Ends')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'trial' => 'warning',
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        'pending_payment' => 'info',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Subscription Ends')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Days Left')
                    ->getStateUsing(function ($record) {
                        if ($record->status === 'trial' && $record->trial_ends_at) {
                            return max(0, now()->diffInDays($record->trial_ends_at, false));
                        }
                        if ($record->ends_at) {
                            return max(0, now()->diffInDays($record->ends_at, false));
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(fn ($state) => is_numeric($state) && $state <= 3 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'pending_payment' => 'Pending Payment',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),
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
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
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
