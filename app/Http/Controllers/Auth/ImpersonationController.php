<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    public function store(Request $request, $userId): RedirectResponse
    {
        $impersonator = $request->user();
        $user = $this->getUser($userId);

        try {
            if (! $user) {
                return back()->withErrors(['User to be impersonated cannot be found.']);
            }

            if (! $user->canBeImpersonatedBy($impersonator)) {
                return back()->withErrors(['User cannot be impersonated.']);
            }

            // Save impersonator info
            session()->put('impersonator', $impersonator->only('id', 'email', 'name'));
            // Log and impersonate
            $this->track($user->getKey(), $impersonator->getKey(), 'Started');
            $this->switchLogin($user);

            return redirect('/dashboard');
        } catch (\Exception $e) {
            $this->track($user->getKey(), $impersonator->getKey(), 'Entering failed - ' . $e->getMessage(), 'error');

            return back()->withErrors(['User cannot be impersonated due to ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $impersonator = session()->has('impersonator') ? $this->getUser(session()->pull('impersonator')['id']) : null;
        $user = $request->user();

        try {
            if (! $impersonator) {
                return back()->withErrors(['No impersonator found.']);
            }

            // Log and impersonate
            $this->track($user->getKey(), $impersonator->getKey(), 'Ended');
            $this->switchLogin($impersonator);

            return redirect('/dashboard');
        } catch (\Exception $e) {
            $this->track($user->getKey(), $impersonator->getKey(), 'Leaving failed - ' . $e->getMessage(), 'error');

            // Force logout and reset impersonation
            session()->forget('impersonator');
            auth()->logout();

            return redirect()->route('login');
        }
    }

    private function getUser($id): ?User
    {
        return User::query()->where('id', $id)->first();
    }

    private function switchLogin(Authenticatable $user): void
    {
        auth()->logout();
        auth()->login($user);
    }

    private function track(int $impersonatorId, int $impersonatedId, string $message, $level = 'info'): void
    {
        Log::$level("[Impersonation][{$impersonatorId}] as [{$impersonatedId}] $message");
    }
}
