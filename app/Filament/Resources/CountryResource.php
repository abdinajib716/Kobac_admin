<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    
    protected static ?string $navigationGroup = 'Location Management';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Countries';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Country Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Country Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->label('ISO Code (Alpha-3)')
                                    ->required()
                                    ->maxLength(3)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('e.g., SOM, KEN, USA'),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('code_alpha2')
                                    ->label('ISO Code (Alpha-2)')
                                    ->maxLength(2)
                                    ->helperText('e.g., SO, KE, US'),
                                Forms\Components\TextInput::make('phone_code')
                                    ->label('Phone Code')
                                    ->maxLength(10)
                                    ->helperText('e.g., +252'),
                                Forms\Components\TextInput::make('currency')
                                    ->label('Currency Code')
                                    ->maxLength(3)
                                    ->helperText('e.g., SOS, USD'),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('flag')
                                    ->label('Flag Emoji')
                                    ->maxLength(10)
                                    ->helperText('e.g., 🇸🇴'),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flag')
                    ->label('')
                    ->width('40px'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Country')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('phone_code')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency'),
                Tables\Columns\TextColumn::make('regions_count')
                    ->label('Regions')
                    ->counts('regions')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
