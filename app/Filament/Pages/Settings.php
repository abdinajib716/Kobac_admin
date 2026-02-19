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
            'waafipay_enabled' => Setting::get('waafipay_enabled', false),
            'waafipay_environment' => Setting::get('waafipay_environment', 'LIVE'),
            'waafipay_merchant_uid' => Setting::get('waafipay_merchant_uid'),
            'waafipay_api_user_id' => Setting::get('waafipay_api_user_id'),
            'waafipay_api_key' => Setting::get('waafipay_api_key'),
            'waafipay_merchant_no' => Setting::get('waafipay_merchant_no'),
            'waafipay_api_url' => Setting::get('waafipay_api_url', 'https://api.waafipay.net/asm'),
            // Firebase
            'firebase_enabled' => (bool) Setting::get('firebase_enabled', false),
            'firebase_project_id' => Setting::get('firebase_project_id'),
            'firebase_client_email' => Setting::get('firebase_client_email'),
            'firebase_private_key' => $this->getDecryptedFirebaseKey(),
            'firebase_sender_id' => Setting::get('firebase_sender_id'),
            'firebase_server_key' => Setting::get('firebase_server_key'),
            'firebase_default_topic' => Setting::get('firebase_default_topic', 'kobac_all'),
            // WhatsApp Support
            'whatsapp_enabled' => (bool) Setting::get('whatsapp_enabled', false),
            'whatsapp_phone_number' => Setting::get('whatsapp_phone_number'),
            'whatsapp_agent_name' => Setting::get('whatsapp_agent_name', 'Support'),
            'whatsapp_agent_title' => Setting::get('whatsapp_agent_title', 'Typically replies instantly'),
            'whatsapp_greeting_message' => Setting::get('whatsapp_greeting_message'),
            'whatsapp_default_message' => Setting::get('whatsapp_default_message'),
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
                        Tabs\Tab::make('Payment Gateway')
                            ->schema([
                                Forms\Components\Section::make('WaafiPay Configuration')
                                    ->schema([
                                        Forms\Components\Toggle::make('waafipay_enabled')
                                            ->label('Enable WaafiPay')
                                            ->inline(false)
                                            ->helperText('Enable WaafiPay mobile payment gateway for Somalia'),
                                        Forms\Components\Select::make('waafipay_environment')
                                            ->label('Environment')
                                            ->options([
                                                'LIVE' => 'Live (Production)',
                                                'TEST' => 'Test (Sandbox)',
                                            ])
                                            ->required()
                                            ->helperText('Select LIVE for production, TEST for development'),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('waafipay_merchant_uid')
                                                    ->label('Merchant UID')
                                                    ->helperText('Example: M1234567')
                                                    ->placeholder('M1234567'),
                                                Forms\Components\TextInput::make('waafipay_api_user_id')
                                                    ->label('API User ID')
                                                    ->helperText('Example: 1234567')
                                                    ->placeholder('1234567'),
                                            ]),
                                        Forms\Components\TextInput::make('waafipay_api_key')
                                            ->label('API Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Example: API-xxxxxxxxxxxxx')
                                            ->placeholder('API-xxxxxxxxxxxxx'),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('waafipay_merchant_no')
                                                    ->label('Merchant Number')
                                                    ->helperText('Example: 123456789')
                                                    ->placeholder('123456789'),
                                                Forms\Components\TextInput::make('waafipay_api_url')
                                                    ->label('API URL')
                                                    ->default('https://api.waafipay.net/asm')
                                                    ->helperText('Default: https://api.waafipay.net/asm'),
                                            ]),
                                        Forms\Components\Placeholder::make('supported_providers')
                                            ->label('Supported Mobile Wallets')
                                            ->content('EVC Plus (Hormuud) • Zaad Service (Telesom) • Jeeb (Golis) • Sahal (Somtel)'),
                                    ])
                                    ->description('Configure WaafiPay mobile payment gateway for accepting payments via EVC Plus, Zaad, Jeeb, and Sahal'),
                                Forms\Components\Section::make('Test Payment')
                                    ->schema([
                                        Forms\Components\Placeholder::make('test_info')
                                            ->label('Test Payment')
                                            ->content('Send a real test payment to verify your WaafiPay integration is working correctly.'),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('test_phone')
                                                    ->label('Phone Number')
                                                    ->prefix('252')
                                                    ->placeholder('619821172')
                                                    ->helperText('Enter 9-digit phone number (without country code)')
                                                    ->mask('999999999')
                                                    ->maxLength(9),
                                                Forms\Components\TextInput::make('test_amount')
                                                    ->label('Amount (USD)')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->placeholder('0.50')
                                                    ->helperText('Minimum: $0.01')
                                                    ->step(0.01),
                                            ]),
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_payment')
                                                ->label('Send Test Payment')
                                                ->icon('heroicon-o-banknotes')
                                                ->color('success')
                                                ->requiresConfirmation()
                                                ->modalHeading('Send Test Payment')
                                                ->modalDescription('This will send a real payment request to the phone number. Make sure you have approval to charge this number.')
                                                ->modalSubmitActionLabel('Send Payment')
                                                ->action(function (array $data) {
                                                    $this->sendTestPayment($data);
                                                }),
                                            Forms\Components\Actions\Action::make('test_connection')
                                                ->label('Test API Connection')
                                                ->icon('heroicon-o-signal')
                                                ->color('info')
                                                ->action(function () {
                                                    $this->testWaafiPayConnection();
                                                }),
                                        ]),
                                    ])
                                    ->description('Configure WaafiPay mobile payment gateway for accepting payments via EVC Plus, Zaad, Jeeb, and Sahal'),
                                Forms\Components\Section::make('Offline Payment Configuration')
                                    ->schema([
                                        Forms\Components\Toggle::make('offline_payment_enabled')
                                            ->label('Enable Offline Payment')
                                            ->inline(false)
                                            ->helperText('Allow users to request subscription via offline/manual payment (requires admin approval)')
                                            ->live(),
                                        Forms\Components\Textarea::make('offline_payment_instructions')
                                            ->label('Payment Instructions')
                                            ->rows(4)
                                            ->placeholder("Please transfer the payment to:\nBank: Premier Bank\nAccount: 1234567890\nName: Your Company Name\n\nAfter payment, your subscription will be activated within 24 hours.")
                                            ->helperText('Instructions shown to users when they choose offline payment')
                                            ->visible(fn (Forms\Get $get): bool => $get('offline_payment_enabled')),
                                        Forms\Components\Placeholder::make('offline_info')
                                            ->label('How Offline Payment Works')
                                            ->content('1. User selects a plan and chooses offline payment
2. System creates a pending payment request
3. Admin reviews and approves/rejects in the Payments section
4. On approval, the user\'s subscription is activated')
                                            ->visible(fn (Forms\Get $get): bool => $get('offline_payment_enabled')),
                                    ])
                                    ->description('Configure offline/manual payment for users who cannot use mobile wallets'),
                            ]),
                        Tabs\Tab::make('Firebase (Push Notifications)')
                            ->schema([
                                Forms\Components\Section::make('Firebase Cloud Messaging (FCM)')
                                    ->schema([
                                        Forms\Components\Toggle::make('firebase_enabled')
                                            ->label('Enable Push Notifications')
                                            ->inline(false)
                                            ->helperText('Enable Firebase Cloud Messaging for push notifications to mobile devices')
                                            ->live(),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('firebase_project_id')
                                                    ->label('Project ID')
                                                    ->placeholder('my-project-12345')
                                                    ->helperText('Firebase project ID from console.firebase.google.com'),
                                                Forms\Components\TextInput::make('firebase_client_email')
                                                    ->label('Service Account Email')
                                                    ->placeholder('firebase-adminsdk-xxxxx@project.iam.gserviceaccount.com')
                                                    ->helperText('Client email from your service account JSON file'),
                                            ])
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('firebase_enabled')),
                                        Forms\Components\Textarea::make('firebase_private_key')
                                            ->label('Private Key')
                                            ->rows(4)
                                            ->placeholder('-----BEGIN PRIVATE KEY-----\nMIIEv...')
                                            ->helperText('Private key from your service account JSON file. This will be encrypted at rest.')
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('firebase_enabled')),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('firebase_sender_id')
                                                    ->label('Sender ID (Optional)')
                                                    ->placeholder('123456789012')
                                                    ->helperText('FCM Sender ID from Project Settings → Cloud Messaging. Optional — used for reference only.'),
                                                Forms\Components\TextInput::make('firebase_server_key')
                                                    ->label('Server Key (Legacy — Deprecated)')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Not required — leave empty')
                                                    ->helperText('⚠️ DEPRECATED: Legacy server keys are no longer available from Google Cloud. This system uses the modern FCM HTTP v1 API with service account credentials instead. You can safely leave this empty.'),
                                            ])
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('firebase_enabled')),
                                        Forms\Components\TextInput::make('firebase_default_topic')
                                            ->label('Default Topic')
                                            ->default('kobac_all')
                                            ->helperText('Default FCM topic for broadcast notifications')
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('firebase_enabled')),
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_firebase')
                                                ->label('Test Firebase Connection')
                                                ->icon('heroicon-o-signal')
                                                ->color('info')
                                                ->action(function () {
                                                    $this->testFirebaseConnection();
                                                }),
                                        ])
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('firebase_enabled')),
                                    ])
                                    ->description('Configure Firebase Cloud Messaging for sending push notifications to Android and iOS devices'),
                            ]),
                        Tabs\Tab::make('WhatsApp Support')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Widget Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_enabled')
                                            ->label('Enable WhatsApp Widget')
                                            ->inline(false)
                                            ->helperText('Show the WhatsApp support button on your website')
                                            ->live(),
                                    ])
                                    ->description('Control the WhatsApp support widget visibility'),
                                Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('whatsapp_phone_number')
                                                    ->label('WhatsApp Phone Number')
                                                    ->required()
                                                    ->placeholder('252612345678')
                                                    ->helperText('Include country code without + or spaces (e.g., 252612345678)'),
                                                Forms\Components\TextInput::make('whatsapp_agent_name')
                                                    ->label('Agent Name')
                                                    ->required()
                                                    ->placeholder('Support Team')
                                                    ->maxLength(50),
                                            ]),
                                        Forms\Components\TextInput::make('whatsapp_agent_title')
                                            ->label('Agent Title')
                                            ->required()
                                            ->placeholder('Typically replies instantly')
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('whatsapp_greeting_message')
                                            ->label('Greeting Message')
                                            ->rows(3)
                                            ->placeholder("Assalamu Alaikum! 👋\nHow can we help you today?")
                                            ->helperText('Message shown in the WhatsApp popup widget'),
                                        Forms\Components\TextInput::make('whatsapp_default_message')
                                            ->label('Default Message')
                                            ->placeholder('Hello, I need help with...')
                                            ->helperText('Pre-filled message when user clicks Start Chat'),
                                    ])
                                    ->visible(fn (Forms\Get $get): bool => (bool) $get('whatsapp_enabled')),
                                Forms\Components\Section::make('Preview')
                                    ->schema([
                                        Forms\Components\Placeholder::make('whatsapp_preview')
                                            ->label('How your WhatsApp widget will look')
                                            ->content(fn (Forms\Get $get) => view('filament.components.whatsapp-preview', [
                                                'agentName' => $get('whatsapp_agent_name') ?: 'Support',
                                                'agentTitle' => $get('whatsapp_agent_title') ?: 'Typically replies instantly',
                                                'greetingMessage' => $get('whatsapp_greeting_message') ?: 'Hello! How can we help you?',
                                            ])),
                                    ])
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => (bool) $get('whatsapp_enabled')),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Encrypt Firebase private key before saving (avoid double-encryption)
        if (!empty($data['firebase_private_key'])) {
            $currentEncrypted = Setting::get('firebase_private_key');
            $currentDecrypted = null;
            if ($currentEncrypted) {
                try {
                    $currentDecrypted = decrypt($currentEncrypted);
                } catch (\Exception $e) {
                    $currentDecrypted = $currentEncrypted;
                }
            }
            // Only re-encrypt if the key actually changed
            if ($data['firebase_private_key'] !== $currentDecrypted) {
                $data['firebase_private_key'] = encrypt($data['firebase_private_key']);
            } else {
                // Key unchanged — keep the existing encrypted value
                $data['firebase_private_key'] = $currentEncrypted;
            }
        }
        
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
            ->body('All configuration settings have been updated')
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
    
    public function testWaafiPayConnection(): void
    {
        try {
            // Get current form data
            $data = $this->form->getState();
            
            // Check if WaafiPay is enabled
            if (!$data['waafipay_enabled']) {
                Notification::make()
                    ->title('WaafiPay Disabled')
                    ->warning()
                    ->body('Please enable WaafiPay before testing the connection.')
                    ->send();
                return;
            }
            
            // Check if all required fields are filled
            $requiredFields = [
                'waafipay_merchant_uid' => 'Merchant UID',
                'waafipay_api_user_id' => 'API User ID',
                'waafipay_api_key' => 'API Key',
                'waafipay_merchant_no' => 'Merchant Number',
            ];
            
            $missingFields = [];
            foreach ($requiredFields as $field => $label) {
                if (empty($data[$field])) {
                    $missingFields[] = $label;
                }
            }
            
            if (!empty($missingFields)) {
                Notification::make()
                    ->title('Missing Configuration')
                    ->warning()
                    ->body('Please fill in: ' . implode(', ', $missingFields))
                    ->persistent()
                    ->send();
                return;
            }
            
            // Temporarily save settings to test
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'waafipay_')) {
                    Setting::set($key, $value);
                }
            }
            
            // Create WaafiPay service instance
            $waafiPay = new \App\Services\WaafiPayService();
            
            // Check if configured
            if (!$waafiPay->isConfigured()) {
                Notification::make()
                    ->title('Configuration Error')
                    ->danger()
                    ->body('WaafiPay configuration is incomplete.')
                    ->persistent()
                    ->send();
                return;
            }
            
            // Test API connection with a dummy check
            $testPayload = [
                'schemaVersion' => '1.0',
                'requestId' => 'TEST-' . time(),
                'timestamp' => time(),
                'channelName' => 'WEB',
                'serviceName' => 'API_PURCHASE',
                'serviceParams' => [
                    'merchantUid' => $data['waafipay_merchant_uid'],
                    'apiUserId' => $data['waafipay_api_user_id'],
                    'apiKey' => $data['waafipay_api_key'],
                ],
            ];
            
            $response = \Http::timeout(10)->post($data['waafipay_api_url'], $testPayload);
            
            if ($response->successful() || $response->status() === 400) {
                // 400 is expected for incomplete test payload, but means API is reachable
                Notification::make()
                    ->title('Connection Successful!')
                    ->success()
                    ->body('WaafiPay API is reachable. Environment: ' . $data['waafipay_environment'])
                    ->duration(5000)
                    ->send();
                    
                \Log::info('WaafiPay connection test successful', [
                    'environment' => $data['waafipay_environment'],
                    'merchant_uid' => $data['waafipay_merchant_uid'],
                ]);
            } else {
                throw new \Exception('API returned status: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Connection Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
                
            \Log::error('WaafiPay connection test failed: ' . $e->getMessage());
        }
    }
    
    public function sendTestPayment(array $formData): void
    {
        try {
            $data = $this->form->getState();
            
            // Validate phone number
            if (empty($data['test_phone'])) {
                Notification::make()
                    ->title('Validation Error')
                    ->warning()
                    ->body('Please enter a phone number')
                    ->send();
                return;
            }
            
            // Validate amount
            if (empty($data['test_amount']) || $data['test_amount'] < 0.01) {
                Notification::make()
                    ->title('Validation Error')
                    ->warning()
                    ->body('Please enter an amount (minimum $0.01)')
                    ->send();
                return;
            }
            
            // Check if WaafiPay is enabled and configured
            if (!$data['waafipay_enabled']) {
                Notification::make()
                    ->title('WaafiPay Disabled')
                    ->warning()
                    ->body('Please enable WaafiPay before testing payments.')
                    ->send();
                return;
            }
            
            // Save current settings temporarily
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'waafipay_')) {
                    Setting::set($key, $value);
                }
            }
            
            // Create WaafiPay service instance
            $waafiPay = new \App\Services\WaafiPayService();
            
            if (!$waafiPay->isConfigured()) {
                Notification::make()
                    ->title('Configuration Error')
                    ->danger()
                    ->body('WaafiPay is not properly configured. Please fill all required fields.')
                    ->persistent()
                    ->send();
                return;
            }
            
            // Prepare payment parameters
            $paymentParams = [
                'phone_number' => $data['test_phone'],
                'amount' => $data['test_amount'],
                'customer_name' => 'Test Payment',
                'description' => 'WaafiPay Integration Test Payment - ' . now()->format('Y-m-d H:i:s'),
                'channel' => 'ADMIN_PANEL',
                'customer_id' => auth()->id(),
            ];
            
            // Call WaafiPay API
            $result = $waafiPay->purchase($paymentParams);
            
            if ($result['success']) {
                $message = $result['status'] === 'success' 
                    ? '✅ Payment completed successfully!'
                    : '📱 Payment request sent! Check phone 252' . $data['test_phone'] . ' for approval.';
                
                Notification::make()
                    ->title('Test Payment Sent')
                    ->success()
                    ->body($message . ' Reference: ' . $result['reference_id'])
                    ->duration(10000)
                    ->send();
                    
                \Log::info('Test payment sent successfully', [
                    'reference_id' => $result['reference_id'],
                    'transaction_id' => $result['transaction_id'],
                    'status' => $result['status'],
                ]);
            } else {
                $errorDetails = $result['message'] ?? 'Payment request failed';
                if (isset($result['error_code'])) {
                    $errorDetails .= ' (Code: ' . $result['error_code'] . ')';
                }
                if (isset($result['response_code'])) {
                    $errorDetails .= ' [Response: ' . $result['response_code'] . ']';
                }
                
                Notification::make()
                    ->title('Payment Failed')
                    ->danger()
                    ->body($errorDetails . ' - Check logs for full details.')
                    ->persistent()
                    ->send();
                    
                \Log::error('Test payment failed', [
                    'error' => $result['message'] ?? 'Unknown error',
                    'error_code' => $result['error_code'] ?? null,
                    'response_code' => $result['response_code'] ?? null,
                    'phone' => '252' . $data['test_phone'],
                    'full_response' => $result,
                ]);
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Payment Error')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
                
            \Log::error('Test payment exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Get decrypted Firebase private key for form display
     */
    protected function getDecryptedFirebaseKey(): ?string
    {
        $encrypted = Setting::get('firebase_private_key');
        if (empty($encrypted)) {
            return null;
        }
        
        try {
            return decrypt($encrypted);
        } catch (\Exception $e) {
            return $encrypted;
        }
    }
    
    /**
     * Test Firebase connection
     */
    public function testFirebaseConnection(): void
    {
        try {
            $data = $this->form->getState();
            
            if (!$data['firebase_enabled']) {
                Notification::make()
                    ->title('Firebase Disabled')
                    ->warning()
                    ->body('Please enable Firebase before testing the connection.')
                    ->send();
                return;
            }
            
            $requiredFields = [
                'firebase_project_id' => 'Project ID',
                'firebase_client_email' => 'Client Email',
                'firebase_private_key' => 'Private Key',
            ];
            
            $missing = [];
            foreach ($requiredFields as $field => $label) {
                if (empty($data[$field])) {
                    $missing[] = $label;
                }
            }
            
            if (!empty($missing)) {
                Notification::make()
                    ->title('Missing Configuration')
                    ->warning()
                    ->body('Please fill in: ' . implode(', ', $missing))
                    ->persistent()
                    ->send();
                return;
            }
            
            // Temporarily save Firebase settings for the test
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'firebase_')) {
                    if ($key === 'firebase_private_key' && !empty($value)) {
                        // Avoid double-encryption
                        $currentEncrypted = Setting::get('firebase_private_key');
                        $currentDecrypted = null;
                        if ($currentEncrypted) {
                            try { $currentDecrypted = decrypt($currentEncrypted); } catch (\Exception $e) { $currentDecrypted = $currentEncrypted; }
                        }
                        Setting::set($key, ($value !== $currentDecrypted) ? encrypt($value) : $currentEncrypted);
                    } else {
                        Setting::set($key, $value);
                    }
                }
            }
            
            $firebase = app(\App\Services\FirebaseNotificationService::class);
            $result = $firebase->testConnection();
            
            if ($result['success']) {
                Notification::make()
                    ->title('Firebase Connected!')
                    ->success()
                    ->body($result['message'])
                    ->duration(5000)
                    ->send();
                    
                \Log::info('Firebase connection test successful', [
                    'project_id' => $data['firebase_project_id'],
                ]);
            } else {
                Notification::make()
                    ->title('Connection Failed')
                    ->danger()
                    ->body($result['message'])
                    ->persistent()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Firebase Test Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();
                
            \Log::error('Firebase connection test failed: ' . $e->getMessage());
        }
    }
}
