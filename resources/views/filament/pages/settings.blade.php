<x-filament-panels::page>
    <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 500)">
        <!-- Skeleton Loader (shows ONLY on initial page load) -->
        <div x-show="loading" class="mb-6">
            <x-skeleton.form :fields="10" :columns="2" />
        </div>

        <!-- Actual Form Content (shows after initial load, stays visible during actions) -->
        <div x-show="!loading" x-cloak>
            <form wire:submit="save">
                {{ $this->form }}
                
                <div class="mt-6">
                    <x-filament::button type="submit" color="primary">
                        Save Settings
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        /* Hide loading spinner for faster image upload experience */
        .filepond--item .filepond--file-status-sub {
            display: none !important;
        }
        
        .filepond--item .filepond--file-info {
            opacity: 1 !important;
        }
        
        /* Faster animations */
        .filepond--item {
            transition: all 0.2s ease !important;
        }
        
        /* Smooth upload progress */
        .filepond--item-panel {
            transition: transform 0.15s ease !important;
        }
    </style>
    
    @script
    <script>
        // Listen for the refresh-page event and reload the page
        Livewire.on('refresh-page', () => {
            setTimeout(() => {
                window.location.reload();
            }, 1500); // 1.5 second delay to show the success message
        });
    </script>
    @endscript
</x-filament-panels::page>
