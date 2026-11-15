{{--
    Dismissible Academic Capstone Project Notice Partial
    Visible by default on public-facing pages; hidden across navigation when dismissed in the session.
--}}
<aside
    id="academicCapstoneNotice"
    role="region"
    aria-label="Academic Capstone Project Notice"
    class="relative z-40 border-b border-amber-200/90 bg-gradient-to-r from-amber-50 via-amber-100/70 to-amber-50 text-amber-950 shadow-sm transition-all duration-300 dark:border-amber-900/60 dark:bg-gradient-to-r dark:from-amber-950/70 dark:via-amber-900/40 dark:to-amber-950/70 dark:text-amber-100"
    style="display: none;"
>
    <div class="mx-auto flex max-w-7xl items-start justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-start gap-3 sm:gap-3.5 min-w-0">
            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 text-amber-800 ring-1 ring-amber-500/30 dark:bg-amber-400/20 dark:text-amber-300 dark:ring-amber-400/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                </svg>
            </div>
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold tracking-wider uppercase text-amber-900 dark:text-amber-200">
                        ACADEMIC CAPSTONE PROJECT NOTICE
                    </span>
                    <span class="inline-flex items-center rounded-full bg-amber-200/90 px-2 py-0.5 text-[10px] font-semibold text-amber-900 ring-1 ring-inset ring-amber-500/20 dark:bg-amber-800/60 dark:text-amber-200">
                        Demonstration &amp; Evaluation
                    </span>
                </div>
                <p class="text-xs sm:text-sm leading-relaxed text-amber-900/95 dark:text-amber-200/95 font-medium">
                    You are visiting the Spa Alexandria Appointment and Workforce Scheduling System, developed as part of an academic capstone project.
                </p>
                <p class="text-xs leading-relaxed text-amber-900/85 dark:text-amber-200/85">
                    This website is intended for demonstration, testing, and academic evaluation purposes only. It is not intended for actual commercial transactions or real customer bookings.
                </p>
                <p class="text-xs font-semibold text-amber-950 dark:text-amber-100">
                    Please do not submit real payment information or unnecessary sensitive personal information.
                </p>
                <p class="text-xs text-amber-900/80 dark:text-amber-300/80">
                    Thank you for your understanding!
                </p>
            </div>
        </div>
        <button
            type="button"
            id="dismissCapstoneNoticeBtn"
            aria-label="Dismiss academic capstone project notice"
            class="shrink-0 rounded-lg p-1.5 text-amber-800 transition hover:bg-amber-200/60 hover:text-amber-950 focus:outline-none focus:ring-2 focus:ring-amber-500/50 dark:text-amber-300 dark:hover:bg-amber-900/50 dark:hover:text-amber-100"
            onclick="dismissCapstoneNotice()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</aside>

<script>
    (function() {
        var storageKey = 'dismiss_capstone_notice';
        var banner = document.getElementById('academicCapstoneNotice');
        if (!banner) return;

        try {
            if (sessionStorage.getItem(storageKey) !== 'true') {
                banner.style.display = 'block';
            }
        } catch (e) {
            banner.style.display = 'block';
        }

        window.dismissCapstoneNotice = function() {
            try {
                sessionStorage.setItem(storageKey, 'true');
            } catch (e) {}
            if (banner) {
                banner.style.display = 'none';
            }
        };
    })();
</script>
