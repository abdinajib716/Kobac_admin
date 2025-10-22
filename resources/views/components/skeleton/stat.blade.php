@props([])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6']) }}>
    <!-- Icon -->
    <div class="skeleton-box h-12 w-12 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div>
    
    <!-- Value -->
    <div class="skeleton-box h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mb-2"></div>
    
    <!-- Label -->
    <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
</div>
