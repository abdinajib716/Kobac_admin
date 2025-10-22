@props([
    'lines' => 3,
    'hasImage' => false,
    'hasActions' => false,
])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6']) }}>
    <!-- Image Skeleton -->
    @if($hasImage)
        <div class="skeleton-box h-48 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div>
    @endif

    <!-- Title Skeleton -->
    <div class="skeleton-box h-6 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-4"></div>

    <!-- Content Lines -->
    @for($i = 0; $i < $lines; $i++)
        <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded mb-3 {{ $i === $lines - 1 ? 'w-2/3' : 'w-full' }}"></div>
    @endfor

    <!-- Actions Skeleton -->
    @if($hasActions)
        <div class="flex gap-2 mt-4">
            <div class="skeleton-box h-10 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
            <div class="skeleton-box h-10 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
        </div>
    @endif
</div>
