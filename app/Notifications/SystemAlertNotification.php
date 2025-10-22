<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'warning'
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->message);

        return match ($this->type) {
            'success' => $notification->success()->icon('heroicon-o-check-circle'),
            'warning' => $notification->warning()->icon('heroicon-o-exclamation-triangle'),
            'danger' => $notification->danger()->icon('heroicon-o-x-circle'),
            'info' => $notification->info()->icon('heroicon-o-information-circle'),
            default => $notification->warning()->icon('heroicon-o-bell'),
        }->getDatabaseMessage();
    }
}
