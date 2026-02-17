@extends('emails.layout', [
    'heading' => 'Subscription Activated!',
    'headerIcon' => '🎉',
    'headerStyle' => 'success',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $dashboardUrl ?? '#',
    'ctaText' => 'Go to Dashboard',
    'ctaStyle' => 'success',
])

@section('content')
    <p>Great news! Your subscription has been successfully activated.</p>

    <div class="info-box success">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Status:</span> <span class="badge badge-success">Active</span><br>
            <span class="label">Started:</span> {{ $startsAt }}<br>
            <span class="label">Expires:</span> {{ $endsAt }}<br>
            @if(!empty($paymentMethod))
                <span class="label">Payment:</span> {{ $paymentMethod }}
            @endif
        </p>
    </div>

    <p>You now have full access to all features included in your plan. Enjoy!</p>
@endsection
