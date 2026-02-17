<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }

        /* Base styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            color: #374151;
            line-height: 1.6;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f6f9;
            padding: 40px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            padding: 32px 40px;
            text-align: center;
        }

        .email-header.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .email-header.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .email-header.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .email-header .brand {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .email-header .icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.3;
        }

        /* Body */
        .email-body {
            padding: 36px 40px;
        }

        .greeting {
            font-size: 17px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }

        .email-body p {
            font-size: 15px;
            color: #4b5563;
            margin: 0 0 16px;
            line-height: 1.7;
        }

        /* Info Box */
        .info-box {
            background-color: #f0f7ff;
            border-left: 4px solid #3b82f6;
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            margin: 20px 0;
        }

        .info-box.warning {
            background-color: #fffbeb;
            border-left-color: #f59e0b;
        }

        .info-box.danger {
            background-color: #fef2f2;
            border-left-color: #ef4444;
        }

        .info-box.success {
            background-color: #f0fdf4;
            border-left-color: #10b981;
        }

        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #374151;
        }

        .info-box .label {
            font-weight: 600;
            color: #111827;
            display: inline-block;
            min-width: 120px;
        }

        /* Detail rows */
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }

        .detail-table td {
            padding: 10px 12px;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-table td:first-child {
            font-weight: 600;
            color: #374151;
            width: 40%;
        }

        .detail-table td:last-child {
            color: #6b7280;
        }

        /* CTA Button */
        .cta-wrapper {
            text-align: center;
            margin: 28px 0;
        }

        .cta-button {
            display: inline-block;
            padding: 14px 36px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.3px;
            transition: all 0.2s;
        }

        .cta-button.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .cta-button.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .cta-button.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fecaca; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }

        /* Footer */
        .email-footer {
            background-color: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .email-footer p {
            font-size: 12px;
            color: #9ca3af;
            margin: 0 0 4px;
            line-height: 1.5;
        }

        .email-footer a {
            color: #6b7280;
            text-decoration: underline;
        }

        /* Responsive */
        @media only screen and (max-width: 620px) {
            .email-wrapper { padding: 16px !important; }
            .email-body { padding: 24px 20px !important; }
            .email-header { padding: 24px 20px !important; }
            .email-footer { padding: 20px !important; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">

            {{-- Header --}}
            <div class="email-header {{ $headerStyle ?? '' }}">
                <div class="brand">{{ $brandName ?? config('app.name', 'KOBAC') }}</div>
                @if(!empty($headerIcon))
                    <div class="icon">{{ $headerIcon }}</div>
                @endif
                <h1>{{ $heading ?? 'Notification' }}</h1>
            </div>

            {{-- Body --}}
            <div class="email-body">
                @if(!empty($greeting))
                    <div class="greeting">{{ $greeting }}</div>
                @endif

                @yield('content')

                @if(!empty($ctaUrl) && !empty($ctaText))
                    <div class="cta-wrapper">
                        <a href="{{ $ctaUrl }}" class="cta-button {{ $ctaStyle ?? '' }}">{{ $ctaText }}</a>
                    </div>
                @endif

                @hasSection('extra')
                    @yield('extra')
                @endif

                <hr class="divider">

                <p style="font-size: 13px; color: #9ca3af;">
                    If you did not expect this email or believe it was sent in error, please contact our support team.
                </p>
            </div>

            {{-- Footer --}}
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'KOBAC') }}. All rights reserved.</p>
                <p>This is an automated message — please do not reply directly.</p>
            </div>

        </div>
    </div>
</body>
</html>
