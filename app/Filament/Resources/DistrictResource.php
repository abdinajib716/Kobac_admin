<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Models\Country;
use App\Models\District;
use App\Models\Region;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationGroup = 'Location Management';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Districts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('District Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('country_id')
                                    ->label('Country')
                                    ->options(Country::active()->ordered()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('region_id', null))
                                    ->dehydrated(false),
                                Forms\Components\Select::make('region_id')
                                    ->label('Region')
                                    ->options(function (Get $get) {
                                        $countryId = $get('country_id');
                                        if (!$countryId) {
                                            return [];
                                        }
                                        return Region::where('country_id', $countryId)
                                            ->active()
                                            ->ordered()
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('District Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->label('District Code')
                                    ->maxLength(10)
                                    ->helperText('Optional short code'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
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
                Tables\Columns\TextColumn::make('region.country.flag')
                    ->label('')
                    ->width('40px'),
                Tables\Columns\TextColumn::make('region.country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('District')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->label('Country')
                    ->options(Country::active()->ordered()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('region', function ($q) use ($data) {
                                $q->where('country_id', $data['value']);
                            });
                        }
                    }),
                Tables\Filters\SelectFilter::make('region')
                    ->relationship('region', 'name')
                    ->preload()
                    ->searchable(),
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
            ->defaultSort('name');
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
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
