@extends('emails.layout', [
    'heading' => 'Payment Request Submitted',
    'headerIcon' => '📝',
    'headerStyle' => '',
    'greeting' => 'Hello ' . $user->name . ',',
])

@section('content')
    <p>Your offline payment request has been submitted and is now pending admin approval.</p>

    <div class="info-box">
        <p>
            <span class="label">Plan:</span> {{ $planName }}<br>
            <span class="label">Amount:</span> ${{ number_format($amount, 2) }} {{ $currency }}<br>
            <span class="label">Reference:</span> {{ $referenceId }}<br>
            <span class="label">Status:</span> <span class="badge badge-warning">Pending Approval</span><br>
            <span class="label">Submitted:</span> {{ $submittedAt }}
        </p>
    </div>

    <p>Our team will review your payment request and you will be notified once it has been processed. This usually takes up to 24 hours.</p>

    @if(!empty($instructions))
        <div class="info-box warning">
            <p><strong>Payment Instructions:</strong><br>{{ $instructions }}</p>
        </div>
    @endif
@endsection
