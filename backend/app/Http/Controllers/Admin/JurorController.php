<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurorController extends Controller
{
    public function index(): View
    {
        return view('admin.jurors', [
            'jurors' => User::query()->where('role', 'jurado')->orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $juror = User::query()->create([
            'name' => $validated['full_name'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'jurado',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Audit::log('jury_user_created', 'user', $juror->id, $request->user(), $request, [
            'email' => $juror->email,
        ]);

        return back()->with('status', 'Jurado creado. Ya puede iniciar sesión en /admin/login.');
    }

    public function toggleStatus(Request $request, User $juror): RedirectResponse
    {
        abort_unless($juror->role === 'jurado', 404);

        $juror->update(['is_active' => ! $juror->is_active]);

        Audit::log('jury_user_status_toggled', 'user', $juror->id, $request->user(), $request, [
            'is_active' => $juror->is_active,
        ]);

        return back()->with('status', $juror->is_active ? 'Jurado activado.' : 'Jurado desactivado.');
    }
}
