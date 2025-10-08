<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // --- AWAL PERBAIKAN ---
        $user = $request->user();
        // Cek jika role adalah 'tenaga_kerja'
        if ($user->role === 'tenaga_kerja') {
            // Arahkan ke dashboard tenaga kerja
            return redirect()->intended(route('tenaga_kerja.dashboard', absolute: false));
        }

        // Jika bukan, arahkan ke dashboard utama (admin/bendahara)
        return redirect()->intended(route('dashboard', absolute: false));
        // --- AKHIR PERBAIKAN ---
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
