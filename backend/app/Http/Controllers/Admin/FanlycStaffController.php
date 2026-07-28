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

class FanlycStaffController extends Controller
{
    public function index(): View
    {
        return view('admin.fanlyc-staff', [
            'staff' => User::query()->where('role', 'staff_fanlyc')->orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $staff = User::query()->create([
            'name' => $validated['full_name'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff_fanlyc',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Audit::log('fanlyc_staff_user_created', 'user', $staff->id, $request->user(), $request, [
            'email' => $staff->email,
        ]);

        return back()->with('status', 'Cuenta de staff creada. Ya puede iniciar sesion en /admin/login.');
    }

    public function toggleStatus(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff_fanlyc', 404);

        $staff->update(['is_active' => ! $staff->is_active]);

        Audit::log('fanlyc_staff_user_status_toggled', 'user', $staff->id, $request->user(), $request, [
            'is_active' => $staff->is_active,
        ]);

        return back()->with('status', $staff->is_active ? 'Cuenta activada.' : 'Cuenta desactivada.');
    }
}
