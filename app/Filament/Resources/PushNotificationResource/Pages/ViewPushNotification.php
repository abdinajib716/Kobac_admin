<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPushNotification extends ViewRecord
{
    protected static string $resource = PushNotificationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Notification Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title'),
                        Infolists\Components\TextEntry::make('body')
                            ->label('Body')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('image_url')
                            ->label('Image URL')
                            ->url(fn ($state) => $state)
                            ->visible(fn ($record) => !empty($record->image_url)),
                    ])->columns(2),
                Infolists\Components\Section::make('Delivery Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('audience')
                            ->label('Audience')
                            ->formatStateUsing(fn ($record) => $record->audience_label)
                            ->badge(),
                        Infolists\Components\TextEntry::make('targetUser.name')
                            ->label('Target User')
                            ->visible(fn ($record) => $record->audience === 'specific'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'sent' => 'success',
                                'partial' => 'warning',
                                'failed' => 'danger',
                                'sending' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('total_recipients')
                            ->label('Total Recipients'),
                        Infolists\Components\TextEntry::make('success_count')
                            ->label('Delivered')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('failure_count')
                            ->label('Failed')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('delivery_rate')
                            ->label('Delivery Rate')
                            ->formatStateUsing(fn ($record) => $record->delivery_rate . '%'),
                    ])->columns(3),
                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('sender.name')
                            ->label('Sent By'),
                        Infolists\Components\TextEntry::make('sent_at')
                            ->label('Sent At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Error Message')
                            ->visible(fn ($record) => !empty($record->error_message))
                            ->color('danger')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('data')
                            ->label('Custom Data')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'None')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }
}
