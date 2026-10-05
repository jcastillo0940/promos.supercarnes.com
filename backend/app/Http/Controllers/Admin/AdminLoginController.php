<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FanlycZone;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->defaultRedirect(Auth::user()));
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

            $hasAccess = $user && (
                $user->isAdmin() ||
                $user->isOperator() ||
                $user->isSupervisor() ||
                $user->isJury() ||
                $user->isFanlycStaff()
            );

            if (! $hasAccess) {
                Auth::logout();
                return back()->withErrors(['email' => 'No tienes permisos para acceder al backoffice.']);
            }

            if (! $user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Tu cuenta está desactivada.']);
            }

            $request->session()->regenerate();

            return redirect()->intended($this->defaultRedirect($user));
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    private function defaultRedirect(User $user): string
    {
        return match (true) {
            $user->isOperator()   => $user->firstModuleRoute(),
            $user->isSupervisor() => route('admin.prize-delivery'),
            $user->isJury()       => route('admin.fonda-jury'),
            $user->isFanlycStaff() => route('admin.fanlyc.redeem', \App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->value('code') ?? ''),
            default               => route('admin.invoice-backoffice'),
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
