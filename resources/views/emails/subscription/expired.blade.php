@extends('emails.layout', [
    'heading' => 'Subscription Expired',
    'headerIcon' => '📋',
    'headerStyle' => 'danger',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $renewUrl ?? '#',
    'ctaText' => 'Renew Subscription',
    'ctaStyle' => 'danger',
])

@section('content')
    <p>Your subscription to the <strong>{{ $planName }}</strong> plan has expired.</p>

    <div class="info-box danger">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Expired on:</span> {{ $expiredAt }}<br>
            <span class="label">Status:</span> <span class="badge badge-danger">Expired</span>
        </p>
    </div>

    <p>Your write access has been suspended. To continue creating transactions and managing your business data, please renew your subscription.</p>

    <p>
        • All your data is safe and preserved<br>
        • Read access remains available<br>
        • Renew anytime to restore full functionality
    </p>
@endsection
