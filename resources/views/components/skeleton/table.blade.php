@props([
    'rows' => 5,
    'columns' => 4,
])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden']) }}>
    <!-- Table Header -->
    <div class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="grid gap-4" style="grid-template-columns: repeat({{ $columns }}, 1fr);">
            @for($i = 0; $i < $columns; $i++)
                <div class="skeleton-box h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
            @endfor
        </div>
    </div>

    <!-- Table Rows -->
    @for($row = 0; $row < $rows; $row++)
        <div class="border-b border-gray-200 dark:border-gray-700 p-4">
            <div class="grid gap-4" style="grid-template-columns: repeat({{ $columns }}, 1fr);">
                @for($col = 0; $col < $columns; $col++)
                    <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded {{ $col === 0 ? 'w-full' : 'w-2/3' }}"></div>
                @endfor
            </div>
        </div>
    @endfor
</div>
