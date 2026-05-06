<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Livewire\Forms\SessionForm;
use App\Models\ActivityImage;
use App\Models\SportSession;
use App\Services\AddressValidationService;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Create extends Component
{
    public SessionForm $form;

    /**
     * Validate the free-text address query against the geocoding provider.
     *
     * Called explicitly via `wire:click="validateAddress"` on the form's
     * "Validate address" button, and also called silently inside `save()` when
     * the user submits without first clicking the button.
     */
    public function validateAddress(AddressValidationService $service): void
    {
        $query = trim($this->form->addressQuery);

        if ($query === '') {
            $this->addError('form.addressQuery', __('sessions.address_not_found'));

            return;
        }

        $result = $service->validate($query, app()->getLocale());

        if ($result === null) {
            $this->form->addressValidated = false;
            $this->addError('address', __('sessions.address_not_found'));

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

    public function save(SessionService $service, AddressValidationService $addressValidationService): void
    {
        Gate::authorize('create', SportSession::class);

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
            $this->addError('address', __('sessions.address_not_validated'));

            return;
        }

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
