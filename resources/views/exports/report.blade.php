<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 20px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 20px;
        }
        .meta {
            margin-bottom: 14px;
            color: #4b5563;
        }
        .summary {
            margin-bottom: 14px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .summary p {
            margin: 3px 0;
        }
        .stock-intro {
            margin: 8px 0 14px;
            font-size: 13px;
        }
        .stock-intro p {
            margin: 2px 0;
        }
        .totals-box {
            margin-top: 12px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .totals-box p {
            margin: 4px 0;
        }
        .footer {
            margin-top: 18px;
            font-size: 11px;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    @if (($meta['report_key'] ?? null) === 'stock')
        <div class="stock-intro">
            @if (!empty($meta['business_name']))
                <p><strong>{{ $meta['business_name'] }}</strong></p>
            @endif
            <p>
                <strong>{{ $meta['total_products_label'] ?? 'Total products' }}</strong>
                {{ $summary['total_items'] ?? 0 }}
            </p>
        </div>
    @else
        <div class="meta">
            {{ $meta['generated_label'] ?? 'Generated at' }}:
            {{ $meta['generated_at_display'] ?? now()->format('d/m/Y, H:i:s') }}
        </div>
    @endif

    @if (!empty($summary) && (($meta['report_key'] ?? null) !== 'stock'))
        <div class="summary">
            @foreach ($summary as $key => $value)
                <p>
                    <strong>{{ $meta['summary_labels'][$key] ?? ucwords(str_replace('_', ' ', $key)) }}:</strong>
                    {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? '-') }}
                </p>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach (array_keys($columns) as $key)
                        <td>{{ $row[$key] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">{{ __('mobile.reports.no_records') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (($meta['report_key'] ?? null) === 'stock')
        <div class="totals-box">
            <p><strong>{{ $meta['money_section_label'] ?? 'Totals' }}</strong></p>
            <p>
                {{ $meta['total_stock_value_label'] ?? 'Total stock value' }}
                ({{ $meta['currency'] ?? 'USD' }})
                <strong>{{ number_format((float) ($summary['total_cost_value'] ?? 0), 2) }}</strong>
            </p>
        </div>
    @endif

    <div class="footer">
        {{ $meta['generated_label'] ?? 'Generated at' }}
        {{ $meta['generated_at_display'] ?? now()->format('d/m/Y, H:i:s') }}
        | {{ $meta['app_name'] ?? 'Kobac' }} |
    </div>
</body>
</html>
