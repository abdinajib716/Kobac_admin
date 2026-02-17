<x-filament-panels::page>
    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php $stats = $this->getStats(); @endphp
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-500">{{ $stats['total_features'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Features</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-success-500">{{ $stats['api_endpoints'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">API Endpoints</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-warning-500">{{ $stats['middleware'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Middleware</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-info-500">{{ $stats['admin_resources'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Admin Resources</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Features by Category --}}
    @foreach($this->getFeatures() as $category)
        <x-filament::section class="mb-4">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-dynamic-component :component="$category['icon']" class="w-5 h-5 text-{{ $category['color'] }}-500" />
                    <span>{{ $category['category'] }}</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($category['features'] as $feature)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($feature['status'] === 'active')
                                <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                            @else
                                <x-heroicon-o-clock class="w-5 h-5 text-warning-500" />
                            @endif
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $feature['name'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $feature['description'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach

    {{-- Quick Links --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-link class="w-5 h-5 text-primary-500" />
                <span>Documentation</span>
            </div>
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ url('/') }}/API_ENDPOINTS.md" target="_blank" class="flex items-center gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <x-heroicon-o-document-text class="w-6 h-6 text-primary-500" />
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">API Endpoints</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Complete API reference</div>
                </div>
            </a>
            <a href="{{ url('/') }}/API_IMPLEMENTATION.md" target="_blank" class="flex items-center gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <x-heroicon-o-code-bracket class="w-6 h-6 text-success-500" />
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">Implementation Guide</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Technical documentation</div>
                </div>
            </a>
            <a href="{{ url('/') }}/IMPLEMENTATION_PHASES.md" target="_blank" class="flex items-center gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-warning-500" />
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">Implementation Phases</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Project roadmap</div>
                </div>
            </a>
        </div>
    </x-filament::section>
</x-filament-panels::page>
