<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Livewire\Forms\CoachApplicationForm;
use App\Services\AddressValidationService;
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

    /**
     * Validate the free-text address query against the geocoding provider.
     *
     * Called explicitly via `wire:click="validateAddress"` on the form's
     * "Validate address" button, and also called silently inside `submit()` when
     * the user submits without first clicking the button.
     */
    public function validateAddress(AddressValidationService $service): void
    {
        $query = trim($this->form->addressQuery);

        if ($query === '') {
            $this->addError('form.addressQuery', __('coach.address_not_found'));

            return;
        }

        $result = $service->validate($query, app()->getLocale());

        if ($result === null) {
            $this->form->addressValidated = false;
            $this->addError('form.addressQuery', __('coach.address_not_found'));

            return;
        }

        // Populate all address fields from the validated result.
        $this->form->addressValidated = true;
        $this->form->formattedAddress = $result->formattedAddress;
        $this->form->streetAddress = $result->streetAddress;
        $this->form->locality = $result->locality;
        $this->form->postalCode = $result->postalCode;
        $this->form->country = $result->country;
        $this->form->latitude = $result->latitude;
        $this->form->longitude = $result->longitude;
        $this->form->geocodingProvider = $result->provider;
        $this->form->geocodingPlaceId = $result->providerPlaceId;
        $this->form->geocodedAt = now()->toISOString();
        $this->form->geocodingPayload = $result->rawPayload;
    }

    public function submit(CoachApplicationService $service, AddressValidationService $addressValidationService): void
    {
        // Silently attempt auto-validation when the query is present but the
        // coach hasn't clicked "Validate address" yet.
        if (! $this->form->addressValidated && trim($this->form->addressQuery) !== '') {
            $this->validateAddress($addressValidationService);
        }

        // Validate all Livewire-declared form fields (throws on failure).
        $this->form->validate();

        // After all field validations pass, the address must still be validated.
        // This guard is reached when the query is a valid-length string but the
        // geocoding provider returned no result.
        if (! $this->form->addressValidated) {
            $this->addError('form.addressQuery', __('coach.address_not_validated'));

            return;
        }

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
            'addressQuery' => 'required|string|min:5|max:500',
            'enterprise_number' => 'required|string|regex:/^\d{4}\.\d{3}\.\d{3}$/',
        ];
    }
}
