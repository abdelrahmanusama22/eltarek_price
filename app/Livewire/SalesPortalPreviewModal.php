<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Topbar button + large modal that renders the live Sales Portal in an <iframe>,
 * so Admins / Brand Managers can see what sales reps see without leaving the panel.
 */
class SalesPortalPreviewModal extends Component
{
    public const MODAL_ID = 'sales-portal-preview';

    public bool $isOpen = false;

    /** Locked: the client must not be able to point the iframe somewhere else. */
    #[Locked]
    public string $portalUrl = '';

    /**
     * Who may see the preview (used by the render hook AND re-checked on every action).
     */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isBrandManager());
    }

    /**
     * @param  string|null  $portalUrl  Override the preview URL. Defaults to the Sales Portal home.
     */
    public function mount(?string $portalUrl = null): void
    {
        // Relative URL on purpose: it stays same-origin whatever host the admin is browsing from,
        // which keeps cookies working and lets the "Refresh" button reach into the iframe.
        $this->portalUrl = $portalUrl ?? route('sales.dashboard', absolute: false);
    }

    public function open(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->isOpen = true;

        $this->dispatch('open-modal', id: self::MODAL_ID);
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function render(): View
    {
        return view('livewire.sales-portal-preview-modal', [
            'canAccess' => static::canAccess(),
        ]);
    }
}

