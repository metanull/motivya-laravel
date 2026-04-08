<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Livewire\Forms\CoachApplicationForm;
use App\Services\CoachApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Application extends Component
{
    public CoachApplicationForm $form;

    public int $step = 1;

    public function mount(): void
    {
        Gate::authorize('apply-as-coach');
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->form->validate($this->step1Rules());
        } elseif ($this->step === 2) {
            $this->form->validate($this->step2Rules());
        }

        $this->step = min($this->step + 1, 3);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function submit(CoachApplicationService $service): void
    {
        $this->form->validate();

        Gate::authorize('apply-as-coach');

        $service->apply(Auth::user(), $this->form->toServiceArray());

        session()->flash('status', __('coach.application_submitted'));

        $this->redirect(route('home'));
    }

    public function render(): View
    {
        return view('livewire.coach.application')
            ->title(__('coach.application_title'));
    }

    /**
     * @return array<string, mixed>
     */
    private function step1Rules(): array
    {
        return [
            'specialties' => 'required|array|min:1',
            'bio' => 'nullable|string|max:2000',
            'experience_level' => 'nullable|string|in:beginner,intermediate,advanced,expert',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function step2Rules(): array
    {
        return [
            'postal_code' => 'required|string|regex:/^[1-9]\d{3}$/',
            'country' => 'required|string|size:2',
            'enterprise_number' => 'required|string|regex:/^\d{4}\.\d{3}\.\d{3}$/',
        ];
    }
}
