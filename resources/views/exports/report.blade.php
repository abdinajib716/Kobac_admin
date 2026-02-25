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
    <div class="meta">
        Generated at: {{ now()->toDateTimeString() }}
    </div>

    @if (!empty($summary))
        <div class="summary">
            @foreach ($summary as $key => $value)
                <p><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? '-') }}</p>
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
                    <td colspan="{{ count($columns) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

