<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Livewire\Forms\SessionForm;
use App\Models\ActivityImage;
use App\Models\SportSession;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Edit extends Component
{
    public SessionForm $form;

    public SportSession $sportSession;

    public function mount(SportSession $sportSession): void
    {
        Gate::authorize('update', $sportSession);

        $this->sportSession = $sportSession;
        $this->form->setFromModel($sportSession);
    }

    public function save(SessionService $service): void
    {
        Gate::authorize('update', $this->sportSession);

        $this->form->validate();

        $service->update($this->sportSession, $this->form->toServiceArray());

        $this->dispatch('notify', type: 'success', message: __('sessions.updated'));
        $this->redirect(route('sessions.show', $this->sportSession), navigate: true);
    }

    public function render(): View
    {
        $coverImages = $this->form->activityType
            ? ActivityImage::where('activity_type', $this->form->activityType)->get()
            : collect();

        return view('livewire.session.edit', [
            'activityTypes' => ActivityType::cases(),
            'levels' => SessionLevel::cases(),
            'coverImages' => $coverImages,
        ])->title(__('sessions.edit_title'));
    }
}
