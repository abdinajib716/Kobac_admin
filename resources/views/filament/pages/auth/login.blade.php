<x-filament-panels::page.simple>
    <style>
        /* Override Filament Defaults */
        .fi-simple-main,
        .fi-simple-page,
        .fi-simple-main-ctn {
            padding: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        body, html {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Remove ALL borders from Filament components */
        .fi-simple-page,
        .fi-simple-main,
        .fi-simple-header,
        .fi-simple-main-ctn,
        [class*="fi-simple"] {
            border: none !important;
            border-top: none !important;
            border-bottom: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        
        /* Remove container borders */
        .fi-panel,
        .fi-body {
            border: none !important;
        }
        
        .split-login-container {
            display: flex;
            min-height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            border: none !important;
        }
        
        /* Left Side - Login Form */
        .login-form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: white;
        }
        
        .dark .login-form-side {
            background: rgb(17, 24, 39);
        }
        
        .login-form-wrapper {
            width: 100%;
            max-width: 28rem;
        }
        
        /* Logo at top of form */
        .form-logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-logo-container img {
            max-width: 8rem;
            height: auto;
            margin: 0 auto 1rem;
        }
        
        .form-brand-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: rgb(15, 23, 42);
            letter-spacing: -0.025em;
        }
        
        .dark .form-brand-name {
            color: white;
        }
        
        /* Right Side - Simple Dark Background */
        .logo-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: #0f172a;
            position: relative;
            overflow: hidden;
        }
        
        .dark .logo-side {
            background: #0f172a;
        }
        
        /* Subtle grid pattern background */
        .logo-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }
        
        /* Hide duplicate heading/subheading (we have logo at top) */
        .fi-simple-header {
            display: none !important;
        }
        
        /* Form header styling */
        .login-form-wrapper h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: rgb(15, 23, 42);
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }
        
        .dark .login-form-wrapper h1 {
            color: white;
        }
        
        .login-form-wrapper p {
            color: rgb(100, 116, 139);
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }
        
        .dark .login-form-wrapper p {
            color: rgb(148, 163, 184);
        }
        
        /* Form field improvements */
        .fi-fo-text-input input,
        .fi-input {
            border-radius: 0.5rem !important;
            border: 1px solid rgb(226, 232, 240) !important;
            transition: all 0.2s !important;
        }
        
        .fi-fo-text-input input:focus,
        .fi-input:focus {
            border-color: rgb(59, 130, 246) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Button styling */
        .fi-btn {
            border-radius: 0.5rem !important;
            font-weight: 500 !important;
            transition: all 0.2s !important;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .split-login-container {
                flex-direction: column;
            }
            
            .logo-side {
                min-height: 35vh;
                order: -1;
                padding: 2rem;
            }
            
            .login-form-side {
                min-height: 65vh;
                padding: 2.5rem 2rem;
            }
            
            .logo-container img {
                max-width: 18rem;
            }
            
            .brand-text {
                font-size: 2rem;
            }
            
            .login-form-wrapper {
                max-width: 24rem;
            }
        }
        
        @media (max-width: 640px) {
            .login-form-side {
                padding: 2rem 1.5rem;
            }
            
            .logo-side {
                padding: 1.5rem;
                min-height: 30vh;
            }
            
            .logo-container img {
                max-width: 14rem;
            }
            
            .brand-text {
                font-size: 1.5rem;
                margin-top: 1rem;
            }
            
            .brand-tagline {
                font-size: 0.9rem;
            }
            
            .login-form-wrapper h1 {
                font-size: 1.5rem;
            }
            
            .login-form-wrapper {
                max-width: 100%;
            }
        }
        
        @media (max-width: 390px) {
            .login-form-side {
                padding: 1.5rem 1rem;
            }
            
            .logo-side {
                padding: 1rem;
                min-height: 25vh;
            }
            
            .logo-container img {
                max-width: 10rem;
            }
            
            .brand-text {
                font-size: 1.25rem;
            }
            
            .brand-tagline {
                font-size: 0.8rem;
            }
        }
    </style>

    <div class="split-login-container">
        <!-- Left Side: Login Form -->
        <div class="login-form-side">
            <div class="login-form-wrapper">
                @php
                    $lightLogo = \App\Models\Setting::get('site_logo_full_lite');
                    $siteName = \App\Models\Setting::get('site_name', 'Cajiib');
                @endphp
                
                <!-- Logo and Company Name at Top -->
                <div class="form-logo-container">
                    @if($lightLogo)
                        <img src="{{ asset('storage/' . $lightLogo) }}" alt="{{ $siteName }}" />
                    @endif
                    <div class="form-brand-name">{{ $siteName }}</div>
                </div>

                <!-- Sign In Heading -->
                <h1 style="font-size: 1.875rem; font-weight: 600; color: rgb(15, 23, 42); margin-bottom: 0.5rem; text-align: center;">
                    {{ $this->getHeading() }}
                </h1>
                
                <p style="color: rgb(100, 116, 139); margin-bottom: 2rem; font-size: 0.875rem; text-align: center;">
                    {{ $this->getSubHeading() }}
                </p>

                {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.before') }}

                <x-filament-panels::form wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.after') }}
            </div>
        </div>

        <!-- Right Side: Simple Dark Background -->
        <div class="logo-side">
            <!-- Just dark background with grid pattern -->
        </div>
    </div>
</x-filament-panels::page.simple>
