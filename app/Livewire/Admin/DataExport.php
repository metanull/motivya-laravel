<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class DataExport extends Component
{
    /**
     * Redirect to the coaches CSV export download.
     */
    public function exportCoaches(): void
    {
        Gate::authorize('access-admin-panel');

        $this->redirect(route('admin.export', ['type' => 'coaches']), navigate: false);
    }

    /**
     * Redirect to the sessions CSV export download.
     */
    public function exportSessions(): void
    {
        Gate::authorize('access-admin-panel');

        $this->redirect(route('admin.export', ['type' => 'sessions']), navigate: false);
    }

    /**
     * Redirect to the payments CSV export download.
     */
    public function exportPayments(): void
    {
        Gate::authorize('access-admin-panel');

        $this->redirect(route('admin.export', ['type' => 'payments']), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.admin.data-export')
            ->title(__('admin.data_export_title'));
    }
}
