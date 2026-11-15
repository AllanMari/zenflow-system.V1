{{--
    Dismissible Academic Capstone Project Notice Partial
    Features two completely independent layouts:
      1. Mobile (< 640px): A dedicated card featuring an embossed teal header banner,
         independent close button bar, and clean readable card body.
      2. Desktop (>= 640px): The sleek Preline-inspired horizontal alert bar.
--}}
<div
    id="academicCapstoneNoticeContainer"
    class="academic-capstone-wrapper w-full transition-all duration-300"
    style="display: none;"
>
    {{-- =========================================================================
         MOBILE LAYOUT (< sm / 640px)
         A purpose-built mobile academic announcement card with its own distinct
         header pill, clear separation of badge, title, and body, and touch-target close button.
    ========================================================================== --}}
    <div
        id="academicCapstoneNotice"
        class="sm:hidden overflow-hidden rounded-2xl border border-teal-600/25 bg-white/95 text-slate-900 shadow-lg shadow-teal-950/5 ring-1 ring-teal-500/10 backdrop-blur-md transition-all duration-300 dark:border-teal-500/20 dark:bg-slate-900/95 dark:text-slate-100 dark:shadow-black/20"
        role="region"
        aria-label="Academic Capstone Project Notice"
    >
        {{-- Card Header: Distinct Teal Accent Header Strip --}}
        <div class="flex items-center justify-between border-b border-teal-600/15 bg-gradient-to-r from-teal-700 via-teal-800 to-teal-900 px-3.5 py-2 text-white dark:from-teal-800 dark:via-teal-900 dark:to-slate-950">
            <div class="flex min-w-0 items-center gap-2">
                {{-- Mortarboard icon --}}
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-white/15 text-teal-100 ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                    </svg>
                </span>
                <span class="truncate text-[11px] font-bold tracking-wider uppercase text-teal-50">
                    Academic Capstone Notice
                </span>
            </div>

            <div class="flex items-center gap-1.5">
                <span class="rounded-full bg-teal-500/30 px-2 py-0.5 text-[9px] font-semibold tracking-wide text-teal-100 ring-1 ring-inset ring-white/15">
                    Demo Only
                </span>
                {{-- Mobile Close Button: High-contrast, easy-to-tap touch target --}}
                <button
                    type="button"
                    aria-label="Dismiss academic capstone project notice"
                    class="flex h-7 w-7 items-center justify-center rounded-lg text-teal-100/80 transition hover:bg-white/15 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40 active:scale-90"
                    onclick="dismissCapstoneNotice()"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Card Body: Organized Reading Hierarchy with Dedicated Info Rows --}}
        <div class="space-y-2 p-3.5 text-slate-700 dark:text-slate-300">
            {{-- Lead statement --}}
            <p class="text-[11.5px] font-semibold leading-relaxed text-teal-950 dark:text-teal-200">
                You are visiting the Spa Alexandria Appointment and Workforce Scheduling System, developed as part of an academic capstone project.
            </p>

            {{-- Academic disclaimer detail --}}
            <p class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">
                This website is intended for demonstration, testing, and academic evaluation purposes only. It is not intended for actual commercial transactions or real customer bookings.
            </p>

            {{-- Privacy / Payment callout --}}
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 px-2.5 py-1.5 text-[10.5px] font-medium leading-relaxed text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
                <span class="font-bold text-amber-950 dark:text-amber-100">Notice:</span> Please do not submit real payment information or unnecessary sensitive personal information.
            </div>

            {{-- Courtesy closing --}}
            <p class="pt-0.5 text-[10.5px] font-medium text-teal-800/80 dark:text-teal-400">
                Thank you for your understanding!
            </p>
        </div>
    </div>

    {{-- =========================================================================
         DESKTOP / TABLET LAYOUT (>= sm / 640px)
         The established horizontal Preline UI alert bar, spacious and elegant.
    ========================================================================== --}}
    <div
        class="hidden sm:block overflow-hidden rounded-2xl border border-teal-200/90 bg-teal-50/90 p-4 sm:p-5 text-teal-950 shadow-md backdrop-blur-md transition-all duration-300 dark:border-teal-900/60 dark:bg-slate-900/95 dark:text-teal-100"
        role="region"
        aria-label="Academic Capstone Project Notice"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3.5 min-w-0 flex-1">
                {{-- Academic icon badge --}}
                <div class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-teal-600/10 text-teal-700 ring-1 ring-teal-600/20 dark:bg-teal-400/10 dark:text-teal-300 dark:ring-teal-400/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                    </svg>
                </div>

                {{-- Notice Body --}}
                <div class="min-w-0 flex-1 space-y-1.5">
                    {{-- Heading + Pill --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold tracking-wider uppercase text-teal-900 dark:text-teal-200">
                            ACADEMIC CAPSTONE PROJECT NOTICE
                        </span>
                        <span class="inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-semibold text-teal-800 ring-1 ring-inset ring-teal-600/20 dark:bg-teal-950/80 dark:text-teal-300 dark:ring-teal-500/30">
                            Demonstration &amp; Evaluation
                        </span>
                    </div>

                    {{-- Primary sentence --}}
                    <p class="text-xs sm:text-[13px] leading-relaxed text-teal-950 dark:text-teal-100 font-medium">
                        You are visiting the Spa Alexandria Appointment and Workforce Scheduling System, developed as part of an academic capstone project.
                    </p>

                    {{-- Disclaimer text --}}
                    <p class="text-xs leading-relaxed text-teal-900/80 dark:text-slate-300">
                        This website is intended for demonstration, testing, and academic evaluation purposes only. It is not intended for actual commercial transactions or real customer bookings.
                    </p>

                    {{-- Advisory note --}}
                    <p class="text-xs font-semibold leading-relaxed text-teal-950 dark:text-teal-200">
                        Please do not submit real payment information or unnecessary sensitive personal information.
                    </p>

                    {{-- Thank you --}}
                    <p class="text-xs text-teal-800/80 dark:text-teal-400">
                        Thank you for your understanding!
                    </p>
                </div>
            </div>

            {{-- Desktop Close Button --}}
            <div class="shrink-0 -mr-1 -mt-1 sm:mr-0 sm:mt-0">
                <button
                    type="button"
                    id="dismissCapstoneNoticeBtn"
                    aria-label="Dismiss academic capstone project notice"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-teal-800/70 transition-colors hover:bg-teal-100 hover:text-teal-950 focus:outline-none focus:ring-2 focus:ring-teal-500/40 active:scale-95 dark:text-teal-300/80 dark:hover:bg-slate-800 dark:hover:text-teal-100"
                    onclick="dismissCapstoneNotice()"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var storageKey = 'dismiss_capstone_notice';
        var container = document.getElementById('academicCapstoneNoticeContainer');
        if (!container) return;

        try {
            if (sessionStorage.getItem(storageKey) !== 'true') {
                container.style.display = 'block';
            }
        } catch (e) {
            container.style.display = 'block';
        }

        window.dismissCapstoneNotice = function() {
            try {
                sessionStorage.setItem(storageKey, 'true');
            } catch (e) {}
            if (container) {
                container.style.display = 'none';
            }
        };
    })();
</script>
