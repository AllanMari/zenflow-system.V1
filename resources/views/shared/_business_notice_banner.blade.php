@php
    $isNoticeClosed = !empty($notice['is_closed']) || ($notice['type'] ?? '') === 'holiday';
    if ($isNoticeClosed) {
        $bannerBorder = 'border-teal-200/90 dark:border-teal-900/60';
        $bannerBg = 'bg-teal-50/70 dark:bg-teal-950/30';
        $iconBg = 'bg-teal-100/80 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60';
        $iconName = 'calendar-x';
        $pillClass = 'bg-teal-100/90 text-teal-800 dark:bg-teal-900/60 dark:text-teal-200 border-teal-200/80 dark:border-teal-800/80';
        $statusPill = 'CLOSED';
    } elseif (($notice['type'] ?? '') === 'custom_hours') {
        $bannerBorder = 'border-cyan-200/90 dark:border-cyan-900/60';
        $bannerBg = 'bg-cyan-50/70 dark:bg-cyan-950/30';
        $iconBg = 'bg-cyan-100/80 dark:bg-cyan-900/50 text-cyan-700 dark:text-cyan-300 border border-cyan-200/60 dark:border-cyan-800/60';
        $iconName = 'clock';
        $pillClass = 'bg-cyan-100/90 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-200 border-cyan-200/80 dark:border-cyan-800/80';
        $statusPill = 'ADJUSTED HOURS';
    } else {
        $bannerBorder = 'border-purple-200/90 dark:border-purple-900/60';
        $bannerBg = 'bg-purple-50/70 dark:bg-purple-950/30';
        $iconBg = 'bg-purple-100/80 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60';
        $iconName = 'sparkles';
        $pillClass = 'bg-purple-100/90 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200 border-purple-200/80 dark:border-purple-800/80';
        $statusPill = 'EVENT';
    }
@endphp

<div class="rounded-2xl border {{ $bannerBorder }} {{ $bannerBg }} p-4 sm:p-5 shadow-xs transition-colors duration-200 ease-out">
    <!-- Header Row: Icon + Eyebrow Label + Right Status Pill -->
    <div class="flex items-center justify-between gap-3 mb-2.5">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-lg {{ $iconBg }} flex items-center justify-center shrink-0 shadow-2xs transition-colors duration-200">
                <i data-lucide="{{ $iconName }}" class="w-4 h-4"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate">
                SPA ALEXANDRIA · BUSINESS NOTICE
            </span>
        </div>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border shrink-0 transition-colors duration-200 {{ $pillClass }}">
            {{ $statusPill }}
        </span>
    </div>

    <!-- Main Content: Notice Title -->
    <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white leading-snug transition-colors duration-200">
        {{ $notice['title'] }}
    </h4>

    <!-- Customer-Facing Notice Message -->
    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed transition-colors duration-200">
        @if(!empty($notice['notice_message']))
            {{ $notice['notice_message'] }}
        @elseif($isNoticeClosed)
            Spa Alexandria will be closed all day for {{ $notice['title'] }}. Online and counter bookings are unavailable for this date.
        @elseif(!empty($notice['formatted_hours']))
            Please note our adjusted operating schedule for this date: {{ $notice['formatted_hours'] }}.
        @else
            Special announcement for {{ $notice['title'] }}.
        @endif
    </p>

    <!-- Compact Date Line + Hours below message -->
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2.5 pt-2.5 border-t border-gray-200/70 dark:border-gray-800/80 text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
        <div class="flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">
            <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400"></i>
            <span>{{ $notice['day_name'] }}, {{ $notice['formatted_date'] }}</span>
        </div>

        @if(!$isNoticeClosed && !empty($notice['formatted_hours']))
            <div class="flex items-center gap-1.5 font-semibold text-cyan-800 dark:text-cyan-300 bg-cyan-100/70 dark:bg-cyan-900/40 px-2 py-0.5 rounded-md border border-cyan-200/60 dark:border-cyan-800/60 transition-colors duration-200">
                <i data-lucide="clock" class="w-3 h-3 text-cyan-600 dark:text-cyan-400"></i>
                <span>Operating Hours: {{ $notice['formatted_hours'] }}</span>
            </div>
        @elseif($isNoticeClosed)
            <span class="font-semibold text-teal-800 dark:text-teal-300 text-[11px] transition-colors duration-200">
                Closed All Day
            </span>
        @endif
    </div>
</div>
