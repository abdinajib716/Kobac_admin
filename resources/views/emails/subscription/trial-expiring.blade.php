@extends('emails.layout', [
    'heading' => 'Your Trial is Ending Soon',
    'headerIcon' => '⏳',
    'headerStyle' => 'warning',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $upgradeUrl ?? '#',
    'ctaText' => 'Upgrade Now',
    'ctaStyle' => 'warning',
])

@section('content')
    <p>Your free trial for the <strong>{{ $planName }}</strong> plan is ending soon.</p>

    <div class="info-box warning">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Trial ends:</span> {{ $trialEndsAt }}<br>
            <span class="label">Days remaining:</span> {{ $daysRemaining }} day{{ $daysRemaining !== 1 ? 's' : '' }}
        </p>
    </div>

    <p>To continue using all features without interruption, upgrade to a paid subscription before your trial expires.</p>

    <p>After your trial ends:</p>
    <p>
        • You will lose write access (no new transactions)<br>
        • Your existing data will be preserved<br>
        • You can upgrade at any time to restore full access
    </p>
@endsection
