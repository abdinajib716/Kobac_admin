@props([
    'fields' => 5,
    'columns' => 1,
])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6']) }}>
    <div class="grid gap-6 {{ $columns > 1 ? 'md:grid-cols-' . $columns : '' }}">
        @for($i = 0; $i < $fields; $i++)
            <div>
                <!-- Label -->
                <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-2"></div>
                <!-- Input Field -->
                <div class="skeleton-box h-10 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
            </div>
        @endfor
    </div>

    <!-- Submit Button -->
    <div class="mt-6 flex gap-2">
        <div class="skeleton-box h-10 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
        <div class="skeleton-box h-10 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
    </div>
</div>
