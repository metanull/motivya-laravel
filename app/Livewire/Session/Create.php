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

final class Create extends Component
{
    public SessionForm $form;

    public function save(SessionService $service): void
    {
        Gate::authorize('create', SportSession::class);

        $this->form->validate();

        $session = $service->create(auth()->user(), $this->form->toServiceArray());

        $this->dispatch('notify', type: 'success', message: __('sessions.created'));
        $this->redirect(route('sessions.show', $session), navigate: true);
    }

    public function render(): View
    {
        $coverImages = $this->form->activityType
            ? ActivityImage::where('activity_type', $this->form->activityType)->get()
            : collect();

        return view('livewire.session.create', [
            'activityTypes' => ActivityType::cases(),
            'levels' => SessionLevel::cases(),
            'coverImages' => $coverImages,
        ])->title(__('sessions.create_title'));
    }
}
