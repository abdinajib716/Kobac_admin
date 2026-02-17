<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $deviceStats = $this->getDeviceStats();
        $coverage = $this->getUserCoverage();
        $recentNotifications = $this->getRecentNotifications();
        $weeklyChart = $this->getWeeklyChart();
        $firebaseStatus = $this->getFirebaseStatus();
    @endphp

    {{-- Firebase Status Banner --}}
    @if(!$firebaseStatus['enabled'])
        <div class="rounded-xl border border-warning-300 bg-warning-50 dark:border-warning-600 dark:bg-warning-950/50 p-4 mb-6">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-warning-500" />
                <div>
                    <p class="font-semibold text-warning-700 dark:text-warning-400">Firebase Push Notifications Disabled</p>
                    <p class="text-sm text-warning-600 dark:text-warning-500">
                        Enable Firebase in <a href="{{ route('filament.admin.pages.settings') }}" class="underline font-medium">Settings → Firebase</a> to start sending push notifications.
                    </p>
                </div>
            </div>
        </div>
    @elseif(!$firebaseStatus['has_credentials'])
        <div class="rounded-xl border border-danger-300 bg-danger-50 dark:border-danger-600 dark:bg-danger-950/50 p-4 mb-6">
            <div class="flex items-center gap-3">
                <x-heroicon-o-x-circle class="h-6 w-6 text-danger-500" />
                <div>
                    <p class="font-semibold text-danger-700 dark:text-danger-400">Firebase Credentials Missing</p>
                    <p class="text-sm text-danger-600 dark:text-danger-500">
                        Firebase is enabled but credentials are incomplete. Go to <a href="{{ route('filament.admin.pages.settings') }}" class="underline font-medium">Settings → Firebase</a> to configure.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-success-300 bg-success-50 dark:border-success-600 dark:bg-success-950/50 p-4 mb-6">
            <div class="flex items-center gap-3">
                <x-heroicon-o-check-circle class="h-6 w-6 text-success-500" />
                <div>
                    <p class="font-semibold text-success-700 dark:text-success-400">Firebase Connected</p>
                    <p class="text-sm text-success-600 dark:text-success-500">
                        Project: <span class="font-mono">{{ $firebaseStatus['project_id'] }}</span> • Topic: <span class="font-mono">{{ $firebaseStatus['default_topic'] }}</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- KPI Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Notifications --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-primary-100 p-2.5 dark:bg-primary-900/50">
                    <x-heroicon-o-bell class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_notifications']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Notifications</p>
                </div>
            </div>
        </div>

        {{-- Delivery Rate --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-success-100 p-2.5 dark:bg-success-900/50">
                    <x-heroicon-o-check-badge class="h-5 w-5 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['delivery_rate'] }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Delivery Rate</p>
                </div>
            </div>
        </div>

        {{-- Total Delivered --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-info-100 p-2.5 dark:bg-info-900/50">
                    <x-heroicon-o-paper-airplane class="h-5 w-5 text-info-600 dark:text-info-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_delivered']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Messages Delivered</p>
                </div>
            </div>
        </div>

        {{-- Failed --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-danger-100 p-2.5 dark:bg-danger-900/50">
                    <x-heroicon-o-x-circle class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['failed_notifications']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Failed Notifications</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Device Stats + User Coverage --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Device Breakdown --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Registered Devices</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-success-500"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-300">Android</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($deviceStats['android_tokens']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-info-500"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-300">iOS</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($deviceStats['ios_tokens']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-warning-500"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-300">Web</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($deviceStats['web_tokens']) }}</span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Total Active Tokens</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($deviceStats['total_tokens']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Unique Users</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($deviceStats['unique_users']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Inactive Tokens</span>
                    <span class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ number_format($deviceStats['inactive_tokens']) }}</span>
                </div>
            </div>
        </div>

        {{-- User Coverage --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Push Notification Coverage</h3>
            <div class="space-y-4">
                {{-- Coverage Progress Bar --}}
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Overall Coverage</span>
                        <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $coverage['coverage_percent'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                        <div class="bg-primary-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($coverage['coverage_percent'], 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $coverage['users_with_tokens'] }} of {{ $coverage['total_mobile_users'] }} mobile users have registered devices</p>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user class="h-4 w-4 text-success-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Individual Users</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($coverage['individual_with_tokens']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-building-office class="h-4 w-4 text-info-500" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Business Users</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($coverage['business_with_tokens']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Weekly Chart --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Deliveries (Last 7 Days)</h3>
        <div class="grid grid-cols-7 gap-2 items-end" style="height: 120px;">
            @php
                $maxValue = max(1, max($weeklyChart['data']));
            @endphp
            @foreach($weeklyChart['data'] as $index => $value)
                <div class="flex flex-col items-center gap-1">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ number_format($value) }}</span>
                    <div class="w-full bg-primary-500 dark:bg-primary-600 rounded-t-md transition-all duration-300"
                         style="height: {{ $maxValue > 0 ? max(4, ($value / $maxValue) * 80) : 4 }}px">
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $weeklyChart['labels'][$index] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Notifications --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Notifications</h3>
            <a href="{{ route('filament.admin.resources.push-notifications.index') }}"
               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                View All →
            </a>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($recentNotifications as $notification)
                <div class="p-4 flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $notification->title }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $notification->body }}</p>
                    </div>
                    <div class="flex items-center gap-3 ml-4 shrink-0">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                            @if($notification->status === 'sent') bg-success-50 text-success-700 dark:bg-success-900/50 dark:text-success-400
                            @elseif($notification->status === 'partial') bg-warning-50 text-warning-700 dark:bg-warning-900/50 dark:text-warning-400
                            @elseif($notification->status === 'failed') bg-danger-50 text-danger-700 dark:bg-danger-900/50 dark:text-danger-400
                            @elseif($notification->status === 'sending') bg-info-50 text-info-700 dark:bg-info-900/50 dark:text-info-400
                            @else bg-gray-50 text-gray-700 dark:bg-gray-900/50 dark:text-gray-400
                            @endif
                        ">{{ ucfirst($notification->status) }}</span>
                        <span class="text-xs text-gray-400">{{ $notification->sent_at?->diffForHumans() ?? $notification->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <x-heroicon-o-bell-slash class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No push notifications sent yet.</p>
                    <a href="{{ route('filament.admin.resources.push-notifications.create') }}"
                       class="inline-flex items-center gap-1 mt-2 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                        <x-heroicon-o-paper-airplane class="h-4 w-4" />
                        Send your first notification
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
