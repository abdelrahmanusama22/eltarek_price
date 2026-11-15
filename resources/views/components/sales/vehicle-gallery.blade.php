@props([
    'images' => [],
    'car' => null,
    'title' => null,
])

@php
    // 1. Normalize images input
    $rawImages = $images;
    if (empty($rawImages) && $car && $car->priceEntry) {
        $rawImages = $car->priceEntry->images ?? [];
    }

    $galleryUrls = [];
    if (is_array($rawImages)) {
        foreach ($rawImages as $img) {
            if (empty($img)) {
                continue;
            }
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, '//')) {
                $galleryUrls[] = $img;
            } elseif (str_starts_with($img, '/storage/')) {
                $galleryUrls[] = asset($img);
            } else {
                $galleryUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($img);
            }
        }
    }

    $totalImages = count($galleryUrls);
    $vehicleTitle = $title ?? ($car ? "{$car->brand?->name} {$car->model_name} • {$car->model_sales_code}" : 'Vehicle Photo Gallery');
@endphp

<div
    x-data="{
        images: {{ Js::from($galleryUrls) }},
        total: {{ $totalImages }},
        activeIndex: 0,
        lightboxOpen: false,
        touchStartX: 0,
        touchEndX: 0,

        init() {
            // Watch for index change to auto-scroll active thumbnail into view
            this.$watch('activeIndex', (idx) => {
                this.scrollToThumb(idx);
            });
        },

        next() {
            if (this.total <= 1) return;
            this.activeIndex = (this.activeIndex + 1) % this.total;
        },

        prev() {
            if (this.total <= 1) return;
            this.activeIndex = (this.activeIndex - 1 + this.total) % this.total;
        },

        setIndex(idx) {
            if (idx >= 0 && idx < this.total) {
                this.activeIndex = idx;
            }
        },

        openLightbox(idx = null) {
            if (idx !== null) this.activeIndex = idx;
            this.lightboxOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeLightbox() {
            this.lightboxOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        scrollToThumb(idx) {
            this.$nextTick(() => {
                const el = this.$refs['thumb_' + idx];
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
                const lbEl = this.$refs['lb_thumb_' + idx];
                if (lbEl) {
                    lbEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            });
        },

        handleTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
        },

        handleTouchEnd(e) {
            this.touchEndX = e.changedTouches[0].clientX;
            const diff = this.touchStartX - this.touchEndX;
            if (Math.abs(diff) > 40) {
                if (diff > 0) this.next();
                else this.prev();
            }
        }
    }"
    @keydown.escape.window="if (lightboxOpen) closeLightbox()"
    @keydown.arrow-left.window="if (lightboxOpen) prev()"
    @keydown.arrow-right.window="if (lightboxOpen) next()"
    class="w-full select-none"
>
    @if ($totalImages === 0)
        {{-- ── 1. Graceful Empty State ──────────────────────────────────────── --}}
        <div class="relative w-full h-72 sm:h-80 md:h-96 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 flex flex-col items-center justify-center p-6 text-center transition-all duration-300">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center mb-4 text-gray-400 dark:text-gray-500 shadow-inner">
                <svg class="w-9 h-9 sm:w-11 sm:h-11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </div>
            <h4 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-200">No Photos Available Yet</h4>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm">
                Photos for <strong class="text-gray-700 dark:text-gray-300">{{ $vehicleTitle }}</strong> have not been uploaded by brand managers yet.
            </p>
            <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Automated WebP Gallery Ready</span>
            </div>
        </div>
    @else
        {{-- ── 2. Main Gallery Container ───────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            
            {{-- Main Slider Area --}}
            <div
                class="relative w-full h-80 sm:h-96 md:h-[420px] bg-gray-950 overflow-hidden group cursor-zoom-in"
                @touchstart="handleTouchStart($event)"
                @touchend="handleTouchEnd($event)"
                @click="openLightbox(activeIndex)"
            >
                {{-- Ambient blurred backdrop: Fills any letterbox space with adaptive color matching --}}
                <template x-for="(img, idx) in images" :key="'bg_' + idx">
                    <img
                        :src="img"
                        x-show="activeIndex === idx"
                        x-transition:enter="transition-opacity ease-out duration-500"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-40"
                        x-transition:leave="transition-opacity ease-in duration-300"
                        x-transition:leave-start="opacity-40"
                        x-transition:leave-end="opacity-0"
                        alt=""
                        aria-hidden="true"
                        class="absolute inset-0 w-full h-full object-cover filter blur-2xl opacity-40 scale-125 pointer-events-none"
                    />
                </template>

                {{-- Crisp foreground car image --}}
                <template x-for="(img, idx) in images" :key="'main_' + idx">
                    <div
                        x-show="activeIndex === idx"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200 transform absolute inset-0"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="relative z-10 w-full h-full flex items-center justify-center p-2 sm:p-4"
                    >
                        <img
                            :src="img"
                            :alt="'{{ addslashes($vehicleTitle) }} - Photo ' + (idx + 1)"
                            loading="lazy"
                            class="max-h-full max-w-full object-contain rounded-lg transition-transform duration-300 group-hover:scale-[1.01]"
                        />
                    </div>
                </template>

                {{-- Top Floating Badges --}}
                <div class="absolute top-3 inset-x-3 z-20 flex items-center justify-between pointer-events-none">
                    {{-- Click to Enlarge Pill --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/60 backdrop-blur-md border border-white/10 shadow-lg pointer-events-auto hover:bg-black/80 transition-colors">
                        <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                        <span>Full Screen</span>
                    </span>

                    {{-- Counter Pill --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/60 backdrop-blur-md border border-white/10 shadow-lg">
                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                        <span x-text="(activeIndex + 1) + ' / ' + total"></span>
                    </span>
                </div>

                {{-- Navigation Arrows (Visible when > 1 image) --}}
                <template x-if="total > 1">
                    <div>
                        <!-- Previous Arrow -->
                        <button
                            type="button"
                            @click.stop="prev()"
                            aria-label="Previous Photo"
                            class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-black/40 hover:bg-black/80 text-white backdrop-blur-md flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-95 shadow-xl border border-white/10 opacity-80 group-hover:opacity-100 focus:outline-none"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>

                        <!-- Next Arrow -->
                        <button
                            type="button"
                            @click.stop="next()"
                            aria-label="Next Photo"
                            class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-black/40 hover:bg-black/80 text-white backdrop-blur-md flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-95 shadow-xl border border-white/10 opacity-80 group-hover:opacity-100 focus:outline-none"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- ── 3. Thumbnail Navigation Strip ───────────────────────────── --}}
            <template x-if="total > 1">
                <div class="p-3 bg-gray-50/90 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scroll-smooth scrollbar-thin">
                        <template x-for="(img, idx) in images" :key="'thumb_' + idx">
                            <button
                                type="button"
                                :x-ref="'thumb_' + idx"
                                @click="setIndex(idx)"
                                :class="activeIndex === idx
                                    ? 'border-primary ring-2 ring-primary/40 scale-105 shadow-md opacity-100'
                                    : 'border-transparent opacity-60 hover:opacity-100 hover:scale-102'"
                                class="relative w-20 h-14 sm:w-24 sm:h-16 flex-shrink-0 rounded-xl overflow-hidden border-2 transition-all duration-200 bg-gray-900 focus:outline-none"
                                :aria-label="'Go to photo ' + (idx + 1)"
                            >
                                <img
                                    :src="img"
                                    alt=""
                                    loading="lazy"
                                    class="w-full h-full object-cover"
                                />
                                {{-- Active overlay badge --}}
                                <div
                                    x-show="activeIndex === idx"
                                    class="absolute inset-0 bg-primary/10 pointer-events-none"
                                ></div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- ── 4. Fullscreen Lightbox Modal ────────────────────────────────── --}}
        <div
            x-show="lightboxOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex flex-col justify-between bg-black/95 backdrop-blur-xl p-3 sm:p-6"
            @click.self="closeLightbox()"
        >
            {{-- Lightbox Top Header Bar --}}
            <div class="flex items-center justify-between text-white z-20 pb-3 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/10 text-white font-mono text-xs font-bold" x-text="(activeIndex + 1) + '/' + total"></span>
                    <span class="text-sm sm:text-base font-semibold truncate max-w-xs sm:max-w-md">{{ $vehicleTitle }}</span>
                </div>
                
                <div class="flex items-center gap-2">
                    {{-- Open image in new tab --}}
                    <a
                        :href="images[activeIndex]"
                        target="_blank"
                        class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-gray-200 hover:text-white transition-colors text-xs flex items-center gap-1.5 focus:outline-none"
                        title="Open full size in new tab"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        <span class="hidden sm:inline">Original</span>
                    </a>

                    {{-- Close Button --}}
                    <button
                        type="button"
                        @click="closeLightbox()"
                        class="p-2 rounded-lg bg-white/10 hover:bg-red-600 text-gray-200 hover:text-white transition-colors focus:outline-none"
                        title="Close (Esc)"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Lightbox Center Image Stage --}}
            <div
                class="relative flex-grow flex items-center justify-center my-4 overflow-hidden"
                @touchstart="handleTouchStart($event)"
                @touchend="handleTouchEnd($event)"
                @click.self="closeLightbox()"
            >
                {{-- Left Nav Arrow in Lightbox --}}
                <template x-if="total > 1">
                    <button
                        type="button"
                        @click.stop="prev()"
                        aria-label="Previous Photo"
                        class="absolute left-2 sm:left-4 z-30 w-12 h-12 rounded-full bg-white/10 hover:bg-white/25 text-white backdrop-blur-md flex items-center justify-center transition-all transform hover:scale-110 active:scale-95 border border-white/20 focus:outline-none"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                </template>

                {{-- Fullscreen Image with Smooth Fade --}}
                <template x-for="(img, idx) in images" :key="'lb_' + idx">
                    <div
                        x-show="activeIndex === idx"
                        x-transition:enter="transition ease-out duration-200 transform"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150 transform absolute"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="max-h-[75vh] sm:max-h-[80vh] max-w-full flex items-center justify-center"
                    >
                        <img
                            :src="img"
                            :alt="'{{ addslashes($vehicleTitle) }} - Photo ' + (idx + 1)"
                            class="max-h-[75vh] sm:max-h-[80vh] max-w-[90vw] object-contain rounded-xl shadow-2xl"
                        />
                    </div>
                </template>

                {{-- Right Nav Arrow in Lightbox --}}
                <template x-if="total > 1">
                    <button
                        type="button"
                        @click.stop="next()"
                        aria-label="Next Photo"
                        class="absolute right-2 sm:right-4 z-30 w-12 h-12 rounded-full bg-white/10 hover:bg-white/25 text-white backdrop-blur-md flex items-center justify-center transition-all transform hover:scale-110 active:scale-95 border border-white/20 focus:outline-none"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </template>
            </div>

            {{-- Lightbox Bottom Thumbnails Row --}}
            <template x-if="total > 1">
                <div class="pt-3 border-t border-white/10 z-20">
                    <div class="flex items-center justify-center gap-2 overflow-x-auto pb-1 max-w-4xl mx-auto scroll-smooth scrollbar-thin">
                        <template x-for="(img, idx) in images" :key="'lb_thumb_' + idx">
                            <button
                                type="button"
                                :x-ref="'lb_thumb_' + idx"
                                @click="setIndex(idx)"
                                :class="activeIndex === idx
                                    ? 'border-primary ring-2 ring-primary/50 scale-110 opacity-100'
                                    : 'border-transparent opacity-40 hover:opacity-90 hover:scale-105'"
                                class="w-16 h-11 sm:w-20 sm:h-14 flex-shrink-0 rounded-lg overflow-hidden border-2 transition-all duration-200 bg-gray-900 focus:outline-none"
                            >
                                <img
                                    :src="img"
                                    alt=""
                                    class="w-full h-full object-cover"
                                />
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
