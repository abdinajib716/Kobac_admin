<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; margin: 20px; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        .meta { margin-bottom: 12px; color: #374151; }
        .meta p { margin: 2px 0; }
        .summary { margin: 12px 0; padding: 10px; border: 1px solid #e5e7eb; background: #f9fafb; }
        .summary p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; }
        .text-right { text-align: right; }
        .footer { margin-top: 14px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Customer Statement</h1>

    <div class="meta">
        <p><strong>Business:</strong> {{ $business_name }}</p>
        <p><strong>Customer:</strong> {{ $customer['name'] ?? '-' }}</p>
        <p><strong>Phone:</strong> {{ $customer['phone'] ?? '-' }}</p>
        <p><strong>Period:</strong> {{ $from ?? 'Beginning' }} to {{ $to ?? 'Today' }}</p>
    </div>

    <div class="summary">
        <p><strong>Opening Balance ({{ $currency }}):</strong> {{ number_format((float) $opening_balance, 2) }}</p>
        <p><strong>Total Debit ({{ $currency }}):</strong> {{ number_format((float) $total_debit, 2) }}</p>
        <p><strong>Total Credit ({{ $currency }}):</strong> {{ number_format((float) $total_credit, 2) }}</p>
        <p><strong>Closing Balance ({{ $currency }}):</strong> {{ number_format((float) $closing_balance, 2) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th class="text-right">Debit ({{ $currency }})</th>
                <th class="text-right">Credit ({{ $currency }})</th>
                <th class="text-right">Balance ({{ $currency }})</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-right">{{ number_format((float) $row['debit'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $row['credit'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $row['balance'], 2) }}</td>
                    <td>{{ $row['created_by'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No transactions found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated at {{ \Carbon\Carbon::parse($generated_at)->format('d/m/Y H:i:s') }} | Kobac
    </div>
</body>
</html>
