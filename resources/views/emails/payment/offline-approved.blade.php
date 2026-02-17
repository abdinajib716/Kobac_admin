@extends('emails.layout', [
    'heading' => 'Payment Approved!',
    'headerIcon' => '✅',
    'headerStyle' => 'success',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $dashboardUrl ?? '#',
    'ctaText' => 'Go to Dashboard',
    'ctaStyle' => 'success',
])

@section('content')
    <p>Great news! Your offline payment has been <strong>approved</strong> and your subscription is now active.</p>

    <div class="info-box success">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Amount:</span> ${{ number_format($amount, 2) }} {{ $currency }}<br>
            <span class="label">Reference:</span> {{ $referenceId }}<br>
            <span class="label">Status:</span> <span class="badge badge-success">Approved</span><br>
            <span class="label">Approved on:</span> {{ $approvedAt }}
        </p>
    </div>

    <p>You now have full access to all features included in your plan. Enjoy!</p>
@endsection
