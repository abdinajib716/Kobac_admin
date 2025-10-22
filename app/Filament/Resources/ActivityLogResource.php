<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource\RelationManagers;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Activity Log';
    
    protected static ?string $navigationGroup = 'Access Control';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Activity Details')
                    ->schema([
                        Forms\Components\TextInput::make('log_name')
                            ->label('Type')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('description')
                            ->label('Activity')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('subject_type')
                            ->label('Subject Type')
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'N/A')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('causer.name')
                            ->label('Performed By')
                            ->default('System')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Date & Time')
                            ->disabled(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Changes')
                    ->schema([
                        Forms\Components\KeyValue::make('properties.attributes')
                            ->label('New Values')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->properties && isset($record->properties['attributes'])),
                        
                        Forms\Components\KeyValue::make('properties.old')
                            ->label('Old Values')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->properties && isset($record->properties['old'])),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => $record && $record->properties),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Activity')
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('System'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Type')
                    ->options([
                        'default' => 'Default',
                        'user' => 'User',
                        'role' => 'Role',
                        'permission' => 'Permission',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
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
}
