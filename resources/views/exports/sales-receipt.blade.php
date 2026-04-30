<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 12px; }
        h1 { margin: 0 0 4px; font-size: 18px; text-align: center; }
        .center { text-align: center; }
        .meta { margin: 10px 0; }
        .meta p, .totals p, .footer p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border-bottom: 1px dashed #cbd5e1; padding: 4px 2px; text-align: left; }
        th { font-weight: 700; }
        .text-right { text-align: right; }
        .totals { margin-top: 10px; }
        .footer { margin-top: 12px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $business?->name ?? 'Kobac' }}</h1>
    <div class="center">
        <p>{{ $branch?->name }}</p>
        <p>Receipt: {{ $sale->receipt_number }}</p>
        <p>Sale: {{ $sale->sale_number }}</p>
    </div>

    <div class="meta">
        <p><strong>Date:</strong> {{ $sale->sold_at?->format('d/m/Y H:i') }}</p>
        <p><strong>Cashier:</strong> {{ $cashier?->name ?? '-' }}</p>
        <p><strong>Payment:</strong> {{ ucfirst($sale->sale_type) }}</p>
        <p><strong>Customer:</strong> {{ $customer?->name ?? 'Walk-in Customer' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->product_name_snapshot }}</td>
                    <td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Subtotal ({{ $currency }}):</strong> {{ number_format((float) $sale->subtotal, 2) }}</p>
        <p><strong>Discount ({{ $currency }}):</strong> {{ number_format((float) $sale->discount_total, 2) }}</p>
        <p><strong>Tax ({{ $currency }}):</strong> {{ number_format((float) $sale->tax_total, 2) }}</p>
        <p><strong>Total ({{ $currency }}):</strong> {{ number_format((float) $sale->total, 2) }}</p>
        <p><strong>Paid ({{ $currency }}):</strong> {{ number_format((float) $sale->amount_paid, 2) }}</p>
        <p><strong>Due ({{ $currency }}):</strong> {{ number_format((float) $sale->amount_due, 2) }}</p>
    </div>

    <div class="footer">
        <p>Generated at {{ \Carbon\Carbon::parse($generated_at)->format('d/m/Y H:i:s') }}</p>
        <p>Kobac</p>
    </div>
</body>
</html>
