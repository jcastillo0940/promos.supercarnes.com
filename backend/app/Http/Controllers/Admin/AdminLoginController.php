<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.invoice-backoffice');
        }

        if (Auth::check() && Auth::user()->isSupervisor()) {
            return redirect()->route('admin.prize-delivery');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, remember: true)) {
            $user = Auth::user();

            if (! $user || (! $user->isAdmin() && ! $user->isSupervisor())) {
                Auth::logout();
                return back()->withErrors(['email' => 'No tienes permisos para acceder al backoffice.']);
            }

            $request->session()->regenerate();

            return redirect()->intended($user->isSupervisor() ? route('admin.prize-delivery') : route('admin.invoice-backoffice'));
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
