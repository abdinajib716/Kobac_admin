<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MobileUserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MobileUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    
    protected static ?string $navigationGroup = 'Subscription Management';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Mobile Users';
    
    protected static ?string $modelLabel = 'Mobile User';
    
    protected static ?string $pluralModelLabel = 'Mobile Users';
    
    protected static ?string $slug = 'mobile-users';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->mobileUsers();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_type')
                            ->label('User Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('created_at')
                            ->label('Signup Date')
                            ->disabled(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Deactivating will block user access'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF';
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'success',
                        'business' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('subscription.status')
                    ->label('Subscription')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'trial' => 'warning',
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (?string $state, $record): string => 
                        $record->isIndividual() ? 'FREE' : ($state ? ucfirst($state) : 'N/A')
                    ),
                Tables\Columns\TextColumn::make('subscription.days_remaining')
                    ->label('Days Left')
                    ->formatStateUsing(fn (?int $state, $record): string => 
                        $record->isIndividual() ? '∞' : ($state !== null ? $state . ' days' : '-')
                    )
                    ->visible(fn ($record) => $record?->isBusiness()),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            $record->activate();
                        } else {
                            $record->deactivate(auth()->id());
                        }
                    }),
                Tables\Columns\TextColumn::make('district.name')
                    ->label('Location')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->district?->name 
                            ? $record->district->name . ', ' . ($record->region?->name ?? '')
                            : ($record->region?->name ?? ($record->country?->name ?? '-'))
                    )
                    ->icon('heroicon-o-map-pin')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Signup Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('User Type')
                    ->options([
                        'individual' => 'Individual',
                        'business' => 'Business',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->label('Subscription Status')
                    ->relationship('subscription', 'status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_active)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->activate()),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->deactivate(auth()->id())),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->activate()),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($r) => $r->deactivate(auth()->id()))),
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListMobileUsers::route('/'),
            'view' => Pages\ViewMobileUser::route('/{record}'),
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
        return true;
    }
}
