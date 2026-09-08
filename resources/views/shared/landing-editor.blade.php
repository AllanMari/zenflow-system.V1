@extends(
    auth()->user()->roles->contains('name', 'admin')
        ? 'layouts.admin'
        : 'layouts.receptionist'
)

@section('title', 'Landing Page Editor')

@php
    $resolveImage = function ($path) {
        if (!$path) {
            return null;
        }

        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return asset(ltrim($path, '/'));
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return \Illuminate\Support\Facades\Storage::url($path);
    };

    $heroImageUrl = $resolveImage(
        $heroSettings['hero_image'] ?? null
    );

    $previewData = $categories
        ->map(function ($category) use ($resolveImage) {
            $categoryShow = old(
                'categories.' . $category->id . '.show',
                $category->show_on_landing
            );

            return [
                'id' => $category->id,
                'name' => $category->name,
                'show' => (bool) $categoryShow,

                'services' => $category->services
                    ->map(function ($service) use ($resolveImage) {
                        $serviceShow = old(
                            'services.' .
                            $service->id .
                            '.show',
                            $service->show_on_landing
                        );

                        $serviceDescription = old(
                            'services.' .
                            $service->id .
                            '.landing_description',
                            $service->landing_description ?? ''
                        );

                        $serviceImage = $resolveImage(
                            $service->image
                        );

                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'price' => number_format(
                                $service->price,
                                2
                            ),
                            'show' => (bool) $serviceShow,
                            'description' => $serviceDescription,
                            'image' => $serviceImage,
                            'originalImage' => $serviceImage,
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        })
        ->values()
        ->all();
@endphp

@push('styles')
<style>
    [x-cloak] {
        display: none !important;
    }

    .editor-card {
        box-shadow:
            0 8px 24px rgba(15, 23, 42, 0.04),
            0 2px 8px rgba(15, 23, 42, 0.025);
    }

    .dark .editor-card {
        box-shadow:
            0 10px 30px rgba(0, 0, 0, 0.16),
            0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .editor-input {
        transition:
            border-color 0.2s ease,
            box-shadow 0.2s ease,
            background-color 0.2s ease;
    }

    .editor-input:focus {
        box-shadow:
            0 0 0 3px rgba(20, 184, 166, 0.1);
    }

    .preview-stage {
        background:
            radial-gradient(
                circle at 50% 0,
                rgba(20, 184, 166, 0.08),
                transparent 22rem
            ),
            #f1f5f9;
    }

    .dark .preview-stage {
        background:
            radial-gradient(
                circle at 50% 0,
                rgba(20, 184, 166, 0.07),
                transparent 22rem
            ),
            #0f172a;
    }

    .preview-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .dark .preview-scroll {
        scrollbar-color: #475569 transparent;
    }

    .device-shell {
        transition:
            width 0.25s ease,
            height 0.25s ease,
            border-radius 0.25s ease;
    }

    .device-mobile {
        width: 375px;
        height: 720px;
    }

    .device-desktop {
        width: 900px;
        height: 600px;
    }

    @media (max-width: 640px) {
        .device-mobile {
            width: 100%;
            max-width: 375px;
            height: 680px;
        }
    }
</style>
@endpush

@section('content')
<div
    x-data="landingEditor(
        @js($previewData),
        @js($heroImageUrl),
        @js(old('hero_title', $heroSettings['hero_title'] ?? '')),
        @js(old('hero_subtitle', $heroSettings['hero_subtitle'] ?? ''))
    )"
    x-init="init()"
    class="mx-auto max-w-7xl"
>
    <form
        id="landingEditorForm"
        method="POST"
        action="{{ route('admin.landing.update') }}"
        enctype="multipart/form-data"
        @submit="submitting = true"
        @input="markDirty()"
        @change="markDirty()"
    >
        @csrf

        {{-- Validation --}}
        @if ($errors->any())
            <div
                class="
                    mb-5 rounded-2xl border border-red-200
                    bg-red-50 p-4
                    dark:border-red-900/40
                    dark:bg-red-950/20
                "
            >
                <div class="flex items-start gap-3">
                    <div
                        class="
                            flex h-9 w-9 shrink-0
                            items-center justify-center
                            rounded-xl bg-red-100
                            text-red-600
                            dark:bg-red-900/30
                            dark:text-red-400
                        "
                    >
                        <i
                            data-lucide="triangle-alert"
                            class="h-4 w-4"
                        ></i>
                    </div>

                    <div>
                        <p
                            class="
                                text-sm font-bold text-red-800
                                dark:text-red-300
                            "
                        >
                            Please correct the following errors.
                        </p>

                        <ul
                            class="
                                mt-2 space-y-1 text-xs
                                leading-5 text-red-700
                                dark:text-red-400
                            "
                        >
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Hero --}}
        <section
            class="
                editor-card mb-5 overflow-hidden rounded-3xl
                border border-gray-200/80 bg-white
                dark:border-gray-800/70
                dark:bg-[#111827]
            "
        >
            <div
                class="
                    border-b border-gray-100 p-5
                    dark:border-gray-800 sm:p-6
                "
            >
                <div class="flex items-start gap-3">
                    <div
                        class="
                            flex h-9 w-9 shrink-0
                            items-center justify-center
                            rounded-xl bg-brand-50
                            text-brand-600
                            dark:bg-brand-900/30
                            dark:text-brand-400
                        "
                    >
                        <i
                            data-lucide="sparkles"
                            class="h-4 w-4"
                        ></i>
                    </div>

                    <div>
                        <h2
                            class="
                                text-base font-extrabold
                                text-gray-900
                                dark:text-white
                            "
                        >
                            Hero Section
                        </h2>

                        <p
                            class="
                                mt-1 max-w-2xl text-xs
                                leading-5 text-gray-500
                                dark:text-gray-400
                            "
                        >
                            Edit the main headline, supporting text,
                            and hero image.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:p-6 xl:grid-cols-2">
                <div class="space-y-5">
                    {{-- Hero Title --}}
                    <div>
                        <label
                            for="hero_title"
                            class="
                                mb-2 block text-[11px]
                                font-bold uppercase
                                tracking-wider
                                text-gray-500
                                dark:text-gray-400
                            "
                        >
                            Hero title
                        </label>

                        <input
                            id="hero_title"
                            name="hero_title"
                            type="text"
                            maxlength="255"
                            required
                            x-model="heroTitle"
                            class="
                                editor-input w-full rounded-2xl
                                border border-gray-200
                                bg-gray-50 px-4 py-3.5
                                text-sm font-semibold
                                text-gray-900 outline-none
                                placeholder:text-gray-400
                                focus:border-brand-500
                                dark:border-gray-700
                                dark:bg-gray-900/60
                                dark:text-white
                            "
                            placeholder="Welcome to Spa Alexandria"
                        >
                    </div>

                    {{-- Hero Subtitle --}}
                    <div>
                        <div
                            class="
                                mb-2 flex items-center
                                justify-between
                            "
                        >
                            <label
                                for="hero_subtitle"
                                class="
                                    text-[11px] font-bold
                                    uppercase tracking-wider
                                    text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Hero subtitle
                            </label>

                            <span
                                class="
                                    text-[10px] font-semibold
                                    text-gray-400
                                    dark:text-gray-500
                                "
                                x-text="heroSubtitle.length + '/500'"
                            ></span>
                        </div>

                        <textarea
                            id="hero_subtitle"
                            name="hero_subtitle"
                            rows="5"
                            maxlength="500"
                            x-model="heroSubtitle"
                            class="
                                editor-input w-full resize-y
                                rounded-2xl border
                                border-gray-200 bg-gray-50
                                px-4 py-3.5 text-sm leading-6
                                text-gray-900 outline-none
                                placeholder:text-gray-400
                                focus:border-brand-500
                                dark:border-gray-700
                                dark:bg-gray-900/60
                                dark:text-white
                            "
                            placeholder="Relax, recharge, and experience personalized wellness."
                        ></textarea>
                    </div>

                    {{-- Hero Image --}}
                    <div>
                        <input
                            type="hidden"
                            name="remove_hero_image"
                            :value="removeHeroImage ? '1' : '0'"
                        >

                        <label
                            for="hero_image"
                            class="
                                mb-2 block text-[11px]
                                font-bold uppercase
                                tracking-wider
                                text-gray-500
                                dark:text-gray-400
                            "
                        >
                            Hero image
                        </label>

                        <div
                            class="
                                rounded-2xl border-2
                                border-dashed border-gray-200
                                bg-gray-50/60 p-4
                                dark:border-gray-700
                                dark:bg-gray-900/40
                            "
                        >
                            <input
                                id="hero_image"
                                type="file"
                                name="hero_image"
                                accept="image/*"
                                class="hidden"
                                @change.stop="selectHeroImage($event)"
                            >

                            <label
                                for="hero_image"
                                class="
                                    flex cursor-pointer
                                    flex-col items-center
                                    justify-center rounded-xl
                                    px-4 py-7 text-center
                                    hover:bg-white
                                    dark:hover:bg-gray-800/60
                                "
                            >
                                <span
                                    class="
                                        flex h-11 w-11
                                        items-center justify-center
                                        rounded-2xl
                                        bg-brand-100
                                        text-brand-600
                                        dark:bg-brand-900/30
                                        dark:text-brand-400
                                    "
                                >
                                    <i
                                        data-lucide="image-plus"
                                        class="h-5 w-5"
                                    ></i>
                                </span>

                                <span
                                    class="
                                        mt-3 text-sm font-bold
                                        text-gray-800
                                        dark:text-gray-200
                                    "
                                >
                                    Choose image
                                </span>

                                <span
                                    class="
                                        mt-1 max-w-sm text-xs
                                        leading-5 text-gray-400
                                        dark:text-gray-500
                                    "
                                >
                                    Select an image for the main
                                    landing-page hero.
                                </span>
                            </label>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="removeHero()"
                                class="
                                    inline-flex items-center gap-2
                                    rounded-xl px-3 py-2
                                    text-xs font-bold
                                    text-red-600
                                    hover:bg-red-50
                                    dark:text-red-400
                                    dark:hover:bg-red-950/20
                                "
                            >
                                <i
                                    data-lucide="trash-2"
                                    class="h-3.5 w-3.5"
                                ></i>

                                Remove image
                            </button>

                            <span
                                class="
                                    inline-flex items-center
                                    rounded-xl bg-gray-100
                                    px-3 py-2 text-[10px]
                                    font-semibold text-gray-500
                                    dark:bg-gray-800
                                    dark:text-gray-400
                                "
                            >
                                Images up to 2 MB
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Live Preview --}}
                <div class="xl:self-start">
                    <div
                        class="
                            flex flex-col gap-3
                            sm:flex-row sm:items-center
                            sm:justify-between
                        "
                    >
                        <div>
                            <p
                                class="
                                    text-[11px] font-bold
                                    uppercase tracking-wider
                                    text-gray-500
                                    dark:text-gray-400
                                "
                            >
                                Live preview
                            </p>

                            <p
                                class="
                                    mt-0.5 text-[11px]
                                    text-gray-400
                                    dark:text-gray-500
                                "
                            >
                                See how the landing page changes.
                            </p>
                        </div>

                        <div
                            class="
                                inline-flex w-full rounded-xl
                                border border-gray-200
                                bg-gray-50 p-1
                                dark:border-gray-700
                                dark:bg-gray-900/60
                                sm:w-auto
                            "
                        >
                            <button
                                type="button"
                                @click="previewMode = 'mobile'"
                                class="
                                    inline-flex flex-1
                                    items-center justify-center
                                    gap-1.5 rounded-lg px-3 py-2
                                    text-[11px] font-bold
                                    transition sm:flex-none
                                "
                                :class="
                                    previewMode === 'mobile'
                                        ? 'bg-white text-brand-700 shadow-sm dark:bg-gray-800 dark:text-brand-400'
                                        : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300'
                                "
                            >
                                <i
                                    data-lucide="smartphone"
                                    class="h-3.5 w-3.5"
                                ></i>

                                Mobile
                            </button>

                            <button
                                type="button"
                                @click="previewMode = 'desktop'"
                                class="
                                    inline-flex flex-1
                                    items-center justify-center
                                    gap-1.5 rounded-lg px-3 py-2
                                    text-[11px] font-bold
                                    transition sm:flex-none
                                "
                                :class="
                                    previewMode === 'desktop'
                                        ? 'bg-white text-brand-700 shadow-sm dark:bg-gray-800 dark:text-brand-400'
                                        : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300'
                                "
                            >
                                <i
                                    data-lucide="monitor"
                                    class="h-3.5 w-3.5"
                                ></i>

                                Desktop
                            </button>
                        </div>
                    </div>

                    <div
                        class="
                            preview-stage mt-4 overflow-x-auto
                            rounded-3xl p-4 sm:p-6
                        "
                    >
                        <div
                            class="
                                flex min-w-full justify-center
                            "
                        >
                            {{-- Mobile --}}
                            <div
                                x-show="previewMode === 'mobile'"
                                x-cloak
                                class="
                                    device-shell device-mobile
                                    overflow-hidden
                                    rounded-[2rem]
                                    border-[8px]
                                    border-gray-900
                                    bg-black shadow-2xl
                                    dark:border-gray-700
                                "
                            >
                                <div
                                    class="
                                        h-full overflow-hidden
                                        bg-white
                                        dark:bg-gray-950
                                    "
                                >
                                    @include(
                                        'shared.landing-preview',
                                        ['mobile' => true]
                                    )
                                </div>
                            </div>

                            {{-- Desktop --}}
                            <div
                                x-show="previewMode === 'desktop'"
                                x-cloak
                                class="
                                    device-shell device-desktop
                                    overflow-hidden rounded-2xl
                                    border border-gray-300
                                    bg-white shadow-2xl
                                    dark:border-gray-700
                                    dark:bg-gray-950
                                "
                            >
                                <div
                                    class="
                                        flex h-9 items-center
                                        gap-1.5 border-b
                                        border-gray-200
                                        bg-gray-100 px-3
                                        dark:border-gray-700
                                        dark:bg-gray-800
                                    "
                                >
                                    <span
                                        class="
                                            h-2.5 w-2.5 rounded-full
                                            bg-red-400
                                        "
                                    ></span>

                                    <span
                                        class="
                                            h-2.5 w-2.5 rounded-full
                                            bg-yellow-400
                                        "
                                    ></span>

                                    <span
                                        class="
                                            h-2.5 w-2.5 rounded-full
                                            bg-green-400
                                        "
                                    ></span>

                                    <div
                                        class="
                                            ml-3 flex h-5 flex-1
                                            items-center rounded-md
                                            bg-white px-3
                                            text-[8px] text-gray-400
                                            dark:bg-gray-900
                                        "
                                    >
                                        spaalexandria.com
                                    </div>
                                </div>

                                <div
                                    class="
                                        preview-scroll h-[560px]
                                        overflow-y-auto
                                    "
                                >
                                    @include(
                                        'shared.landing-preview',
                                        ['mobile' => false]
                                    )
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Services --}}
        <section
            class="
                editor-card mb-5 overflow-hidden rounded-3xl
                border border-gray-200/80 bg-white
                dark:border-gray-800/70
                dark:bg-[#111827]
            "
        >
            <div
                class="
                    border-b border-gray-100 p-5
                    dark:border-gray-800 sm:p-6
                "
            >
                <div class="flex items-start gap-3">
                    <div
                        class="
                            flex h-9 w-9 shrink-0
                            items-center justify-center
                            rounded-xl bg-blue-50
                            text-blue-600
                            dark:bg-blue-900/30
                            dark:text-blue-400
                        "
                    >
                        <i
                            data-lucide="layers-3"
                            class="h-4 w-4"
                        ></i>
                    </div>

                    <div>
                        <h2
                            class="
                                text-base font-extrabold
                                text-gray-900
                                dark:text-white
                            "
                        >
                            Services Showcase
                        </h2>

                        <p
                            class="
                                mt-1 max-w-2xl text-xs
                                leading-5 text-gray-500
                                dark:text-gray-400
                            "
                        >
                            Select what customers can see and edit
                            the description used on the landing page.
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-6">
                @forelse ($categories as $category)
                    <div
                        class="
                            hs-accordion mb-3 overflow-hidden
                            rounded-2xl border
                            border-gray-200/80 last:mb-0
                            dark:border-gray-700/70
                        "
                        id="landing-category-{{ $category->id }}"
                    >
                        <div
                            class="
                                flex items-center gap-3
                                bg-gray-50/70 px-3 py-3.5
                                dark:bg-gray-900/50
                            "
                        >
                            <input
                                type="hidden"
                                name="categories[{{ $category->id }}][show]"
                                value="0"
                            >

                            <input
                                id="category-show-{{ $category->id }}"
                                type="checkbox"
                                name="categories[{{ $category->id }}][show]"
                                value="1"
                                @checked(
                                    old(
                                        'categories.' .
                                        $category->id .
                                        '.show',
                                        $category->show_on_landing
                                    )
                                )
                                @change="
                                    setCategoryVisibility(
                                        {{ $category->id }},
                                        $event.target.checked
                                    )
                                "
                                class="
                                    h-4 w-4 shrink-0 rounded
                                    border-gray-300
                                    text-brand-600
                                    focus:ring-brand-500
                                    dark:border-gray-600
                                    dark:bg-gray-800
                                "
                            >

                            <span
                                class="
                                    h-2.5 w-2.5 shrink-0
                                    rounded-full bg-brand-500
                                "
                            ></span>

                            <div class="min-w-0 flex-1">
                                <label
                                    for="category-show-{{ $category->id }}"
                                    class="
                                        block truncate
                                        text-sm font-extrabold
                                        text-gray-800
                                        dark:text-gray-200
                                    "
                                >
                                    {{ $category->name }}
                                </label>

                                <p
                                    class="
                                        text-[11px] text-gray-400
                                        dark:text-gray-500
                                    "
                                >
                                    {{ $category->services->count() }}
                                    {{
                                        Str::plural(
                                            'service',
                                            $category->services->count()
                                        )
                                    }}
                                </p>
                            </div>

                            <span
                                class="
                                    hidden text-[10px] font-bold
                                    sm:block
                                "
                                :class="
                                    categoryById(
                                        {{ $category->id }}
                                    ).show
                                        ? 'text-brand-600 dark:text-brand-400'
                                        : 'text-gray-400 dark:text-gray-500'
                                "
                                x-text="
                                    categoryById(
                                        {{ $category->id }}
                                    ).show
                                        ? 'Visible'
                                        : 'Hidden'
                                "
                            ></span>

                            <button
                                type="button"
                                class="
                                    hs-accordion-toggle flex h-9 w-9
                                    shrink-0 items-center
                                    justify-center rounded-xl
                                    text-gray-400
                                    hover:bg-white
                                    hover:text-gray-700
                                    dark:hover:bg-gray-800
                                    dark:hover:text-gray-200
                                "
                                aria-controls="landing-category-content-{{ $category->id }}"
                            >
                                <i
                                    data-lucide="chevron-down"
                                    class="
                                        h-4 w-4 transition-transform
                                        hs-accordion-active:rotate-180
                                    "
                                ></i>
                            </button>
                        </div>

                        <div
                            id="landing-category-content-{{ $category->id }}"
                            class="
                                hs-accordion-content hidden w-full
                            "
                        >
                            <div
                                class="
                                    space-y-3 border-t
                                    border-gray-100 p-3
                                    dark:border-gray-800
                                    sm:p-4
                                "
                            >
                                @forelse ($category->services as $service)
                                    @php
                                        $serviceImage =
                                            $resolveImage(
                                                $service->image
                                            );

                                        $serviceShow = old(
                                            'services.' .
                                            $service->id .
                                            '.show',
                                            $service->show_on_landing
                                        );

                                        $serviceDescription = old(
                                            'services.' .
                                            $service->id .
                                            '.landing_description',
                                            $service->landing_description
                                        );

                                        $serviceRemoveImage = old(
                                            'services.' .
                                            $service->id .
                                            '.remove_image',
                                            false
                                        );
                                    @endphp

                                    <article
                                        class="
                                            rounded-2xl border
                                            border-gray-200/80
                                            bg-white p-3
                                            dark:border-gray-700/70
                                            dark:bg-gray-900/30
                                            sm:p-4
                                        "
                                    >
                                        <input
                                            type="hidden"
                                            name="services[{{ $service->id }}][show]"
                                            value="0"
                                        >

                                        <input
                                            type="hidden"
                                            name="services[{{ $service->id }}][remove_image]"
                                            value="0"
                                        >

                                        <div
                                            class="
                                                flex flex-col gap-4
                                                lg:flex-row
                                            "
                                        >
                                            {{-- Image --}}
                                            <div
                                                class="
                                                    w-full shrink-0
                                                    lg:w-32
                                                "
                                            >
                                                <div
                                                    class="
                                                        relative h-32
                                                        w-full overflow-hidden
                                                        rounded-2xl
                                                        bg-gray-100
                                                        dark:bg-gray-800
                                                    "
                                                >
                                                    <img
                                                        id="service-preview-{{ $service->id }}"
                                                        src="{{ $serviceImage ?? '' }}"
                                                        alt="{{ $service->name }}"
                                                        class="
                                                            h-full w-full
                                                            object-cover
                                                        "
                                                        style="
                                                            {{
                                                                $serviceImage
                                                                    ? ''
                                                                    : 'display:none;'
                                                            }}
                                                        "
                                                    >

                                                    <div
                                                        id="service-placeholder-{{ $service->id }}"
                                                        class="
                                                            flex h-full w-full
                                                            flex-col
                                                            items-center
                                                            justify-center
                                                            text-center
                                                        "
                                                        style="
                                                            {{
                                                                $serviceImage
                                                                    ? 'display:none;'
                                                                    : ''
                                                            }}
                                                        "
                                                    >
                                                        <i
                                                            data-lucide="image-off"
                                                            class="
                                                                h-6 w-6
                                                                text-gray-300
                                                                dark:text-gray-600
                                                            "
                                                        ></i>

                                                        <span
                                                            class="
                                                                mt-1 text-[10px]
                                                                font-semibold
                                                                text-gray-400
                                                                dark:text-gray-500
                                                            "
                                                        >
                                                            No image
                                                        </span>
                                                    </div>

                                                    <div
                                                        id="service-new-badge-{{ $service->id }}"
                                                        class="
                                                            hidden absolute
                                                            left-2 top-2
                                                            rounded-full
                                                            bg-brand-600
                                                            px-2 py-1
                                                            text-[9px]
                                                            font-bold text-white
                                                        "
                                                    >
                                                        New
                                                    </div>
                                                </div>

                                                <input
                                                    id="service-image-{{ $service->id }}"
                                                    type="file"
                                                    name="service_images[{{ $service->id }}]"
                                                    accept="image/*"
                                                    class="hidden"
                                                    data-service-id="{{ $service->id }}"
                                                    @change.stop="
                                                        selectServiceImage(
                                                            {{ $service->id }},
                                                            $event
                                                        )
                                                    "
                                                >

                                                <button
                                                    type="button"
                                                    @click="
                                                        const scrollYBefore = window.scrollY;
                                                        const input = document.getElementById('service-image-{{ $service->id }}');

                                                        if (input) {
                                                            input.click();
                                                        }

                                                        setTimeout(() => {
                                                            window.scrollTo({
                                                                top: scrollYBefore,
                                                                behavior: 'instant'
                                                            });
                                                        }, 150);
                                                    "
                                                    class="
                                                        mt-2 flex w-full
                                                        cursor-pointer
                                                        items-center
                                                        justify-center gap-2
                                                        rounded-xl border
                                                        border-gray-200
                                                        bg-gray-50 px-3 py-2
                                                        text-[11px] font-bold
                                                        text-gray-600
                                                        dark:border-gray-700
                                                        dark:bg-gray-800/60
                                                        dark:text-gray-300
                                                    "
                                                >
                                                    <i
                                                        data-lucide="upload"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Change image
                                                </button>

                                                @if ($serviceImage)
                                                    <label
                                                        for="remove-service-image-{{ $service->id }}"
                                                        class="
                                                            mt-2 flex cursor-pointer
                                                            items-center gap-2
                                                            text-[11px]
                                                            font-semibold
                                                            text-gray-500
                                                            dark:text-gray-400
                                                        "
                                                    >
                                                        <input
                                                            id="remove-service-image-{{ $service->id }}"
                                                            type="checkbox"
                                                            name="services[{{ $service->id }}][remove_image]"
                                                            value="1"
                                                            @checked(
                                                                $serviceRemoveImage
                                                            )
                                                            @change="
                                                                toggleRemoveServiceImage(
                                                                    {{ $service->id }},
                                                                    $event.target.checked
                                                                )
                                                            "
                                                            class="
                                                                h-3.5 w-3.5
                                                                rounded
                                                                border-gray-300
                                                                text-red-500
                                                                focus:ring-red-500
                                                                dark:border-gray-600
                                                                dark:bg-gray-800
                                                            "
                                                        >

                                                        Remove image
                                                    </label>
                                                @endif
                                            </div>

                                            {{-- Details --}}
                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="
                                                        flex flex-col gap-3
                                                        sm:flex-row
                                                        sm:items-start
                                                        sm:justify-between
                                                    "
                                                >
                                                    <div class="min-w-0">
                                                        <label
                                                            for="service-show-{{ $service->id }}"
                                                            class="
                                                                flex cursor-pointer
                                                                items-center gap-2
                                                            "
                                                        >
                                                            <input
                                                                id="service-show-{{ $service->id }}"
                                                                type="checkbox"
                                                                name="services[{{ $service->id }}][show]"
                                                                value="1"
                                                                @checked(
                                                                    $serviceShow
                                                                )
                                                                @change="
                                                                    setServiceVisibility(
                                                                        {{ $service->id }},
                                                                        $event.target.checked
                                                                    )
                                                                "
                                                                class="
                                                                    h-4 w-4 shrink-0
                                                                    rounded
                                                                    border-gray-300
                                                                    text-brand-600
                                                                    focus:ring-brand-500
                                                                    dark:border-gray-600
                                                                    dark:bg-gray-800
                                                                "
                                                            >

                                                            <span
                                                                class="
                                                                    truncate
                                                                    text-sm
                                                                    font-extrabold
                                                                    text-gray-900
                                                                    dark:text-white
                                                                "
                                                            >
                                                                {{ $service->name }}
                                                            </span>
                                                        </label>

                                                        <div
                                                            class="
                                                                mt-1 flex
                                                                items-center gap-2
                                                            "
                                                        >
                                                            <span
                                                                class="
                                                                    text-xs font-extrabold
                                                                    text-brand-600
                                                                    dark:text-brand-400
                                                                "
                                                            >
                                                                ₱{{ number_format($service->price, 2) }}
                                                            </span>

                                                            <span
                                                                class="
                                                                    text-gray-300
                                                                    dark:text-gray-700
                                                                "
                                                            >
                                                                •
                                                            </span>

                                                            <span
                                                                class="
                                                                    text-[11px]
                                                                    text-gray-400
                                                                    dark:text-gray-500
                                                                "
                                                            >
                                                                {{ $category->name }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <span
                                                        class="
                                                            w-fit rounded-full
                                                            px-2.5 py-1
                                                            text-[10px]
                                                            font-bold
                                                        "
                                                        :class="
                                                            serviceById(
                                                                {{ $service->id }}
                                                            ).show
                                                                ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400'
                                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500'
                                                        "
                                                        x-text="
                                                            serviceById(
                                                                {{ $service->id }}
                                                            ).show
                                                                ? 'Visible'
                                                                : 'Hidden'
                                                        "
                                                    ></span>
                                                </div>

                                                <div class="mt-4">
                                                    <label
                                                        for="service-description-{{ $service->id }}"
                                                        class="
                                                            mb-2 block text-[11px]
                                                            font-bold uppercase
                                                            tracking-wider
                                                            text-gray-500
                                                            dark:text-gray-400
                                                        "
                                                    >
                                                        Landing description
                                                    </label>

                                                    <textarea
                                                        id="service-description-{{ $service->id }}"
                                                        name="services[{{ $service->id }}][landing_description]"
                                                        rows="3"
                                                        maxlength="500"
                                                        @input="
                                                            setServiceDescription(
                                                                {{ $service->id }},
                                                                $event.target.value
                                                            )
                                                        "
                                                        class="
                                                            editor-input w-full
                                                            resize-y rounded-2xl
                                                            border border-gray-200
                                                            bg-gray-50
                                                            px-3.5 py-3
                                                            text-sm leading-6
                                                            text-gray-800
                                                            outline-none
                                                            placeholder:text-gray-400
                                                            focus:border-brand-500
                                                            dark:border-gray-700
                                                            dark:bg-gray-900/60
                                                            dark:text-gray-200
                                                        "
                                                        placeholder="Describe this service for customers..."
                                                    >{{ $serviceDescription }}</textarea>

                                                    <p
                                                        class="
                                                            mt-1 text-right
                                                            text-[10px]
                                                            text-gray-400
                                                            dark:text-gray-500
                                                        "
                                                        x-text="
                                                            serviceById(
                                                                {{ $service->id }}
                                                            ).description.length +
                                                            '/500'
                                                        "
                                                    ></p>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div
                                        class="
                                            rounded-2xl border
                                            border-dashed
                                            border-gray-200 p-8
                                            text-center
                                            dark:border-gray-700
                                        "
                                    >
                                        <p
                                            class="
                                                text-sm font-bold
                                                text-gray-500
                                                dark:text-gray-400
                                            "
                                        >
                                            No services in this category.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="
                            rounded-2xl border border-dashed
                            border-gray-200 p-10 text-center
                            dark:border-gray-700
                        "
                    >
                        <p
                            class="
                                text-sm font-bold text-gray-600
                                dark:text-gray-400
                            "
                        >
                            No service categories found.
                        </p>
                    </div>
                @endforelse
            </div>
        </section>
    </form>

    {{-- Unsaved Changes Bar --}}
    <div
        x-show="dirty"
        x-cloak
        x-transition
        class="
            sticky bottom-4 z-40 mt-5
            rounded-2xl border
            border-brand-200
            bg-white/95
            p-3
            shadow-xl
            backdrop-blur-xl
            dark:border-brand-900/50
            dark:bg-gray-900/95
        "
    >
        <div
            class="
                flex flex-col gap-3
                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >
            <div class="flex items-center gap-3">
                <div
                    class="
                        flex h-9 w-9 shrink-0
                        items-center justify-center
                        rounded-xl
                        bg-brand-100
                        text-brand-600
                        dark:bg-brand-900/30
                        dark:text-brand-400
                    "
                >
                    <i
                        data-lucide="pencil"
                        class="h-4 w-4"
                    ></i>
                </div>

                <div>
                    <p
                        class="
                            text-sm font-extrabold
                            text-gray-900
                            dark:text-white
                        "
                    >
                        Unsaved changes
                    </p>

                    <p
                        class="
                            text-[11px]
                            text-gray-500
                            dark:text-gray-400
                        "
                    >
                        Your changes have not been saved yet.
                    </p>
                </div>
            </div>

            <div class="flex w-full gap-2 sm:w-auto">
                <button
                    type="button"
                    @click="discardChanges()"
                    class="
                        inline-flex flex-1
                        items-center justify-center
                        gap-2 rounded-xl
                        border border-gray-200
                        bg-white px-4 py-2.5
                        text-xs font-bold
                        text-gray-600
                        transition hover:bg-gray-50
                        dark:border-gray-700
                        dark:bg-gray-800
                        dark:text-gray-300
                        dark:hover:bg-gray-700
                        sm:flex-none
                    "
                >
                    <i
                        data-lucide="x"
                        class="h-4 w-4"
                    ></i>

                    Cancel
                </button>

                <button
                    type="submit"
                    form="landingEditorForm"
                    :disabled="submitting"
                    class="
                        inline-flex flex-1
                        items-center justify-center
                        gap-2 rounded-xl
                        bg-brand-600
                        px-5 py-2.5
                        text-xs font-bold
                        text-white
                        shadow-lg
                        shadow-brand-500/20
                        transition
                        hover:bg-brand-700
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                        sm:flex-none
                    "
                >
                    <i
                        data-lucide="save"
                        class="h-4 w-4"
                    ></i>

                    <span x-show="!submitting">
                        Save Changes
                    </span>

                    <span
                        x-show="submitting"
                        x-cloak
                    >
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Admin Access --}}
    @if (auth()->user()->roles->contains('name', 'admin'))
        <section
            class="
                editor-card mb-5 mt-5 overflow-hidden rounded-3xl
                border border-gray-200/80 bg-white
                dark:border-gray-800/70
                dark:bg-[#111827]
            "
        >
            <div
                class="
                    border-b border-gray-100 p-5
                    dark:border-gray-800 sm:p-6
                "
            >
                <div class="flex items-start gap-3">
                    <div
                        class="
                            flex h-9 w-9 shrink-0
                            items-center justify-center
                            rounded-xl bg-violet-50
                            text-violet-600
                            dark:bg-violet-900/30
                            dark:text-violet-400
                        "
                    >
                        <i
                            data-lucide="shield-check"
                            class="h-4 w-4"
                        ></i>
                    </div>

                    <div>
                        <h2
                            class="
                                text-base font-extrabold
                                text-gray-900
                                dark:text-white
                            "
                        >
                            Editor Access
                        </h2>

                        <p
                            class="
                                mt-1 text-xs leading-5
                                text-gray-500
                                dark:text-gray-400
                            "
                        >
                            Manage which receptionists can edit
                            landing-page content.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-3 p-4 sm:p-6">
                @forelse ($receptionists as $receptionist)
                    <div
                        class="
                            flex flex-col gap-3 rounded-2xl
                            border border-gray-200/80
                            bg-gray-50/60 p-4
                            sm:flex-row sm:items-center
                            sm:justify-between
                            dark:border-gray-700/70
                            dark:bg-gray-900/40
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="
                                    flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-xl bg-white
                                    text-sm font-extrabold
                                    text-brand-600 shadow-sm
                                    dark:bg-gray-800
                                    dark:text-brand-400
                                "
                            >
                                {{
                                    strtoupper(
                                        substr(
                                            $receptionist->first_name ?? 'U',
                                            0,
                                            1
                                        ) .
                                        substr(
                                            $receptionist->last_name ?? '',
                                            0,
                                            1
                                        )
                                    )
                                }}
                            </div>

                            <div>
                                <p
                                    class="
                                        text-sm font-extrabold
                                        text-gray-800
                                        dark:text-gray-200
                                    "
                                >
                                    {{ $receptionist->first_name }}
                                    {{ $receptionist->last_name }}
                                </p>

                                <p
                                    class="
                                        text-[11px] text-gray-400
                                        dark:text-gray-500
                                    "
                                >
                                    Receptionist
                                </p>
                            </div>
                        </div>

                        <div
                            class="
                                flex flex-wrap items-center gap-3
                            "
                        >
                            @if ($receptionist->can_edit_landing)
                                <span
                                    class="
                                        inline-flex items-center
                                        gap-1.5 rounded-full
                                        bg-brand-100 px-3 py-1.5
                                        text-[10px] font-bold
                                        text-brand-700
                                        dark:bg-brand-900/30
                                        dark:text-brand-400
                                    "
                                >
                                    <span
                                        class="
                                            h-1.5 w-1.5 rounded-full
                                            bg-brand-500
                                        "
                                    ></span>

                                    Can edit
                                </span>
                            @else
                                <span
                                    class="
                                        inline-flex items-center
                                        gap-1.5 rounded-full
                                        bg-gray-100 px-3 py-1.5
                                        text-[10px] font-bold
                                        text-gray-500
                                        dark:bg-gray-800
                                        dark:text-gray-400
                                    "
                                >
                                    <span
                                        class="
                                            h-1.5 w-1.5 rounded-full
                                            bg-gray-400
                                        "
                                    ></span>

                                    No access
                                </span>
                            @endif

                            <form
                                method="POST"
                                action="{{
                                    route(
                                        'admin.receptionist.toggle-landing',
                                        $receptionist
                                    )
                                }}"
                            >
                                @csrf
                                @method('PUT')

                                <button
                                    type="button"
                                    onclick="
                                        confirmLandingAccess(
                                            this,
                                            '{{ $receptionist->first_name }} {{ $receptionist->last_name }}',
                                            {{ $receptionist->can_edit_landing ? 'true' : 'false' }}
                                        )
                                    "
                                    class="
                                        inline-flex items-center
                                        gap-2 rounded-xl border
                                        px-3.5 py-2.5
                                        text-xs font-bold
                                        transition
                                        {{
                                            $receptionist->can_edit_landing
                                                ? 'border-red-200 text-red-600 hover:bg-red-50 dark:border-red-900/40 dark:text-red-400 dark:hover:bg-red-950/20'
                                                : 'border-brand-200 text-brand-700 hover:bg-brand-50 dark:border-brand-900/40 dark:text-brand-400 dark:hover:bg-brand-950/20'
                                        }}
                                    "
                                >
                                    <i
                                        data-lucide="{{
                                            $receptionist->can_edit_landing
                                                ? 'shield-off'
                                                : 'shield-check'
                                        }}"
                                        class="h-3.5 w-3.5"
                                    ></i>

                                    {{
                                        $receptionist->can_edit_landing
                                            ? 'Revoke access'
                                            : 'Grant access'
                                    }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p
                        class="
                            rounded-2xl bg-gray-50
                            p-6 text-center text-sm
                            font-semibold text-gray-500
                            dark:bg-gray-900
                            dark:text-gray-400
                        "
                    >
                        No receptionists found.
                    </p>
                @endforelse
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/preline@latest/dist/preline.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<script>
    function landingEditor(
        categories,
        heroImage,
        heroTitle,
        heroSubtitle
    ) {
        return {
            categories: Array.isArray(categories)
                ? categories
                : [],

            heroImage: heroImage || null,

            heroTitle: heroTitle || '',
            heroSubtitle: heroSubtitle || '',

            previewMode: 'mobile',

            removeHeroImage: false,
            submitting: false,
            dirty: false,

            init() {
                this.$nextTick(() => {
                    this.refreshIcons();

                    if (
                        window.HSStaticMethods &&
                        typeof window.HSStaticMethods.autoInit ===
                            'function'
                    ) {
                        window.HSStaticMethods.autoInit();
                    }
                });
            },

            refreshIcons() {
                if (
                    window.lucide &&
                    typeof window.lucide.createIcons ===
                        'function'
                ) {
                    window.lucide.createIcons();
                }
            },

            markDirty() {
                this.dirty = true;
            },

            categoryById(id) {
                const category =
                    this.categories.find(
                        item =>
                            Number(item.id) ===
                            Number(id)
                    );

                if (category) {
                    return category;
                }

                return {
                    id: id,
                    name: '',
                    show: false,
                    services: []
                };
            },

            serviceById(id) {
                for (
                    const category of this.categories
                ) {
                    if (
                        !Array.isArray(
                            category.services
                        )
                    ) {
                        continue;
                    }

                    const service =
                        category.services.find(
                            item =>
                                Number(item.id) ===
                                Number(id)
                        );

                    if (service) {
                        return service;
                    }
                }

                return {
                    id: id,
                    name: '',
                    price: '0.00',
                    show: false,
                    description: '',
                    image: null,
                    originalImage: null
                };
            },

            visibleServices(category) {
                if (
                    !category ||
                    !category.show ||
                    !Array.isArray(
                        category.services
                    )
                ) {
                    return [];
                }

                return category.services.filter(
                    service =>
                        service.show
                );
            },

            visibleCategoryCount() {
                return this.categories.filter(
                    category =>
                        category.show &&
                        this.visibleServices(
                            category
                        ).length > 0
                ).length;
            },

            setCategoryVisibility(
                id,
                visible
            ) {
                const category =
                    this.categoryById(id);

                category.show =
                    Boolean(visible);

                this.markDirty();
            },

            setServiceVisibility(
                id,
                visible
            ) {
                const service =
                    this.serviceById(id);

                service.show =
                    Boolean(visible);

                this.markDirty();
            },

            setServiceDescription(
                id,
                value
            ) {
                const service =
                    this.serviceById(id);

                service.description =
                    value || '';

                this.markDirty();
            },

            selectHeroImage(event) {
                const input =
                    event.target;

                const file =
                    input &&
                    input.files &&
                    input.files.length
                        ? input.files[0]
                        : null;

                if (!file) {
                    return;
                }

                if (
                    !file.type.startsWith(
                        'image/'
                    )
                ) {
                    input.value = '';
                    return;
                }

                const reader =
                    new FileReader();

                reader.onload =
                    loadEvent => {
                        const result =
                            loadEvent.target.result;

                        if (
                            typeof result !==
                                'string' ||
                            result === ''
                        ) {
                            return;
                        }

                        this.heroImage =
                            result;

                        this.removeHeroImage =
                            false;

                        this.markDirty();
                    };

                reader.onerror = () => {
                    console.error(
                        'Unable to preview hero image.'
                    );
                };

                reader.readAsDataURL(file);
            },

            removeHero() {
                this.heroImage = null;
                this.removeHeroImage =
                    true;

                const input =
                    document.getElementById(
                        'hero_image'
                    );

                if (input) {
                    input.value = '';
                }

                this.markDirty();
            },

            selectServiceImage(
                id,
                event
            ) {
                const input =
                    event.target;

                const file =
                    input &&
                    input.files &&
                    input.files.length
                        ? input.files[0]
                        : null;

                if (!file) {
                    return;
                }

                if (
                    !file.type.startsWith(
                        'image/'
                    )
                ) {
                    input.value = '';
                    return;
                }

                const reader =
                    new FileReader();

                reader.onload =
                    loadEvent => {
                        const result =
                            loadEvent.target.result;

                        if (
                            typeof result !==
                                'string' ||
                            result === ''
                        ) {
                            return;
                        }

                        input.dataset.previewUrl =
                            result;

                        this.updateServiceImagePreview(
                            id,
                            result,
                            true
                        );

                        const service =
                            this.serviceById(id);

                        if (service) {
                            service.image =
                                result;
                        }

                        const removeCheckbox =
                            document.getElementById(
                                'remove-service-image-' +
                                id
                            );

                        if (removeCheckbox) {
                            removeCheckbox.checked =
                                false;
                        }

                        this.markDirty();
                    };

                reader.onerror = () => {
                    console.error(
                        'Unable to preview service image.'
                    );
                };

                reader.readAsDataURL(file);
            },

            updateServiceImagePreview(
                id,
                src,
                isNew
            ) {
                const image =
                    document.getElementById(
                        'service-preview-' +
                        id
                    );

                const placeholder =
                    document.getElementById(
                        'service-placeholder-' +
                        id
                    );

                const badge =
                    document.getElementById(
                        'service-new-badge-' +
                        id
                    );

                if (image) {
                    image.src = src || '';
                    image.style.display =
                        src
                            ? 'block'
                            : 'none';
                }

                if (placeholder) {
                    placeholder.style.display =
                        src
                            ? 'none'
                            : 'flex';
                }

                if (badge) {
                    if (isNew) {
                        badge.classList.remove(
                            'hidden'
                        );
                    } else {
                        badge.classList.add(
                            'hidden'
                        );
                    }
                }
            },

            toggleRemoveServiceImage(
                id,
                remove
            ) {
                const service =
                    this.serviceById(id);

                const input =
                    document.getElementById(
                        'service-image-' +
                        id
                    );

                const pendingPreview =
                    input &&
                    input.dataset.previewUrl
                        ? input.dataset.previewUrl
                        : null;

                if (remove) {
                    service.image = null;

                    this.updateServiceImagePreview(
                        id,
                        null,
                        false
                    );
                } else if (
                    pendingPreview
                ) {
                    service.image =
                        pendingPreview;

                    this.updateServiceImagePreview(
                        id,
                        pendingPreview,
                        true
                    );
                } else {
                    service.image =
                        service.originalImage ||
                        null;

                    this.updateServiceImagePreview(
                        id,
                        service.originalImage ||
                            '',
                        false
                    );
                }

                this.markDirty();
            },

            discardChanges() {
                if (!this.dirty) {
                    window.location.href =
                        @json(route('admin.landing.editor'));

                    return;
                }

                Swal.fire({
                    icon: 'warning',

                    title: 'Discard unsaved changes?',

                    text:
                        'Your changes will not be saved.',

                    showCancelButton: true,

                    confirmButtonText:
                        'Discard changes',

                    cancelButtonText:
                        'Keep editing',

                    reverseButtons: true,

                    buttonsStyling: false,

                    customClass: {
                        popup:
                            'rounded-3xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900',

                        title:
                            'text-lg font-extrabold text-gray-900 dark:text-white',

                        htmlContainer:
                            'text-sm leading-6 text-gray-500 dark:text-gray-400',

                        confirmButton:
                            'rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-red-700 mx-1',

                        cancelButton:
                            'rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-200 mx-1 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href =
                            @json(route('admin.landing.editor'));
                    }
                });
            }
        };
    }

    function confirmLandingAccess(
        button,
        name,
        currentlyAllowed
    ) {
        const form =
            button.closest('form');

        if (!form) {
            return;
        }

        const action =
            currentlyAllowed
                ? 'Revoke'
                : 'Grant';

        const message =
            currentlyAllowed
                ? name +
                  ' will no longer be able to edit the landing page.'
                : name +
                  ' will be allowed to edit the landing page.';

        Swal.fire({
            icon: currentlyAllowed
                ? 'warning'
                : 'question',

            title:
                action +
                ' landing page access?',

            text: message,

            showCancelButton: true,

            confirmButtonText:
                action + ' access',

            cancelButtonText:
                'Cancel',

            reverseButtons: true,

            buttonsStyling: false,

            customClass: {
                popup:
                    'rounded-2xl',

                title:
                    'text-lg font-extrabold text-gray-900 dark:text-white',

                htmlContainer:
                    'text-sm text-gray-500 dark:text-gray-400',

                confirmButton:
                    'px-4 py-2.5 rounded-xl bg-teal-600 text-white text-sm font-bold mx-1 hover:bg-teal-700',

                cancelButton:
                    'px-4 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-bold mx-1 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
            }
        }).then(result => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            if (
                window.lucide &&
                typeof window.lucide.createIcons ===
                    'function'
            ) {
                window.lucide.createIcons();
            }

            if (
                window.HSStaticMethods &&
                typeof window.HSStaticMethods.autoInit ===
                    'function'
            ) {
                window.HSStaticMethods.autoInit();
            }
        }
    );
</script>
@endpush