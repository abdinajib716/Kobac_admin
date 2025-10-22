<x-filament-panels::page>
    <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 300)">
        <!-- Skeleton Loader -->
        <div x-show="loading" x-cloak>
            <x-skeleton.list />
        </div>

        <!-- Actual Content -->
        <div x-show="!loading" x-cloak>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
