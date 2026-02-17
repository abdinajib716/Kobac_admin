@extends('emails.layout', [
    'heading' => 'Reset Your Password',
    'headerIcon' => '🔐',
    'headerStyle' => '',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $resetUrl,
    'ctaText' => 'Reset Password',
    'ctaStyle' => '',
])

@section('content')
    <p>We received a request to reset the password for your account associated with <strong>{{ $user->email }}</strong>.</p>

    <p>Click the button below to set a new password. This link will expire in <strong>{{ $expireMinutes }} minutes</strong>.</p>
@endsection

@section('extra')
    <div class="info-box warning">
        <p><strong>Didn't request this?</strong><br>
        If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
    </div>
@endsection
