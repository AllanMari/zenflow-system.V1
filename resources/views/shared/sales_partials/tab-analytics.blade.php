<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="card-hover bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 {{ $safeRevenueChange >= 0 ? 'bg-green-500' : 'bg-red-500' }}"></div>
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-400 mb-4">Revenue Growth</p>
        <div class="flex items-baseline gap-2 mb-4">
            <span class="text-3xl font-extrabold {{ $safeRevenueChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} tracking-tight">
                {{ $revenueChangeLabel }}%
            </span>
        </div>
        <div class="flex w-full h-2.5 bg-gray-100 dark:bg-neutral-700 rounded-full overflow-hidden">
            <div class="h-full {{ $safeRevenueChange >= 0 ? 'bg-green-500' : 'bg-red-500' }} bar-animate rounded-full" style="width: {{ min(abs($safeRevenueChange), 100) }}%"></div>
        </div>
    </div>

    <div class="card-hover bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-6 relative overflow-hidden">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-400 mb-5">Appointment Health</p>
        @php
            $healthMetrics = [
                ['label' => 'Completion Rate', 'value' => $safeCompletionRate, 'color' => 'bg-green-500', 'text' => 'text-green-600 dark:text-green-400'],
                ['label' => 'No-Show Rate', 'value' => $safeNoShowRate, 'color' => 'bg-red-500', 'text' => 'text-red-600 dark:text-red-400'],
                ['label' => 'Cancellation Rate', 'value' => $safeCancellationRate, 'color' => 'bg-rose-500', 'text' => 'text-rose-600 dark:text-rose-400'],
            ];
        @endphp
        <div class="space-y-4 mb-5">
            @foreach($healthMetrics as $m)
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-xs font-semibold text-gray-700 dark:text-neutral-200">{{ $m['label'] }}</span>
                    <span class="text-xs font-extrabold {{ $m['text'] }}">{{ $m['value'] }}%</span>
                </div>
                <div class="flex w-full h-2 bg-gray-100 dark:bg-neutral-700 rounded-full overflow-hidden">
                    <div class="h-full {{ $m['color'] }} bar-animate rounded-full" style="width: {{ $m['value'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card-hover bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-6 relative overflow-hidden">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-400 mb-5">Top Services by Revenue</p>
        <div class="space-y-4">
            @forelse($topServices as $name => $amount)
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-xs font-semibold text-gray-700 dark:text-neutral-200 truncate max-w-[60%]">{{ $name }}</span>
                    <span class="text-xs font-extrabold text-brand-600 dark:text-brand-400">{{ $currency }}{{ number_format($amount, 2) }}</span>
                </div>
                <div class="flex w-full h-1.5 bg-gray-100 dark:bg-neutral-700 rounded-full overflow-hidden">
                    <div class="h-full bg-brand-500 bar-animate rounded-full" style="width: {{ $maxSvc > 0 ? ($amount / $maxSvc) * 100 : 0 }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm font-medium text-gray-400">No service data</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card-hover bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-6 mb-6 relative overflow-hidden">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
        <h3 class="text-xs font-bold text-gray-700 dark:text-neutral-300 uppercase tracking-wider">12-Month Booking Trends</h3>
    </div>
    <div class="relative h-52"><canvas id="monthlySpikeChart"></canvas></div>
</div>