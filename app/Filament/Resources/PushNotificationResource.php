<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushNotificationResource\Pages;
use App\Models\PushNotification;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PushNotificationResource extends Resource
{
    protected static ?string $model = PushNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Push Notifications';

    protected static ?string $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Push Notification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Compose Notification')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Notification Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. New Feature Available!')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('body')
                            ->label('Notification Body')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Enter your notification message...')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('image_url')
                            ->label('Image URL (Optional)')
                            ->url()
                            ->placeholder('https://example.com/image.png')
                            ->helperText('Optional image to display with the notification')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Audience')
                    ->schema([
                        Forms\Components\Select::make('audience')
                            ->label('Send To')
                            ->options([
                                'all' => 'All Mobile Users',
                                'individual' => 'Individual Users Only',
                                'business' => 'Business Users Only',
                                'specific' => 'Specific User',
                            ])
                            ->required()
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('target_user_id', null)),
                        Forms\Components\Select::make('target_user_id')
                            ->label('Select User')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return User::active()
                                    ->mobileUsers()
                                    ->orderBy('name')
                                    ->limit(200)
                                    ->pluck('name', 'id');
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return User::active()
                                    ->mobileUsers()
                                    ->where(function ($q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                          ->orWhere('email', 'like', "%{$search}%")
                                          ->orWhere('phone', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->pluck('name', 'id');
                            })
                            ->required(fn (Forms\Get $get): bool => $get('audience') === 'specific')
                            ->visible(fn (Forms\Get $get): bool => $get('audience') === 'specific')
                            ->helperText('Search by name, email, or phone number'),
                        Forms\Components\KeyValue::make('data')
                            ->label('Custom Data Payload (Optional)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Additional data to send with the notification (for app navigation, etc.)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (PushNotification $record) => $record->title),
                Tables\Columns\TextColumn::make('body')
                    ->label('Message')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Audience')
                    ->badge()
                    ->formatStateUsing(fn (PushNotification $record) => $record->audience_label)
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'primary',
                        'individual' => 'success',
                        'business' => 'info',
                        'specific' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('success_count')
                    ->label('Delivered')
                    ->numeric()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('failure_count')
                    ->label('Failed')
                    ->numeric()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        'sending' => 'info',
                        'pending' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sent By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'partial' => 'Partial',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('audience')
                    ->options([
                        'all' => 'All Users',
                        'individual' => 'Individual',
                        'business' => 'Business',
                        'specific' => 'Specific User',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotifications::route('/'),
            'create' => Pages\CreatePushNotification::route('/create'),
            'view' => Pages\ViewPushNotification::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = PushNotification::where('status', 'sending')->count();
        return $pending > 0 ? (string) $pending : null;
    }
}
