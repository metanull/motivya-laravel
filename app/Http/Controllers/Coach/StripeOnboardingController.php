<?php

declare(strict_types=1);

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StripeOnboardingController extends Controller
{
    public function handleReturn(): RedirectResponse
    {
        return redirect()->route('coach.dashboard');
    }

    public function refresh(Request $request, StripeConnectService $stripeConnectService): RedirectResponse
    {
        $coachProfile = $request->user()?->coachProfile;

        abort_if($coachProfile === null || ! is_string($coachProfile->stripe_account_id) || $coachProfile->stripe_account_id === '', 404);

        return redirect()->away($stripeConnectService->generateOnboardingLink($coachProfile));
    }
}