<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Enums\CoachProfileStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Profile extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        if ($user->role !== UserRole::Coach) {
            throw new NotFoundHttpException;
        }

        $this->user = $user;
    }

    public function render(): View
    {
        $profile = $this->user->coachProfile;

        $upcomingSessions = SportSession::where('coach_id', $this->user->id)
            ->whereIn('status', [SessionStatus::Published, SessionStatus::Confirmed])
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(6)
            ->get();

        return view('livewire.coach.profile', [
            'profile' => $profile,
            'upcomingSessions' => $upcomingSessions,
            'isVerified' => $profile?->status === CoachProfileStatus::Approved,
        ])->title($this->user->name.' — '.__('coach.profile_title'));
    }
}
