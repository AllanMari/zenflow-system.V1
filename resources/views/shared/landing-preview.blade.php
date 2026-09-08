@php
    $isMobile = $mobile ?? false;
@endphp

<div
    class="
        min-h-full
        bg-white
        text-gray-900
        dark:bg-gray-950
        dark:text-white
    "
>
    {{-- Navigation --}}
    <header
        class="
            sticky top-0 z-30 border-b
            border-gray-100
            bg-white/95
            backdrop-blur-xl
            dark:border-gray-800
            dark:bg-gray-950/95
        "
    >
        <div
            class="
                mx-auto flex items-center justify-between
                {{
                    $isMobile
                        ? 'px-4 py-3'
                        : 'max-w-7xl px-8 py-4'
                }}
            "
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="
                        flex shrink-0 items-center
                        justify-center rounded-xl
                        bg-gradient-to-br
                        from-brand-400
                        to-brand-600
                        text-white
                        {{
                            $isMobile
                                ? 'h-8 w-8'
                                : 'h-10 w-10'
                        }}
                    "
                >
                    <i
                        data-lucide="flower-2"
                        class="
                            {{
                                $isMobile
                                    ? 'h-4 w-4'
                                    : 'h-5 w-5'
                            }}
                        "
                    ></i>
                </div>

                <div>
                    <p
                        class="
                            font-extrabold tracking-tight
                            text-gray-900
                            dark:text-white
                            {{
                                $isMobile
                                    ? 'text-[10px]'
                                    : 'text-sm'
                            }}
                        "
                    >
                        Spa Alexandria
                    </p>

                    <p
                        class="
                            font-semibold uppercase
                            tracking-[0.15em]
                            text-brand-600
                            dark:text-brand-400
                            {{
                                $isMobile
                                    ? 'text-[6px]'
                                    : 'text-[9px]'
                            }}
                        "
                    >
                        Wellness & Relaxation
                    </p>
                </div>
            </div>

            @if ($isMobile)
                <button
                    type="button"
                    class="
                        flex h-8 w-8 items-center
                        justify-center rounded-xl
                        bg-gray-100
                        text-gray-600
                        dark:bg-gray-800
                        dark:text-gray-300
                    "
                >
                    <i
                        data-lucide="menu"
                        class="h-4 w-4"
                    ></i>
                </button>
            @else
                <div class="flex items-center gap-6">
                    <span
                        class="
                            text-xs font-semibold
                            text-gray-600
                            dark:text-gray-300
                        "
                    >
                        Home
                    </span>

                    <span
                        class="
                            text-xs font-semibold
                            text-gray-500
                            dark:text-gray-400
                        "
                    >
                        Services
                    </span>

                    <span
                        class="
                            text-xs font-semibold
                            text-gray-500
                            dark:text-gray-400
                        "
                    >
                        About
                    </span>

                    <button
                        type="button"
                        class="
                            rounded-full
                            bg-brand-600 px-4 py-2
                            text-xs font-bold
                            text-white
                        "
                    >
                        Book Now
                    </button>
                </div>
            @endif
        </div>
    </header>

    {{-- Hero --}}
    <section
        class="
            relative overflow-hidden
            {{
                $isMobile
                    ? 'h-[430px]'
                    : 'h-[500px]'
            }}
        "
    >
        <div
            class="
                relative h-full
                bg-gradient-to-br
                from-brand-950
                via-brand-800
                to-brand-600
            "
        >
            <img
                x-show="heroImage"
                :src="heroImage || ''"
                alt=""
                class="
                    absolute inset-0
                    h-full w-full
                    object-cover
                "
            >

            <div
                class="
                    absolute inset-0
                    bg-gradient-to-br
                    from-black/55
                    via-black/30
                    to-brand-950/80
                "
            ></div>

            <div
                class="
                    relative z-10 mx-auto flex
                    h-full items-end
                    {{
                        $isMobile
                            ? 'px-5 pb-9'
                            : 'max-w-7xl px-8 pb-16'
                    }}
                "
            >
                <div
                    class="
                        text-white
                        {{
                            $isMobile
                                ? 'max-w-[280px]'
                                : 'max-w-2xl'
                        }}
                    "
                >
                    <span
                        class="
                            inline-flex items-center
                            rounded-full
                            bg-white/15
                            px-3 py-1.5
                            font-bold uppercase
                            tracking-[0.16em]
                            text-brand-100
                            backdrop-blur-md
                            {{
                                $isMobile
                                    ? 'text-[7px]'
                                    : 'text-[9px]'
                            }}
                        "
                    >
                        Your space to unwind
                    </span>

                    <h1
                        class="
                            mt-4 font-extrabold
                            leading-[1.05]
                            tracking-tight
                            {{
                                $isMobile
                                    ? 'text-3xl'
                                    : 'text-5xl'
                            }}
                        "
                        x-text="
                            heroTitle ||
                            'Spa Alexandria'
                        "
                    ></h1>

                    <p
                        class="
                            mt-4 max-w-xl
                            text-white/80
                            {{
                                $isMobile
                                    ? 'text-[9px] leading-4'
                                    : 'text-sm leading-6'
                            }}
                        "
                        x-text="
                            heroSubtitle ||
                            'Relax, recharge, and experience personalized wellness.'
                        "
                    ></p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="
                                rounded-full
                                bg-white
                                font-bold
                                text-brand-800
                                {{
                                    $isMobile
                                        ? 'px-4 py-2 text-[8px]'
                                        : 'px-5 py-2.5 text-xs'
                                }}
                            "
                        >
                            Book an appointment
                        </button>

                        <button
                            type="button"
                            class="
                                rounded-full
                                border border-white/30
                                bg-white/10
                                font-bold
                                text-white
                                backdrop-blur-sm
                                {{
                                    $isMobile
                                        ? 'px-4 py-2 text-[8px]'
                                        : 'px-5 py-2.5 text-xs'
                                }}
                            "
                        >
                            Explore services
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Services Introduction --}}
    <section
        class="
            bg-white
            dark:bg-gray-950
            {{
                $isMobile
                    ? 'px-5 py-8'
                    : 'px-8 py-14'
            }}
        "
    >
        <div
            class="
                mx-auto
                {{
                    $isMobile
                        ? 'max-w-full text-center'
                        : 'max-w-3xl text-center'
                }}
            "
        >
            <p
                class="
                    font-bold uppercase
                    tracking-[0.18em]
                    text-brand-600
                    dark:text-brand-400
                    {{
                        $isMobile
                            ? 'text-[7px]'
                            : 'text-[9px]'
                    }}
                "
            >
                Our services
            </p>

            <h2
                class="
                    mt-2 font-extrabold
                    tracking-tight
                    text-gray-900
                    dark:text-white
                    {{
                        $isMobile
                            ? 'text-lg'
                            : 'text-3xl'
                    }}
                "
            >
                Treatments made for your wellness
            </h2>

            <p
                class="
                    mx-auto mt-2 max-w-2xl
                    text-gray-500
                    dark:text-gray-400
                    {{
                        $isMobile
                            ? 'text-[8px] leading-4'
                            : 'text-sm leading-6'
                    }}
                "
            >
                Discover relaxing treatments and personalized
                experiences designed to help you feel your best.
            </p>
        </div>
    </section>

    {{-- Services --}}
    <section
        class="
            bg-gray-50
            dark:bg-gray-900
            {{
                $isMobile
                    ? 'px-5 py-8'
                    : 'px-8 py-14'
            }}
        "
    >
        <div
            class="
                mx-auto
                {{
                    $isMobile
                        ? 'max-w-full'
                        : 'max-w-7xl'
                }}
            "
        >
            <template
                x-for="category in categories"
                :key="'preview-category-' + category.id"
            >
                <div
                    x-show="
                        category.show &&
                        visibleServices(category).length > 0
                    "
                    class="mb-8 last:mb-0"
                >
                    <div
                        class="
                            flex items-center justify-between
                            {{
                                $isMobile
                                    ? 'mb-3'
                                    : 'mb-5'
                            }}
                        "
                    >
                        <div
                            class="flex items-center gap-2"
                        >
                            <span
                                class="
                                    rounded-full
                                    bg-brand-500
                                    {{
                                        $isMobile
                                            ? 'h-1.5 w-1.5'
                                            : 'h-2.5 w-2.5'
                                    }}
                                "
                            ></span>

                            <h3
                                class="
                                    font-extrabold
                                    text-gray-900
                                    dark:text-white
                                    {{
                                        $isMobile
                                            ? 'text-[10px]'
                                            : 'text-lg'
                                    }}
                                "
                                x-text="category.name"
                            ></h3>
                        </div>

                        <span
                            class="
                                font-semibold
                                text-gray-400
                                dark:text-gray-500
                                {{
                                    $isMobile
                                        ? 'text-[7px]'
                                        : 'text-[10px]'
                                }}
                            "
                            x-text="
                                visibleServices(category).length +
                                ' services'
                            "
                        ></span>
                    </div>

                    <div
                        class="
                            grid
                            {{
                                $isMobile
                                    ? 'grid-cols-1 gap-2.5'
                                    : 'grid-cols-3 gap-5'
                            }}
                        "
                    >
                        <template
                            x-for="
                                service in visibleServices(
                                    category
                                )
                            "
                            :key="
                                'preview-service-' +
                                service.id
                            "
                        >
                            <article
                                class="
                                    overflow-hidden rounded-2xl
                                    bg-white shadow-sm
                                    ring-1 ring-gray-100
                                    dark:bg-gray-800
                                    dark:ring-gray-700
                                "
                            >
                                <div
                                    class="
                                        {{
                                            $isMobile
                                                ? 'flex gap-3 p-2.5'
                                                : ''
                                        }}
                                    "
                                >
                                    {{-- Service Preview Image --}}
                                    <div
                                        class="
                                            overflow-hidden
                                            bg-gray-100
                                            dark:bg-gray-700
                                            {{
                                                $isMobile
                                                    ? 'h-16 w-16 shrink-0 rounded-xl'
                                                    : 'aspect-[4/3] w-full'
                                            }}
                                        "
                                    >
                                        <img
                                            :data-service-image-preview="
                                                'service-' +
                                                service.id
                                            "
                                            :src="
                                                service.image || ''
                                            "
                                            :alt="
                                                service.name || ''
                                            "
                                            class="
                                                h-full w-full
                                                object-cover
                                            "
                                            :style="
                                                service.image
                                                    ? 'display:block;'
                                                    : 'display:none;'
                                            "
                                        >

                                        <div
                                            :data-service-image-placeholder="
                                                'service-' +
                                                service.id
                                            "
                                            class="
                                                flex h-full w-full
                                                items-center
                                                justify-center
                                                text-gray-300
                                                dark:text-gray-600
                                            "
                                            :style="
                                                service.image
                                                    ? 'display:none;'
                                                    : 'display:flex;'
                                            "
                                        >
                                            <i
                                                data-lucide="image"
                                                class="
                                                    {{
                                                        $isMobile
                                                            ? 'h-5 w-5'
                                                            : 'h-8 w-8'
                                                    }}
                                                "
                                            ></i>
                                        </div>
                                    </div>

                                    <div
                                        class="
                                            min-w-0 flex-1
                                            {{
                                                $isMobile
                                                    ? 'py-0.5 pr-1'
                                                    : 'p-5'
                                            }}
                                        "
                                    >
                                        <div
                                            class="
                                                flex items-start
                                                justify-between gap-2
                                            "
                                        >
                                            <h4
                                                class="
                                                    min-w-0 truncate
                                                    font-extrabold
                                                    text-gray-900
                                                    dark:text-white
                                                    {{
                                                        $isMobile
                                                            ? 'text-[9px]'
                                                            : 'text-sm'
                                                    }}
                                                "
                                                x-text="service.name"
                                            ></h4>

                                            <span
                                                class="
                                                    shrink-0
                                                    font-extrabold
                                                    text-brand-600
                                                    dark:text-brand-400
                                                    {{
                                                        $isMobile
                                                            ? 'text-[8px]'
                                                            : 'text-sm'
                                                    }}
                                                "
                                                x-text="
                                                    '₱' +
                                                    service.price
                                                "
                                            ></span>
                                        </div>

                                        <p
                                            class="
                                                mt-1.5
                                                line-clamp-3
                                                text-gray-500
                                                dark:text-gray-400
                                                {{
                                                    $isMobile
                                                        ? 'text-[7px] leading-3'
                                                        : 'text-xs leading-5'
                                                }}
                                            "
                                            x-text="
                                                service.description ||
                                                'A relaxing treatment designed for your wellness.'
                                            "
                                        ></p>

                                        <button
                                            type="button"
                                            class="
                                                mt-2 font-bold
                                                text-brand-600
                                                dark:text-brand-400
                                                {{
                                                    $isMobile
                                                        ? 'text-[7px]'
                                                        : 'text-xs'
                                                }}
                                            "
                                        >
                                            Learn more
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <div
                x-show="
                    visibleCategoryCount() === 0
                "
                class="
                    rounded-2xl border
                    border-dashed border-gray-200
                    bg-white text-center
                    dark:border-gray-700
                    dark:bg-gray-800
                    {{
                        $isMobile
                            ? 'p-8'
                            : 'p-12'
                    }}
                "
            >
                <i
                    data-lucide="eye-off"
                    class="
                        mx-auto
                        text-gray-300
                        dark:text-gray-600
                        {{
                            $isMobile
                                ? 'h-5 w-5'
                                : 'h-7 w-7'
                        }}
                    "
                ></i>

                <p
                    class="
                        mt-3 font-bold
                        text-gray-500
                        dark:text-gray-400
                        {{
                            $isMobile
                                ? 'text-[9px]'
                                : 'text-sm'
                        }}
                    "
                >
                    No services are visible.
                </p>

                <p
                    class="
                        mt-1 text-gray-400
                        dark:text-gray-500
                        {{
                            $isMobile
                                ? 'text-[7px]'
                                : 'text-xs'
                        }}
                    "
                >
                    Enable categories and services in the editor.
                </p>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section
        class="
            bg-white
            dark:bg-gray-950
            {{
                $isMobile
                    ? 'px-5 py-10'
                    : 'px-8 py-16'
            }}
        "
    >
        <div
            class="
                mx-auto rounded-3xl
                bg-gradient-to-br
                from-brand-800
                to-brand-950
                text-center text-white
                {{
                    $isMobile
                        ? 'px-5 py-8'
                        : 'max-w-6xl px-12 py-14'
                }}
            "
        >
            <p
                class="
                    font-bold uppercase
                    tracking-[0.18em]
                    text-brand-200
                    {{
                        $isMobile
                            ? 'text-[7px]'
                            : 'text-[9px]'
                    }}
                "
            >
                Ready to relax?
            </p>

            <h2
                class="
                    mt-2 font-extrabold
                    {{
                        $isMobile
                            ? 'text-xl'
                            : 'text-3xl'
                    }}
                "
            >
                Your wellness experience starts here.
            </h2>

            <p
                class="
                    mx-auto mt-2 max-w-xl
                    text-white/65
                    {{
                        $isMobile
                            ? 'text-[8px] leading-4'
                            : 'text-sm leading-6'
                    }}
                "
            >
                Book your preferred treatment and give yourself
                the time you deserve.
            </p>

            <button
                type="button"
                class="
                    mt-5 rounded-full
                    bg-white font-bold
                    text-brand-800
                    {{
                        $isMobile
                            ? 'px-4 py-2 text-[8px]'
                            : 'px-5 py-3 text-xs'
                    }}
                "
            >
                Book an appointment
            </button>
        </div>
    </section>

    {{-- Footer --}}
    <footer
        class="
            bg-gray-950 text-white
            {{
                $isMobile
                    ? 'px-5 py-8'
                    : 'px-8 py-10'
            }}
        "
    >
        <div
            class="
                mx-auto
                {{
                    $isMobile
                        ? 'max-w-full'
                        : 'max-w-7xl'
                }}
            "
        >
            <p
                class="
                    font-extrabold
                    {{
                        $isMobile
                            ? 'text-[10px]'
                            : 'text-sm'
                    }}
                "
            >
                Spa Alexandria
            </p>

            <p
                class="
                    mt-1 text-white/40
                    {{
                        $isMobile
                            ? 'text-[7px]'
                            : 'text-xs'
                    }}
                "
            >
                Your space to relax, restore, and reconnect.
            </p>
        </div>
    </footer>
</div>