<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Livewire\Forms\CoachProfileForm;
use App\Models\CoachProfile as CoachProfileModel;
use App\Models\User;
use App\Services\AddressValidationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProfileEdit extends Component
{
    public CoachProfileForm $form;

    public bool $isVatSubject = false;

    /**
     * The address query as it was when the profile was loaded.
     * Used to detect changes and mark the address as unvalidated.
     */
    public string $initialAddressQuery = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $profile = $user->coachProfile;

        if ($profile !== null) {
            $this->form->setFromModel($profile);
            $this->isVatSubject = $profile->is_vat_subject ?? false;
        }

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
            $this->addError('form.addressQuery', __('coach.address_not_found'));

            return;
        }

        $result = $service->validate($query, app()->getLocale());

        if ($result === null) {
            $this->form->addressValidated = false;
            $this->addError('form.addressQuery', __('coach.address_not_found'));

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

    public function save(AddressValidationService $addressValidationService): void
    {
        // Silently attempt auto-validation when the query is present but the
        // coach hasn't clicked "Validate address" yet.
        if (! $this->form->addressValidated && trim($this->form->addressQuery) !== '') {
            $this->validateAddress($addressValidationService);
        }

        // Validate all Livewire-declared form fields (throws on failure).
        $this->form->validate();

        // After all field validations pass, the address must still be validated.
        if (! $this->form->addressValidated) {
            $this->addError('form.addressQuery', __('coach.address_not_validated'));

            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $profile = $user->coachProfile;

        if ($profile === null) {
            $profile = new CoachProfileModel;
            $profile->user_id = $user->id;
        }

        $profile->fill($this->form->toServiceArray());
        $profile->save();

        $this->dispatch('notify', type: 'success', message: __('coach.profile_updated'));
    }

    public function render(): View
    {
        return view('livewire.coach.profile-edit')
            ->title(__('coach.profile_edit_title'));
    }
}
