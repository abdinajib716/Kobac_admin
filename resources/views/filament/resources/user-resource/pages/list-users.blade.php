<x-filament-panels::page>
    <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 300)">
        <!-- Skeleton Loader (shows on initial load) -->
        <div x-show="loading" class="mb-6">
            <x-skeleton.table :rows="10" :columns="5" />
        </div>

        <!-- Actual Table Content (shows after delay) -->
        <div x-show="!loading" x-cloak>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
