<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            // Only load mail config from database if tables exist
            if (Schema::hasTable('settings')) {
                $this->configureMail();
            }
        } catch (\Exception $e) {
            // Silently fail during migrations
        }
    }

    /**
     * Configure mail settings from database
     */
    protected function configureMail(): void
    {
        $mailConfig = [
            'default' => Setting::get('mail_mailer', config('mail.default')),
            'mailers' => [
                'smtp' => [
                    'transport' => 'smtp',
                    'host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
                    'port' => Setting::get('mail_port', config('mail.mailers.smtp.port')),
                    'encryption' => Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
                    'username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
                    'password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
                    'timeout' => null,
                ],
            ],
            'from' => [
                'address' => Setting::get('mail_from_address', config('mail.from.address')),
                'name' => Setting::get('mail_from_name', config('mail.from.name')),
            ],
        ];

        Config::set('mail', array_merge(config('mail'), $mailConfig));
    }
}
