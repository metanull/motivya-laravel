<?php

declare(strict_types=1);

namespace App\Livewire\Athlete;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Favourites extends Component
{
    public function render(): View
    {
        /** @var User $athlete */
        $athlete = auth()->user();

        $favourites = $athlete->favouriteSessions()
            ->with('coach')
            ->orderByPivot('created_at', 'desc')
            ->get();

        return view('livewire.athlete.favourites', [
            'favourites' => $favourites,
        ])->title(__('athlete.favourites_title'));
    }
}
