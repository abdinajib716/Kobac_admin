@extends('emails.layout', [
    'heading' => 'You\'ve Been Invited!',
    'headerIcon' => '👥',
    'headerStyle' => 'primary',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $loginUrl,
    'ctaText' => 'Login to Get Started',
    'ctaStyle' => 'primary',
])

@section('content')
    <p>You have been invited to join <strong>{{ $business->name }}</strong> as a team member on {{ config('app.name') }}.</p>

    <div class="info-box primary">
        <p>
            <span class="label">Business:</span> {{ $business->name }}<br>
            <span class="label">Your Role:</span> <span class="badge badge-primary">{{ ucfirst($role) }}</span><br>
            @if($branchName)
                <span class="label">Branch:</span> {{ $branchName }}<br>
            @else
                <span class="label">Access:</span> All Branches<br>
            @endif
        </p>
    </div>

    @if($temporaryPassword)
        <div class="info-box warning">
            <p><strong>Your Login Credentials:</strong></p>
            <p>
                <span class="label">Email:</span> {{ $user->email }}<br>
                <span class="label">Temporary Password:</span> <code>{{ $temporaryPassword }}</code>
            </p>
            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                <strong>Important:</strong> Please change your password after your first login for security purposes.
            </p>
        </div>
    @else
        <p>You can login using your existing account credentials.</p>
    @endif

    <h3>What You Can Do:</h3>
    <ul>
        @if($role === 'admin')
            <li>Full access to all business features</li>
            <li>Manage team members and staff</li>
            <li>View reports and analytics</li>
        @else
            <li>Access features assigned by your administrator</li>
            <li>Record transactions and manage data</li>
        @endif
    </ul>

    <p>Click the button below to login and start collaborating with your team!</p>
@endsection
