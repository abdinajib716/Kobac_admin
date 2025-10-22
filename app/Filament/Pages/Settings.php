<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Settings';
    
    protected static ?string $navigationGroup = 'Access Control';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $title = 'Settings';
    
    protected static string $view = 'filament.pages.settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::get('site_name', 'Dashboard Cajiib Creative'),
            'site_logo_full_lite' => Setting::get('site_logo_full_lite'),
            'site_logo_full_dark' => Setting::get('site_logo_full_dark'),
            'login_page_logo' => Setting::get('login_page_logo'),
            'site_icon' => Setting::get('site_icon'),
            'site_favicon' => Setting::get('site_favicon'),
            'theme_primary_color' => Setting::get('theme_primary_color', '#0a6679'),
            'theme_secondary_color' => Setting::get('theme_secondary_color', '#1f2937'),
            'default_mode' => Setting::get('default_mode', 'lite'),
            'lite_navbar_bg' => Setting::get('lite_navbar_bg', '#ffffff'),
            'lite_sidebar_bg' => Setting::get('lite_sidebar_bg', '#ffffff'),
            'lite_navbar_text' => Setting::get('lite_navbar_text', '#090909'),
            'lite_sidebar_text' => Setting::get('lite_sidebar_text', '#090909'),
            'dark_navbar_bg' => Setting::get('dark_navbar_bg', '#171f2e'),
            'dark_sidebar_bg' => Setting::get('dark_sidebar_bg', '#171f2e'),
            'dark_navbar_text' => Setting::get('dark_navbar_text', '#ffffff'),
            'dark_sidebar_text' => Setting::get('dark_sidebar_text', '#ffffff'),
            'mail_mailer' => Setting::get('mail_mailer', 'smtp'),
            'mail_host' => Setting::get('mail_host', 'smtp.gmail.com'),
            'mail_port' => Setting::get('mail_port', '587'),
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::get('mail_password'),
            'mail_encryption' => Setting::get('mail_encryption', 'tls'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name'),
            'stripe_enabled' => Setting::get('stripe_enabled', false),
            'stripe_public_key' => Setting::get('stripe_public_key'),
            'stripe_secret_key' => Setting::get('stripe_secret_key'),
            'paypal_enabled' => Setting::get('paypal_enabled', false),
            'paypal_client_id' => Setting::get('paypal_client_id'),
            'paypal_secret' => Setting::get('paypal_secret'),
            'paypal_mode' => Setting::get('paypal_mode', 'sandbox'),
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('General Settings')
                            ->schema([
                                Forms\Components\Section::make('General Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('site_name')
                                            ->label('Site Name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\FileUpload::make('site_logo_full_lite')
                                                    ->label('Site Logo Full (Lite Version)')
                                                    ->image()
                                                    ->directory('settings/logos')
                                                    ->visibility('public')
                                                    ->imagePreviewHeight('120')
                                                    ->maxSize(2048)
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1200')
                                                    ->imageResizeTargetHeight('400')
                                                    ->loadingIndicatorPosition('right')
                                                    ->panelLayout('compact')
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadProgressIndicatorPosition('right'),
                                                Forms\Components\FileUpload::make('site_icon')
                                                    ->label('Site Icon')
                                                    ->image()
                                                    ->directory('settings/icons')
                                                    ->visibility('public')
                                                    ->imagePreviewHeight('120')
                                                    ->maxSize(1024)
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('400')
                                                    ->imageResizeTargetHeight('400')
                                                    ->loadingIndicatorPosition('right')
                                                    ->panelLayout('compact')
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadProgressIndicatorPosition('right'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\FileUpload::make('site_logo_full_dark')
                                                    ->label('Site Logo Full (Dark Version)')
                                                    ->image()
                                                    ->directory('settings/logos')
                                                    ->visibility('public')
                                                    ->imagePreviewHeight('120')
                                                    ->maxSize(2048)
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('1200')
                                                    ->imageResizeTargetHeight('400')
                                                    ->loadingIndicatorPosition('right')
                                                    ->panelLayout('compact')
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadProgressIndicatorPosition('right'),
                                                Forms\Components\FileUpload::make('site_favicon')
                                                    ->label('Site Favicon')
                                                    ->image()
                                                    ->directory('settings/favicons')
                                                    ->visibility('public')
                                                    ->imagePreviewHeight('120')
                                                    ->maxSize(512)
                                                    ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                                                    ->imageResizeMode('contain')
                                                    ->imageResizeTargetWidth('64')
                                                    ->imageResizeTargetHeight('64')
                                                    ->loadingIndicatorPosition('right')
                                                    ->panelLayout('compact')
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadProgressIndicatorPosition('right'),
                                            ]),
                                        Forms\Components\FileUpload::make('login_page_logo')
                                            ->label('Login Page Logo')
                                            ->image()
                                            ->directory('settings/logos')
                                            ->visibility('public')
                                            ->imagePreviewHeight('120')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                                            ->imageResizeMode('contain')
                                            ->imageResizeTargetWidth('800')
                                            ->imageResizeTargetHeight('800')
                                            ->loadingIndicatorPosition('right')
                                            ->panelLayout('compact')
                                            ->removeUploadedFileButtonPosition('right')
                                            ->uploadProgressIndicatorPosition('right')
                                            ->helperText('Logo displayed on the right side of the login page'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Site Appearance')
                            ->schema([
                                Forms\Components\Section::make('Site Appearance')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\ColorPicker::make('theme_primary_color')
                                                    ->label('Theme Primary Color')
                                                    ->required(),
                                                Forms\Components\ColorPicker::make('theme_secondary_color')
                                                    ->label('Theme Secondary Color')
                                                    ->required(),
                                            ]),
                                        Forms\Components\Select::make('default_mode')
                                            ->label('Default Mode')
                                            ->options([
                                                'lite' => 'Lite',
                                                'dark' => 'Dark',
                                            ])
                                            ->required(),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Section::make('Lite Mode Colors')
                                                    ->schema([
                                                        Forms\Components\ColorPicker::make('lite_navbar_bg')
                                                            ->label('Navbar Background Color'),
                                                        Forms\Components\ColorPicker::make('lite_sidebar_bg')
                                                            ->label('Sidebar Background Color'),
                                                        Forms\Components\ColorPicker::make('lite_navbar_text')
                                                            ->label('Navbar Text Color'),
                                                        Forms\Components\ColorPicker::make('lite_sidebar_text')
                                                            ->label('Sidebar Text Color'),
                                                    ]),
                                                Forms\Components\Section::make('Dark Mode Colors')
                                                    ->schema([
                                                        Forms\Components\ColorPicker::make('dark_navbar_bg')
                                                            ->label('Navbar Background Color'),
                                                        Forms\Components\ColorPicker::make('dark_sidebar_bg')
                                                            ->label('Sidebar Background Color'),
                                                        Forms\Components\ColorPicker::make('dark_navbar_text')
                                                            ->label('Navbar Text Color'),
                                                        Forms\Components\ColorPicker::make('dark_sidebar_text')
                                                            ->label('Sidebar Text Color'),
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Email Configuration')
                            ->schema([
                                Forms\Components\Section::make('Email Configuration')
                                    ->schema([
                                        Forms\Components\Select::make('mail_mailer')
                                            ->label('Mail Driver')
                                            ->options([
                                                'smtp' => 'SMTP',
                                                'sendmail' => 'Sendmail',
                                                'mailgun' => 'Mailgun',
                                                'ses' => 'Amazon SES',
                                                'postmark' => 'Postmark',
                                            ])
                                            ->required(),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('mail_host')
                                                    ->label('Mail Host')
                                                    ->required(),
                                                Forms\Components\TextInput::make('mail_port')
                                                    ->label('Mail Port')
                                                    ->numeric()
                                                    ->required(),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('mail_username')
                                                    ->label('Mail Username')
                                                    ->email(),
                                                Forms\Components\TextInput::make('mail_password')
                                                    ->label('Mail Password')
                                                    ->password()
                                                    ->revealable(),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('mail_encryption')
                                                    ->label('Encryption')
                                                    ->options([
                                                        'tls' => 'TLS',
                                                        'ssl' => 'SSL',
                                                        'none' => 'None',
                                                    ]),
                                                Forms\Components\TextInput::make('mail_from_name')
                                                    ->label('From Name'),
                                            ]),
                                        Forms\Components\TextInput::make('mail_from_address')
                                            ->label('From Email Address')
                                            ->email(),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_email')
                                                ->label('Send Test Email')
                                                ->icon('heroicon-o-envelope')
                                                ->color('info')
                                                ->action(function () {
                                                    $this->sendTestEmail();
                                                }),
                                        ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Payment Methods')
                            ->schema([
                                Forms\Components\Section::make('Stripe')
                                    ->schema([
                                        Forms\Components\Toggle::make('stripe_enabled')
                                            ->label('Enable Stripe')
                                            ->inline(false),
                                        Forms\Components\TextInput::make('stripe_public_key')
                                            ->label('Stripe Public Key')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('stripe_secret_key')
                                            ->label('Stripe Secret Key')
                                            ->password()
                                            ->revealable(),
                                    ])
                                    ->collapsible(),
                                Forms\Components\Section::make('PayPal')
                                    ->schema([
                                        Forms\Components\Toggle::make('paypal_enabled')
                                            ->label('Enable PayPal')
                                            ->inline(false),
                                        Forms\Components\Select::make('paypal_mode')
                                            ->label('PayPal Mode')
                                            ->options([
                                                'sandbox' => 'Sandbox (Test)',
                                                'live' => 'Live (Production)',
                                            ]),
                                        Forms\Components\TextInput::make('paypal_client_id')
                                            ->label('PayPal Client ID')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('paypal_secret')
                                            ->label('PayPal Secret')
                                            ->password()
                                            ->revealable(),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Save all settings to database
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }
        
        // Also update .env file with email configuration
        $this->updateEnvFile([
            'MAIL_MAILER' => $data['mail_mailer'] ?? 'smtp',
            'MAIL_HOST' => $data['mail_host'] ?? 'smtp.gmail.com',
            'MAIL_PORT' => $data['mail_port'] ?? '587',
            'MAIL_USERNAME' => $data['mail_username'] ?? '',
            'MAIL_PASSWORD' => $data['mail_password'] ?? '',
            'MAIL_ENCRYPTION' => $data['mail_encryption'] ?? 'tls',
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? '',
            'MAIL_FROM_NAME' => '"' . ($data['mail_from_name'] ?? config('app.name')) . '"',
        ]);
        
        // Clear caches to apply changes
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        
        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->body('Email configuration updated in database and .env file')
            ->duration(2000)
            ->send();
        
        // Auto-refresh page after 2 seconds
        $this->dispatch('refresh-page');
    }
    
    /**
     * Update .env file with new values
     */
    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            return;
        }
        
        $envContent = file_get_contents($envPath);
        
        foreach ($data as $key => $value) {
            // Properly quote values that contain spaces or special characters
            if (str_contains($value, ' ') || str_contains($value, '#')) {
                // Remove existing quotes if any
                $value = trim($value, '"');
                // Wrap in quotes (no escaping, just wrap)
                $value = '"' . $value . '"';
            }
            
            // Check if key exists in .env
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Add new key at the end
                $envContent .= "\n{$key}={$value}";
            }
        }
        
        file_put_contents($envPath, $envContent);
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear All Cache')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Clear All Application Cache')
                ->modalDescription('This will clear all caches including configuration, routes, views, and compiled files. This is safe and will not delete any data.')
                ->modalSubmitActionLabel('Yes, Clear Cache')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->visible(fn () => auth()->user()->hasRole('Admin'))
                ->action(function () {
                    $this->clearAllCache();
                }),
        ];
    }
    
    public function clearAllCache(): void
    {
        try {
            // Clear application cache
            \Artisan::call('cache:clear');
            
            // Clear configuration cache
            \Artisan::call('config:clear');
            
            // Clear route cache
            \Artisan::call('route:clear');
            
            // Clear view cache
            \Artisan::call('view:clear');
            
            // Clear compiled files
            \Artisan::call('clear-compiled');
            
            // Clear event cache
            \Artisan::call('event:clear');
            
            // Optimize autoloader
            \Artisan::call('optimize:clear');
            
            Notification::make()
                ->title('Cache Cleared Successfully!')
                ->success()
                ->body('All caches have been cleared. Your application is now running with fresh cache.')
                ->duration(5000)
                ->send();
                
            // Log the action for security audit
            \Log::info('All application caches cleared by user: ' . auth()->user()->name);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Cache Clear Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
                
            \Log::error('Cache clear failed: ' . $e->getMessage());
        }
    }
    
    public function sendTestEmail(): void
    {
        try {
            // Get current form data
            $data = $this->form->getState();
            
            // Temporarily set mail config
            config([
                'mail.default' => $data['mail_mailer'],
                'mail.mailers.smtp.host' => $data['mail_host'],
                'mail.mailers.smtp.port' => $data['mail_port'],
                'mail.mailers.smtp.username' => $data['mail_username'],
                'mail.mailers.smtp.password' => $data['mail_password'],
                'mail.mailers.smtp.encryption' => $data['mail_encryption'],
                'mail.from.address' => $data['mail_from_address'],
                'mail.from.name' => $data['mail_from_name'],
            ]);
            
            // Send test email
            \Mail::raw('This is a test email from your dashboard. If you received this, your email configuration is working correctly!', function ($message) use ($data) {
                $message->to(auth()->user()->email)
                    ->subject('Test Email - ' . config('app.name'));
            });
            
            Notification::make()
                ->title('Test Email Sent!')
                ->success()
                ->body('Check your inbox at ' . auth()->user()->email)
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Email Test Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
