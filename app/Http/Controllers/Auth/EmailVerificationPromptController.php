<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user && $user->hasVerifiedEmail()) {
            if (!$user->isPaymentVerified()) {
                return redirect()->route('paket-langganan.index');
            }
            if (!$user->isProfileCompleted()) {
                return redirect()->route('onboarding.index');
            }
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
