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

final class Edit extends Component
{
    public SessionForm $form;

    public SportSession $sportSession;

    public bool $isRecurring = false;

    public string $editScope = 'this';

    /**
     * The address query as it was when the session was loaded.
     * Used to detect changes and mark the address as unvalidated.
     */
    public string $initialAddressQuery = '';

    public function mount(SportSession $sportSession): void
    {
        Gate::authorize('update', $sportSession);

        $this->sportSession = $sportSession;
        $this->form->setFromModel($sportSession);
        $this->isRecurring = $sportSession->recurrence_group_id !== null;
        $this->initialAddressQuery = $this->form->addressQuery;
    }

    /**
     * Mark the address as unvalidated whenever the coach edits the query.
     */
    public function updatedFormAddressQuery(): void
    {
        if ($this->form->addressQuery !== $this->initialAddressQuery) {
            $this->form->addressValidated = false;
            $this->form->formattedAddress = '';
        }
    }

    /**
     * Validate the free-text address query against the geocoding provider.
     *
     * Called explicitly via `wire:click="validateAddress"` and also silently
     * inside `save()` as a convenience for coaches who did not click the button.
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

        // Update the baseline so further edits are detected correctly.
        $this->initialAddressQuery = $this->form->addressQuery;
    }

    public function save(SessionService $service, AddressValidationService $addressValidationService): void
    {
        Gate::authorize('update', $this->sportSession);

        // Silently attempt auto-validation when the query is present but the
        // coach hasn't clicked "Validate address" yet.
        if (! $this->form->addressValidated && trim($this->form->addressQuery) !== '') {
            $this->validateAddress($addressValidationService);
        }

        // Validate all Livewire-declared form fields (throws on failure).
        $this->form->validate();

        // After all field validations pass, the address must still be validated.
        if (! $this->form->addressValidated) {
            $this->addError('address', __('sessions.address_not_validated'));

            return;
        }

        if ($this->isRecurring && $this->editScope === 'all_future') {
            $count = $service->updateGroup($this->sportSession, $this->form->toServiceArray());

            $this->dispatch('notify', type: 'success', message: __('sessions.group_updated', ['count' => $count]));
        } else {
            $service->update($this->sportSession, $this->form->toServiceArray());

            $this->dispatch('notify', type: 'success', message: __('sessions.updated'));
        }

        $this->redirect(route('sessions.show', $this->sportSession), navigate: true);
    }

    public function render(): View
    {
        $coverImages = $this->form->activityType
            ? ActivityImage::where('activity_type', $this->form->activityType)->get()
            : collect();

        $coachProfile = auth()->user()?->coachProfile;

        return view('livewire.session.edit', [
            'activityTypes' => ActivityType::cases(),
            'levels' => SessionLevel::cases(),
            'coverImages' => $coverImages,
            'stripeReady' => $coachProfile?->isStripeReady() ?? false,
        ])->title(__('sessions.edit_title'));
    }
}
