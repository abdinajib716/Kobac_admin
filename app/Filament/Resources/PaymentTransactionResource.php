<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTransactionResource\Pages;
use App\Filament\Resources\PaymentTransactionResource\RelationManagers;
use App\Models\PaymentTransaction;
use App\Services\OfflinePaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Payments';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Transactions';
    
    protected static ?string $modelLabel = 'Payment Transaction';
    
    protected static ?string $pluralModelLabel = 'Payment Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\TextInput::make('reference_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('invoice_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('waafi_transaction_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_method')
                    ->required()
                    ->maxLength(255)
                    ->default('WALLET_ACCOUNT'),
                Forms\Components\TextInput::make('wallet_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('customer_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('USD'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('status_code')
                    ->maxLength(255),
                Forms\Components\Textarea::make('status_message')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('request_payload'),
                Forms\Components\TextInput::make('response_payload'),
                Forms\Components\Textarea::make('error_message')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('channel')
                    ->required()
                    ->maxLength(255)
                    ->default('WEB'),
                Forms\Components\TextInput::make('environment')
                    ->required()
                    ->maxLength(255)
                    ->default('LIVE'),
                Forms\Components\TextInput::make('ip_address')
                    ->maxLength(45),
                Forms\Components\TextInput::make('user_agent')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('initiated_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\DateTimePicker::make('failed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet_type')
                    ->label('Wallet')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EVC Plus' => 'success',
                        'Zaad Service' => 'info',
                        'Jeeb' => 'warning',
                        'Sahal' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'online' => 'info',
                        'offline' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'online'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success', 'approved' => 'success',
                        'processing' => 'warning',
                        'pending' => 'info',
                        'pending_approval' => 'warning',
                        'failed', 'rejected' => 'danger',
                        'cancelled' => 'gray',
                        'refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('waafi_transaction_id')
                    ->label('WaafiPay ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('environment')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'LIVE' ? 'success' : 'warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'pending_approval' => 'Pending Approval',
                        'processing' => 'Processing',
                        'success' => 'Success',
                        'approved' => 'Approved',
                        'failed' => 'Failed',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_type')
                    ->label('Payment Type')
                    ->options([
                        'online' => 'Online (WaafiPay)',
                        'offline' => 'Offline (Manual)',
                    ]),
                Tables\Filters\Filter::make('pending_approval')
                    ->label('Needs Approval')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending_approval'))
                    ->toggle(),
                Tables\Filters\SelectFilter::make('wallet_type')
                    ->label('Wallet Provider')
                    ->options([
                        'EVC Plus' => 'EVC Plus',
                        'Zaad Service' => 'Zaad Service',
                        'Jeeb' => 'Jeeb',
                        'Sahal' => 'Sahal',
                    ]),
                Tables\Filters\SelectFilter::make('environment')
                    ->options([
                        'LIVE' => 'Live',
                        'TEST' => 'Test',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Offline Payment')
                    ->modalDescription('Are you sure you want to approve this payment? The user\'s subscription will be activated.')
                    ->modalSubmitActionLabel('Yes, Approve')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes (Optional)')
                            ->placeholder('Add any notes about this approval...'),
                    ])
                    ->action(function (PaymentTransaction $record, array $data) {
                        $service = app(OfflinePaymentService::class);
                        $result = $service->approvePayment($record, auth()->user(), $data['admin_notes'] ?? null);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Approved')
                                ->body('Subscription has been activated for ' . $record->user->name)
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Approval Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (PaymentTransaction $record): bool => 
                        $record->payment_type === 'offline' && $record->status === 'pending_approval'
                    ),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Offline Payment')
                    ->modalDescription('Are you sure you want to reject this payment?')
                    ->modalSubmitActionLabel('Yes, Reject')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Explain why this payment is being rejected...'),
                    ])
                    ->action(function (PaymentTransaction $record, array $data) {
                        $service = app(OfflinePaymentService::class);
                        $result = $service->rejectPayment($record, $data['rejection_reason'], auth()->user());
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Rejected')
                                ->body('The payment has been rejected.')
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Rejection Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (PaymentTransaction $record): bool => 
                        $record->payment_type === 'offline' && $record->status === 'pending_approval'
                    ),
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
            'index' => Pages\ListPaymentTransactions::route('/'),
            'view' => Pages\ViewPaymentTransaction::route('/{record}'),
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
