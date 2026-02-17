@extends('emails.layout', [
    'heading' => 'Account Deactivated',
    'headerIcon' => '🚫',
    'headerStyle' => 'danger',
    'greeting' => 'Hello ' . $user->name . ',',
])

@section('content')
    <p>Your account has been <strong>deactivated</strong> by an administrator.</p>

    <div class="info-box danger">
        <p>
            <span class="label">Account:</span> {{ $user->email }}<br>
            <span class="label">Deactivated on:</span> {{ now()->format('M d, Y \a\t h:i A') }}<br>
            @if(!empty($reason))
                <span class="label">Reason:</span> {{ $reason }}
            @endif
        </p>
    </div>

    <p>While your account is deactivated:</p>
    <p>
        • You will not be able to log in<br>
        • Your data is preserved and not deleted<br>
        • API access is suspended
    </p>

    <p>If you believe this was done in error, please contact our support team for assistance.</p>
@endsection
