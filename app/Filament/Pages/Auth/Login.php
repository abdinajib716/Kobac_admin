<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Filament\Actions\Action;
use Filament\Forms\Form;

class Login extends BaseLogin
{
    /**
     * Get the view for the login page
     */
    public function getView(): string
    {
        return 'filament.pages.auth.login';
    }
    
    /**
     * Get the heading for the login page
     */
    public function getHeading(): string | Htmlable
    {
        return 'Sign In';
    }
    
    /**
     * Get the subheading for the login page
     */
    public function getSubHeading(): string | Htmlable | null
    {
        return 'Enter your email and password to sign in!';
    }
    
    /**
     * Disable default password reset redirect (we use modal instead)
     */
    protected function hasPasswordResetAction(): bool
    {
        return false;
    }
    
    /**
     * Add custom forgot password modal action
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
            $this->getForgotPasswordAction(),
        ];
    }
    
    /**
     * Forgot password modal action
     */
    protected function getForgotPasswordAction(): Action
    {
        return Action::make('forgotPassword')
            ->label('Forgot password?')
            ->link()
            ->color('gray')
            ->modalHeading('Reset Password')
            ->modalDescription('Enter your email address and we will send you a password reset link.')
            ->modalSubmitActionLabel('Send Reset Link')
            ->modalWidth('md')
            ->form([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->autofocus()
                    ->placeholder('Enter your email'),
            ])
            ->action(function (array $data) {
                $status = Password::sendResetLink(['email' => $data['email']]);
                
                if ($status === Password::RESET_LINK_SENT) {
                    Notification::make()
                        ->title('Password reset link sent!')
                        ->success()
                        ->body('Check your email for the password reset link.')
                        ->send();
                } else {
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body('We could not find a user with that email address.')
                        ->send();
                }
            });
    }
}
