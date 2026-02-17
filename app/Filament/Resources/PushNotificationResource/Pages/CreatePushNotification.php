<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use App\Models\Setting;
use App\Services\FirebaseNotificationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null; // We handle notification in afterCreate
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sent_by'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Check if Firebase is enabled
        $firebase = app(FirebaseNotificationService::class);

        if (!$firebase->isEnabled()) {
            $record->markAsFailed('Firebase push notifications are disabled. Enable them in Settings → Firebase tab.');

            Notification::make()
                ->title('Firebase Disabled')
                ->body('Push notifications are disabled. Enable them in Settings → Firebase.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if (!$firebase->isConfigured()) {
            $record->markAsFailed('Firebase is not properly configured. Check Settings → Firebase tab.');

            Notification::make()
                ->title('Firebase Not Configured')
                ->body('Firebase credentials are missing or incomplete.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        // Rate limit: max 10 notifications per minute per admin
        $recentCount = \App\Models\PushNotification::where('sent_by', auth()->id())
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($recentCount > 10) {
            $record->markAsFailed('Rate limit exceeded. Maximum 10 notifications per minute.');

            Notification::make()
                ->title('Rate Limited')
                ->body('You can send a maximum of 10 notifications per minute.')
                ->warning()
                ->send();

            return;
        }

        // Send the notification
        try {
            $result = $firebase->sendNotification(
                title: $record->title,
                body: $record->body,
                audience: $record->audience,
                targetUserId: $record->target_user_id,
                data: $record->data ?? [],
                imageUrl: $record->image_url,
                sentBy: $record->sent_by
            );

            // Update the original record with results from the service
            $record->update([
                'total_recipients' => $result->total_recipients,
                'success_count' => $result->success_count,
                'failure_count' => $result->failure_count,
                'status' => $result->status,
                'error_message' => $result->error_message,
                'failed_tokens' => $result->failed_tokens,
                'sent_at' => $result->sent_at,
                'completed_at' => $result->completed_at,
            ]);

            // Delete the duplicate record created by the service
            if ($result->id !== $record->id) {
                $result->delete();
            }

            if ($record->status === 'sent') {
                Notification::make()
                    ->title('Notification Sent!')
                    ->body("Delivered to {$record->success_count} of {$record->total_recipients} devices.")
                    ->success()
                    ->duration(5000)
                    ->send();
            } elseif ($record->status === 'partial') {
                Notification::make()
                    ->title('Partially Delivered')
                    ->body("Delivered: {$record->success_count}, Failed: {$record->failure_count}")
                    ->warning()
                    ->duration(5000)
                    ->send();
            } else {
                Notification::make()
                    ->title('Notification Failed')
                    ->body($record->error_message ?? 'Unknown error')
                    ->danger()
                    ->persistent()
                    ->send();
            }
        } catch (\Exception $e) {
            $record->markAsFailed($e->getMessage());

            Notification::make()
                ->title('Send Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
