<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\CoachProfileStatus;
use App\Models\CoachProfile;
use App\Services\AdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class CoachApproval extends Component
{
    use WithPagination;

    public string $rejectionReason = '';

    public ?int $rejectingProfileId = null;

    public function approve(int $profileId, AdminService $service): void
    {
        $coachProfile = CoachProfile::with('user')->findOrFail($profileId);

        $service->approveCoach($coachProfile);

        $this->dispatch('notify', type: 'success', message: __('admin.coach_approved'));
    }

    public function confirmReject(int $profileId): void
    {
        $this->rejectingProfileId = $profileId;
        $this->rejectionReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingProfileId = null;
        $this->rejectionReason = '';
    }

    public function reject(AdminService $service): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        if ($this->rejectingProfileId === null) {
            return;
        }

        $coachProfile = CoachProfile::with('user')->findOrFail($this->rejectingProfileId);

        $service->rejectCoach($coachProfile, $this->rejectionReason);

        $this->rejectingProfileId = null;
        $this->rejectionReason = '';

        $this->dispatch('notify', type: 'success', message: __('admin.coach_rejected'));
    }

    public function render(): View
    {
        $pendingApplications = CoachProfile::with('user')
            ->where('status', CoachProfileStatus::Pending)
            ->latest()
            ->paginate(10);

        return view('livewire.admin.coach-approval', [
            'pendingApplications' => $pendingApplications,
        ])->title(__('admin.coach_approval_title'));
    }
}
