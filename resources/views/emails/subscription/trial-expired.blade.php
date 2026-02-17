@extends('emails.layout', [
    'heading' => 'Your Trial Has Expired',
    'headerIcon' => '⌛',
    'headerStyle' => 'danger',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $upgradeUrl ?? '#',
    'ctaText' => 'Subscribe Now',
    'ctaStyle' => 'danger',
])

@section('content')
    <p>Your free trial for the <strong>{{ $planName }}</strong> plan has expired.</p>

    <div class="info-box danger">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Trial ended:</span> {{ $trialEndedAt }}<br>
            <span class="label">Status:</span> <span class="badge badge-danger">Expired</span>
        </p>
    </div>

    <p>Your write access has been suspended. To regain full access to all features, please subscribe to a paid plan.</p>

    <p>Your data is safe — subscribe anytime to pick up right where you left off.</p>
@endsection
