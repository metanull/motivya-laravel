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

        if ($this->form->isRecurring) {
            $sessions = $service->createRecurring(
                auth()->user(),
                $this->form->toServiceArray(),
                $this->form->numberOfWeeks,
            );
            $session = $sessions->first();

            $this->dispatch('notify', type: 'success', message: __('sessions.recurring_created', ['count' => $sessions->count()]));
        } else {
            $session = $service->create(auth()->user(), $this->form->toServiceArray());

            $this->dispatch('notify', type: 'success', message: __('sessions.created'));
        }

        $this->redirect(route('sessions.show', $session), navigate: true);
    }

    public function render(): View
    {
        $coverImages = $this->form->activityType
            ? ActivityImage::where('activity_type', $this->form->activityType)->get()
            : collect();

        $coachProfile = auth()->user()?->coachProfile;

        return view('livewire.session.create', [
            'activityTypes' => ActivityType::cases(),
            'levels' => SessionLevel::cases(),
            'coverImages' => $coverImages,
            'stripeReady' => $coachProfile?->isStripeReady() ?? false,
        ])->title(__('sessions.create_title'));
    }
}
