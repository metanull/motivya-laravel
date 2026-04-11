<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Livewire\Forms\CoachProfileForm;
use App\Models\CoachProfile as CoachProfileModel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProfileEdit extends Component
{
    public CoachProfileForm $form;

    public bool $isVatSubject = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $profile = $user->coachProfile;

        if ($profile !== null) {
            $this->form->setFromModel($profile);
            $this->isVatSubject = $profile->is_vat_subject ?? false;
        }
    }

    public function save(): void
    {
        $this->form->validate();

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
