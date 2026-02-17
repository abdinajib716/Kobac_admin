@extends('emails.layout', [
    'heading' => 'Payment Rejected',
    'headerIcon' => '❌',
    'headerStyle' => 'danger',
    'greeting' => 'Hello ' . $user->name . ',',
    'ctaUrl' => $retryUrl ?? '#',
    'ctaText' => 'Submit New Payment',
    'ctaStyle' => '',
])

@section('content')
    <p>Unfortunately, your offline payment request has been <strong>rejected</strong>.</p>

    <div class="info-box danger">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Amount:</span> ${{ number_format($amount, 2) }} {{ $currency }}<br>
            <span class="label">Reference:</span> {{ $referenceId }}<br>
            <span class="label">Status:</span> <span class="badge badge-danger">Rejected</span><br>
            @if(!empty($reason))
                <span class="label">Reason:</span> {{ $reason }}
            @endif
        </p>
    </div>

    <p>If you believe this was done in error, you can submit a new payment request or contact our support team for assistance.</p>
@endsection
