@extends('emails.layout', [
    'heading' => 'Payment Failed',
    'headerIcon' => '💳',
    'headerStyle' => 'danger',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $retryUrl ?? '#',
    'ctaText' => 'Retry Payment',
    'ctaStyle' => 'danger',
])

@section('content')
    <p>We were unable to process your payment. Please review the details below and try again.</p>

    <div class="info-box danger">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Amount:</span> ${{ number_format($amount, 2) }} {{ $currency }}<br>
            <span class="label">Method:</span> {{ $paymentMethod }}<br>
            <span class="label">Reference:</span> {{ $referenceId }}<br>
            @if(!empty($errorMessage))
                <span class="label">Reason:</span> {{ $errorMessage }}
            @endif
        </p>
    </div>

    <p>Common reasons for payment failure:</p>
    <p>
        • Insufficient balance in your mobile wallet<br>
        • Payment was declined or timed out<br>
        • Network connectivity issues<br>
        • Incorrect phone number
    </p>

    <p>Please try again or use a different payment method. If the issue persists, contact our support team.</p>
@endsection
