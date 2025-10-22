@props([
    'items' => 5,
    'hasAvatar' => false,
])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700']) }}>
    @for($i = 0; $i < $items; $i++)
        <div class="p-4 flex items-center gap-4">
            @if($hasAvatar)
                <!-- Avatar -->
                <div class="skeleton-box h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full flex-shrink-0"></div>
            @endif
            
            <div class="flex-1">
                <!-- Title -->
                <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-2"></div>
                <!-- Description -->
                <div class="skeleton-box h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
            </div>

            <!-- Action -->
            <div class="skeleton-box h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded flex-shrink-0"></div>
        </div>
    @endfor
</div>
