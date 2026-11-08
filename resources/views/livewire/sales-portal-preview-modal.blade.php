<div
    x-data
    x-on:modal-closed.window="if ($event.detail.id === @js(\App\Livewire\SalesPortalPreviewModal::MODAL_ID) && $wire.isOpen) $wire.close()"
>
    <style>
        /* Edge-to-edge iframe inside Filament's modal; colours follow Filament's CSS variables (light + dark). */
        .sales-portal-preview .fi-modal-window { overflow: hidden; }
        .sales-portal-preview .fi-modal-content { padding: 0 !important; gap: 0; flex: 1 1 auto; min-height: 0; }
        .sp-preview { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
        .sp-preview-bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .75rem 1rem; border-bottom: 1px solid var(--gray-200); background: var(--gray-50); }
        .dark .sp-preview-bar { border-bottom-color: rgb(255 255 255 / .1); background: rgb(255 255 255 / .05); }
        .sp-preview-title { display: flex; align-items: center; gap: .5rem; font-size: .875rem; font-weight: 600; color: var(--gray-950); }
        .dark .sp-preview-title { color: #fff; }
        .sp-preview-actions { display: flex; align-items: center; gap: .5rem; }
        .sp-preview-body { position: relative; flex: 1 1 auto; min-height: 0; background: var(--gray-100); }
        .dark .sp-preview-body { background: var(--gray-950); }
        .sp-preview-loading { position: absolute; inset: 0; z-index: 1; display: flex; align-items: center; justify-content: center; background: inherit; }
        .sp-preview-frame { display: block; width: 100%; height: 100%; border: 0; background: #fff; }
    </style>

    <x-filament::icon-button
        icon="heroicon-m-eye"
        icon-size="lg"
        color="gray"
        label="Live Sales Portal Preview"
        tooltip="Live Sales Portal Preview"
        wire:click="open"
    />

    <x-filament::modal
        :id="\App\Livewire\SalesPortalPreviewModal::MODAL_ID"
        width="7xl"
        teleport="body"
        :close-button="false"
        :extra-modal-window-attribute-bag="new \Filament\Support\View\ComponentAttributeBag([
            'style' => 'height: min(90dvh, calc(100dvh - 2rem));',
        ])"
        class="sales-portal-preview"
        aria-label="Live Sales Portal Preview"
    >
        <div
            class="sp-preview"
            x-data="{
                loading: true,
                reload() {
                    const frame = this.$refs.frame
                    if (! frame) return
                    this.loading = true
                    try {
                        frame.contentWindow.location.reload()
                    } catch (e) {
                        frame.src = frame.src
                    }
                },
            }"
        >
            <div class="sp-preview-bar">
                <div class="sp-preview-title">
                    <x-filament::icon icon="heroicon-m-eye" class="h-5 w-5" style="width:1.25rem;height:1.25rem" />
                    <span>Live Sales Portal Preview</span>
                </div>

                <div class="sp-preview-actions">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        icon="heroicon-m-arrow-path"
                        x-on:click="reload()"
                    >
                        Refresh
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="$portalUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        color="gray"
                        size="sm"
                        icon="heroicon-m-arrow-top-right-on-square"
                    >
                        Open in New Tab
                    </x-filament::button>

                    <x-filament::icon-button
                        icon="heroicon-m-x-mark"
                        color="gray"
                        label="Close preview"
                        x-on:click="$dispatch('close-modal', { id: @js(\App\Livewire\SalesPortalPreviewModal::MODAL_ID) })"
                    />
                </div>
            </div>

            <div class="sp-preview-body">
                {{-- Only mounted while open: no portal traffic until someone asks for the preview. --}}
                @if ($isOpen)
                    <div class="sp-preview-loading" x-show="loading" x-cloak>
                        <x-filament::loading-indicator style="width:2rem;height:2rem" />
                    </div>

                    <iframe
                        x-ref="frame"
                        x-init="loading = true"
                        x-on:load="loading = false"
                        class="sp-preview-frame"
                        src="{{ $portalUrl }}"
                        title="Live Sales Portal Preview"
                        referrerpolicy="same-origin"
                    ></iframe>
                @endif
            </div>
        </div>
    </x-filament::modal>
</div>

