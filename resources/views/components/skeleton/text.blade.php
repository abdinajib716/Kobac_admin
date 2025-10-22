@props([
    'lines' => 1,
    'width' => 'full',
])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-2']) }}>
    @for($i = 0; $i < $lines; $i++)
        @php
            $lineWidth = match($width) {
                'full' => 'w-full',
                '3/4' => 'w-3/4',
                '2/3' => 'w-2/3',
                '1/2' => 'w-1/2',
                '1/3' => 'w-1/3',
                '1/4' => 'w-1/4',
                default => 'w-full'
            };
            
            // Last line is shorter
            if ($i === $lines - 1 && $lines > 1) {
                $lineWidth = 'w-2/3';
            }
        @endphp
        <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded {{ $lineWidth }}"></div>
    @endfor
</div>
