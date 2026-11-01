{{-- Scheduled Exceptions & Holidays Cards Partial --}}
<div class="p-4 border-b border-gray-100 dark:border-gray-700/60 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white dark:bg-gray-800/80">
    <div>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i data-lucide="list-filter" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
            Scheduled Exceptions & Holidays
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            Showing {{ $exceptions->total() }} scheduled record(s) on the calendar.
        </p>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search Input -->
        <div class="relative">
            <input type="text"
                   x-model="searchQuery"
                   @input="onSearchInput()"
                   placeholder="Search title or notice..."
                   class="text-xs font-medium pl-8 pr-7 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none focus:border-brand-500 w-44 sm:w-56 transition">
            <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none"></i>
            <button type="button" x-show="searchQuery" @click="clearSearch()" class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i data-lucide="x" class="w-3 h-3"></i>
            </button>
        </div>

        <!-- Type Filter Dropdown -->
        <div class="relative">
            <select x-model="selectedType" @change="fetchExceptions(1)" class="text-xs font-semibold px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 outline-none transition">
                <option value="all">All Types</option>
                <option value="holiday">Full Closures</option>
                <option value="custom_hours">Adjusted Hours</option>
                <option value="event">Special Events</option>
            </select>
        </div>

        <!-- Filter Pills: All / Upcoming / Past -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700/50 p-1 rounded-xl">
            <button type="button"
                    @click="setFilter('all')"
                    :class="selectedFilter === 'all' ? 'bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                    class="px-3 py-1 rounded-lg text-xs font-bold transition">
                All
            </button>
            <button type="button"
                    @click="setFilter('upcoming')"
                    :class="selectedFilter === 'upcoming' ? 'bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                    class="px-3 py-1 rounded-lg text-xs font-bold transition">
                Upcoming
            </button>
            <button type="button"
                    @click="setFilter('past')"
                    :class="selectedFilter === 'past' ? 'bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                    class="px-3 py-1 rounded-lg text-xs font-bold transition">
                Past
            </button>
        </div>
    </div>
</div>

<div class="relative p-4 sm:p-5 bg-gray-50/50 dark:bg-gray-900/30 min-h-[160px]">
    <!-- Subtle Loading Overlay -->
    <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity rounded-b-2xl">
        <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white dark:bg-gray-800 shadow-md border border-gray-100 dark:border-gray-700 text-xs font-semibold text-brand-600 dark:text-brand-400">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-brand-600 dark:text-brand-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Updating exceptions...</span>
        </div>
    </div>

    <!-- Exception Cards List -->
    <div class="space-y-3">
        @forelse($exceptions as $exc)
            @php
                $excDate = \Carbon\Carbon::parse($exc->date, 'Asia/Manila');
                $isToday = $excDate->isToday();
                $isPast = $excDate->isPast() && !$isToday;
                $isClosed = (bool)$exc->is_closed || $exc->type === 'holiday';

                // Type configuration for styling
                if ($isClosed) {
                    $cardBorder = 'border-teal-200 dark:border-teal-900/60 hover:border-teal-400 dark:hover:border-teal-700';
                    $iconBg = 'bg-teal-50 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/60';
                    $iconName = 'calendar-x';
                    $pillClass = 'bg-teal-100/80 text-teal-800 dark:bg-teal-900/50 dark:text-teal-200 border border-teal-200 dark:border-teal-800';
                    $statusLabel = 'CLOSED';
                } elseif ($exc->type === 'custom_hours') {
                    $cardBorder = 'border-cyan-200 dark:border-cyan-900/60 hover:border-cyan-400 dark:hover:border-cyan-700';
                    $iconBg = 'bg-cyan-50 dark:bg-cyan-950/50 text-cyan-700 dark:text-cyan-300 border border-cyan-200/60 dark:border-cyan-800/60';
                    $iconName = 'clock';
                    $pillClass = 'bg-cyan-100/80 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-200 border border-cyan-200 dark:border-cyan-800';
                    $statusLabel = 'ADJUSTED HOURS';
                } else {
                    $cardBorder = 'border-purple-200 dark:border-purple-900/60 hover:border-purple-400 dark:hover:border-purple-700';
                    $iconBg = 'bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60';
                    $iconName = 'sparkles';
                    $pillClass = 'bg-purple-100/80 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200 border border-purple-200 dark:border-purple-800';
                    $statusLabel = 'EVENT';
                }
            @endphp

            <div class="p-4 rounded-2xl bg-white dark:bg-gray-800 border {{ $cardBorder }} shadow-xs hover:shadow-sm transition-colors duration-200 ease-out {{ $isPast ? 'opacity-75' : '' }}">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    
                    <!-- Left: Compact Tinted Icon Panel + Center Details -->
                    <div class="flex items-start gap-3.5 min-w-0 flex-1">
                        <!-- Left Icon Panel (approx 44-48px) -->
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl {{ $iconBg }} flex items-center justify-center shrink-0 shadow-2xs transition-colors duration-200">
                            <i data-lucide="{{ $iconName }}" class="w-5 h-5"></i>
                        </div>

                        <!-- Center Info Content -->
                        <div class="min-w-0 flex-1 space-y-1">
                            <!-- Line 1: Title and Status Pill -->
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-tight truncate transition-colors duration-200">
                                    {{ $exc->title }}
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase transition-colors duration-200 {{ $pillClass }}">
                                    {{ $statusLabel }}
                                </span>
                                @if($isToday)
                                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition-colors duration-200">
                                        Today
                                    </span>
                                @endif
                            </div>

                            <!-- Line 2: Date + Prominent Hours (for custom_hours) -->
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200">
                                <div class="flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300 transition-colors duration-200">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-400"></i>
                                    <span>{{ $excDate->format('F j, Y') }}</span>
                                    <span class="text-gray-400 text-[11px]">({{ $excDate->format('l') }})</span>
                                </div>

                                @if(!$isClosed && $exc->type === 'custom_hours' && $exc->open_time && $exc->close_time)
                                    <div class="flex items-center gap-1.5 font-semibold text-cyan-800 dark:text-cyan-300 bg-cyan-100/70 dark:bg-cyan-900/40 px-2 py-0.5 rounded-md border border-cyan-200/60 dark:border-cyan-800/60 transition-colors duration-200">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400"></i>
                                        <span>{{ \Carbon\Carbon::parse($exc->open_time)->format('g:i A') }} → {{ \Carbon\Carbon::parse($exc->close_time)->format('g:i A') }}</span>
                                    </div>
                                @elseif($isClosed)
                                    <span class="text-teal-800 dark:text-teal-300 font-semibold text-[11px] transition-colors duration-200">
                                        Spa closed all day
                                    </span>
                                @endif
                            </div>

                            <!-- Line 3: Public Customer Notice Message (if available) -->
                            @if(!empty($exc->notice_message))
                                <div class="text-xs text-gray-600 dark:text-gray-300 flex items-start gap-1.5 pt-0.5 transition-colors duration-200">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5 text-gray-400 shrink-0 mt-0.5"></i>
                                    <span class="italic leading-relaxed">“{{ $exc->notice_message }}”</span>
                                </div>
                            @endif

                            <!-- Line 4: Internal Staff Note (Staff Only) -->
                            @if(!empty($exc->description))
                                <div class="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-1.5 pt-0.5 transition-colors duration-200">
                                    <span class="font-semibold uppercase text-[9px] tracking-wider text-gray-400 border border-gray-200 dark:border-gray-700 rounded px-1">Staff Note</span>
                                    <span class="truncate">{{ $exc->description }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Existing Edit and Delete Actions -->
                    <div class="flex items-center justify-end sm:self-center gap-2 border-t sm:border-t-0 pt-2 sm:pt-0 border-gray-100 dark:border-gray-700/60 shrink-0">
                        <button type="button"
                                @click="startEdit({
                                    id: {{ $exc->id }},
                                    date: '{{ $excDate->toDateString() }}',
                                    title: {{ json_encode($exc->title) }},
                                    type: '{{ $exc->type }}',
                                    is_closed: {{ $exc->is_closed ? 'true' : 'false' }},
                                    open_time: '{{ $exc->open_time ? substr($exc->open_time, 0, 5) : '10:00' }}',
                                    close_time: '{{ $exc->close_time ? substr($exc->close_time, 0, 5) : '17:00' }}',
                                    notice_message: {{ json_encode($exc->notice_message ?? '') }},
                                    description: {{ json_encode($exc->description ?? '') }}
                                })"
                                class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-brand-600 dark:text-brand-400 hover:text-brand-700 font-semibold text-xs flex items-center gap-1.5 transition">
                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            <span>Edit</span>
                        </button>

                        <form id="delete-exception-{{ $exc->id }}" method="POST" action="{{ route('business-exceptions.destroy', $exc) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                    class="px-3 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900/60 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-semibold text-xs flex items-center gap-1.5 transition"
                                    onclick="confirmDeleteException('{{ $exc->id }}', '{{ $excDate->format('M d, Y') }}', '{{ addslashes($exc->title) }}')">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-400 bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                <div class="flex flex-col items-center justify-center gap-2">
                    <i data-lucide="calendar" class="w-8 h-8 text-gray-300 dark:text-gray-600"></i>
                    <span class="text-xs">No scheduled exceptions match the selected filter.</span>
                </div>
            </div>
        @endforelse
    </div>
</div>

@if($exceptions->hasPages())
    <div class="p-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between bg-white dark:bg-gray-800/80">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Showing <span class="font-semibold">{{ $exceptions->firstItem() ?? 0 }}</span> to <span class="font-semibold">{{ $exceptions->lastItem() ?? 0 }}</span> of <span class="font-semibold">{{ $exceptions->total() }}</span> results
        </div>
        <div class="flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if ($exceptions->onFirstPage())
                <span class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-300 dark:text-gray-600 cursor-not-allowed">Previous</span>
            @else
                <button type="button" @click="fetchExceptions({{ $exceptions->currentPage() - 1 }})" class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Previous</button>
            @endif

            {{-- Page Numbers --}}
            @foreach ($exceptions->getUrlRange(max(1, $exceptions->currentPage() - 2), min($exceptions->lastPage(), $exceptions->currentPage() + 2)) as $page => $url)
                @if ($page == $exceptions->currentPage())
                    <span class="px-2.5 py-1 rounded-lg bg-brand-600 text-xs font-bold text-white shadow-xs">{{ $page }}</span>
                @else
                    <button type="button" @click="fetchExceptions({{ $page }})" class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">{{ $page }}</button>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($exceptions->hasMorePages())
                <button type="button" @click="fetchExceptions({{ $exceptions->currentPage() + 1 }})" class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Next</button>
            @else
                <span class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-300 dark:text-gray-600 cursor-not-allowed">Next</span>
            @endif
        </div>
    </div>
@endif
