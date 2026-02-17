<?php

namespace App\Filament\Widgets;

use App\Models\PaymentTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Services\OfflinePaymentService;
use Filament\Notifications\Notification;
use Filament\Forms;

class PendingOfflinePaymentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending Offline Payments';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return PaymentTransaction::where('payment_type', 'offline')
            ->where('status', 'pending_approval')
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PaymentTransaction::query()
                    ->where('payment_type', 'offline')
                    ->where('status', 'pending_approval')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payment')
                    ->modalDescription('Approve this payment and activate the subscription?')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes (Optional)')
                            ->placeholder('Add notes...'),
                    ])
                    ->action(function (PaymentTransaction $record, array $data) {
                        $service = app(OfflinePaymentService::class);
                        $result = $service->approvePayment($record, auth()->user(), $data['admin_notes'] ?? null);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Approved')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (PaymentTransaction $record, array $data) {
                        $service = app(OfflinePaymentService::class);
                        $result = $service->rejectPayment($record, $data['rejection_reason'], auth()->user());
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Rejected')
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PaymentTransaction $record): string => 
                        route('filament.admin.resources.payment-transactions.view', $record)
                    ),
            ])
            ->emptyStateHeading('No pending payments')
            ->emptyStateDescription('All offline payment requests have been processed.')
            ->paginated([5]);
    }
}
